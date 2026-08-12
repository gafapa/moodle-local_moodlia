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
