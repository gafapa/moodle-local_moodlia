import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import test from 'node:test';

import { fromRoot } from '../helpers/paths.mjs';

test('plugin metadata supports Moodle 4.5 through 5.3', async () => {
  const versionSource = await fs.readFile(fromRoot('version.php'), 'utf8');

  assert.match(versionSource, /\$plugin->requires\s*=\s*2024100700;/);
  assert.match(versionSource, /\$plugin->supported\s*=\s*\[405, 503\];/);
});

test('Moodle CI covers every supported core branch and PHP boundary', async () => {
  const workflowSource = await fs.readFile(
    fromRoot('.github', 'workflows', 'moodle-ci.yml'),
    'utf8'
  );

  const expectedProfiles = [
    ['MOODLE_405_STABLE', '8.1', 'mariadb', '16'],
    ['MOODLE_405_STABLE', '8.3', 'pgsql', '16'],
    ['MOODLE_500_STABLE', '8.2', 'mariadb', '16'],
    ['MOODLE_500_STABLE', '8.4', 'pgsql', '16'],
    ['MOODLE_501_STABLE', '8.2', 'mariadb', '16'],
    ['MOODLE_501_STABLE', '8.4', 'pgsql', '16'],
    ['MOODLE_502_STABLE', '8.3', 'mariadb', '16'],
    ['MOODLE_502_STABLE', '8.4', 'pgsql', '16'],
    ['v5.3.0-beta', '8.3', 'mariadb', '17'],
    ['v5.3.0-beta', '8.4', 'pgsql', '17']
  ];

  const matrixJson = (name) => {
    const match = workflowSource.match(new RegExp(`${name}='(\\[[\\s\\S]*?\\])'`));
    assert.ok(match, `moodle-ci.yml must define the ${name} matrix`);
    return JSON.parse(match[1]).map((entry) => [entry.moodle, entry.php, entry.database, entry.postgres]);
  };

  // The full matrix runs on main, nightly, and on demand; pull requests run the reduced one.
  assert.deepEqual(matrixJson('full'), expectedProfiles);
  assert.deepEqual(matrixJson('reduced'), [expectedProfiles[0], expectedProfiles[9]]);
  assert.match(workflowSource, /schedule:\s*\n\s*#[^\n]*\n\s*- cron:/);
  assert.match(workflowSource, /github\.event_name \}\}" == "pull_request" \]\]; then selected="\$reduced"/);
  assert.match(workflowSource, /include: \$\{\{ fromJSON\(needs\.plan\.outputs\.matrix\) \}\}/);

  assert.match(workflowSource, /image:\s*postgres:\$\{\{ matrix\.postgres \}\}/);
  assert.match(workflowSource, /MOODLE_BRANCH:\s*\$\{\{ matrix\.moodle \}\}/);
});
