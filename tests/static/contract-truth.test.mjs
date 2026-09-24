import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import test from 'node:test';

import { loadContract } from '../helpers/contract.mjs';
import { fromRoot } from '../helpers/paths.mjs';

// Every operation's `tests` tags must be backed by evidence in this repository:
// - parity: the REST external declares exactly the contract parameters.
// - mcp: classes/mcp/manifest.php is generated from the contract (manifests:check).
// - cli: the moodlia CLI's generated suite drives every CLI operation.
// - api: a PHPUnit test in tests/ exercises the operation class.
// - browser: a browser test references the operation (none exist yet).

function externalParameterNames(source) {
  if (/function execute_parameters\(\)[^{]*\{\s*return new external_function_parameters\(\[\]\);/.test(source)) return [];
  const body = source.match(/function execute_parameters\(\)[\s\S]*?new external_function_parameters\(\[([\s\S]*?)\n        \]\);/);
  if (!body) return null;
  // Only top-level keys: lines indented exactly 12 spaces.
  return [...body[1].matchAll(/^ {12}'([a-z0-9_]+)'\s*=>/gm)].map((match) => match[1]);
}

async function phpunitSources() {
  const files = (await fs.readdir(fromRoot('tests'))).filter((name) => name.endsWith('_test.php'));
  return (await Promise.all(files.map((name) => fs.readFile(fromRoot(`tests/${name}`), 'utf8')))).join('\n');
}

test('parity: every REST external declares exactly the contract parameters', async () => {
  const contract = await loadContract();
  const mismatches = [];
  for (const operation of contract.operations.filter((entry) => entry.tests.includes('parity'))) {
    const source = await fs.readFile(fromRoot(`classes/external/${operation.name}.php`), 'utf8');
    const declared = externalParameterNames(source);
    const expected = Object.keys(operation.parameters ?? {});
    if (declared === null || JSON.stringify([...declared].sort()) !== JSON.stringify([...expected].sort())) {
      mismatches.push({ operation: operation.name, external: declared, contract: expected });
    }
  }
  assert.deepEqual(mismatches, []);
});

test('api: every operation tagged api is exercised by a PHPUnit test', async () => {
  const contract = await loadContract();
  const sources = await phpunitSources();
  const untested = contract.operations
    .filter((operation) => operation.tests.includes('api'))
    .filter((operation) => !new RegExp(`\\b${operation.name}\\b`).test(sources))
    .map((operation) => operation.name);
  assert.deepEqual(untested, [], 'Add a PHPUnit test or remove the api tag from these operations.');
});

test('browser: no operation claims browser coverage without a browser test', async () => {
  const contract = await loadContract();
  const claimed = contract.operations.filter((operation) => operation.tests.includes('browser')).map((operation) => operation.name);
  const browserTests = (await fs.readdir(fromRoot('tests'))).filter((name) => /browser|behat|playwright/i.test(name));
  if (browserTests.length === 0) {
    assert.deepEqual(claimed, [], 'No browser tests exist; remove the browser tag.');
  }
});

test('every operation declares the mcp, cli, and parity tags it satisfies', async () => {
  const contract = await loadContract();
  for (const operation of contract.operations) {
    for (const transport of ['mcp', 'cli']) {
      assert.equal(
        operation.tests.includes(transport),
        operation.transports.includes(transport),
        `${operation.name} ${transport} tag must follow its transports`
      );
    }
    assert.ok(operation.tests.includes('parity'), `${operation.name} must keep REST parity`);
  }
});
