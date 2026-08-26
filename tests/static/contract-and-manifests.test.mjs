import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import test from 'node:test';

import { buildManifests } from '../../tools/generate-manifests.mjs';
import {
  assertSameSet,
  assertValidContract,
  getOperationsByTransport,
  loadContract,
  readJson,
  toRestFunctionName
} from '../helpers/contract.mjs';
import { fromRoot } from '../helpers/paths.mjs';

test('canonical operation contract is structurally valid', async () => {
  assertValidContract(await loadContract());
});

test('generated manifests are current', async () => {
  const expected = buildManifests(await loadContract());

  for (const [relativePath, manifest] of Object.entries(expected)) {
    assert.deepEqual(await readJson(relativePath), manifest, `${relativePath} must be generated from the contract.`);
  }
});

test('REST declarations match the canonical contract', async () => {
  const contract = await loadContract();
  const source = await fs.readFile(fromRoot('db/services.php'), 'utf8');
  const declared = [...source.matchAll(/^\s*'(local_moodlia_[a-z0-9_]+)'\s*=>\s*\[/gm)].map((match) => match[1]);
  const expected = getOperationsByTransport(contract, 'rest').map((operation) => toRestFunctionName(contract, operation.name));

  assertSameSet(declared, expected, 'Moodle REST declarations');
});

test('every REST operation has operation and external PHP classes', async () => {
  const contract = await loadContract();

  for (const operation of getOperationsByTransport(contract, 'rest')) {
    await fs.access(fromRoot('classes/operation', `${operation.name}.php`));
    await fs.access(fromRoot('classes/external', `${operation.name}.php`));
  }
});

test('plugin management writes require the dedicated capability', async () => {
  const contract = await loadContract();
  const operation = contract.operations.find((entry) => entry.name === 'set_plugin_enabled');

  assert.ok(operation);
  assert.equal(operation.type, 'write');
  assert.ok(operation.capabilities.includes('local/moodlia:manageplugins'));
  assert.ok(operation.tests.includes('parity'));
});

test('section summary inputs preserve HTML through the REST adapters', async () => {
  for (const operation of ['create_section', 'update_section']) {
    const source = await fs.readFile(fromRoot('classes/external', `${operation}.php`), 'utf8');
    assert.match(
      source,
      /'summary'\s*=>\s*new external_value\(PARAM_RAW,/,
      `${operation} must preserve HTML summary input before Moodle stores it.`
    );
  }
});

test('gradebook and course completion operations expose the global configuration contract', async () => {
  const contract = await loadContract();
  const operations = Object.fromEntries(contract.operations.map((operation) => [operation.name, operation]));

  assert.equal(operations.update_grade_item.parameters.weight.type, 'number');
  assert.equal(operations.update_grade_category.parameters.exclude_empty_grades.type, 'boolean');
  assert.equal(operations.get_grade_items.returns.items[0].item_type, 'string');
  assert.equal(operations.get_grade_items.returns.items[0].grade_pass, 'number');
  assert.equal(operations.get_grade_items.returns.items[0].weight, 'number');
  assert.equal(operations.set_course_grade_pass.type, 'write');
  assert.equal(operations.get_course_completion_criteria.type, 'read');
  assert.equal(operations.set_course_completion_criteria.parameters.required_module_ids.type, 'array');
});
