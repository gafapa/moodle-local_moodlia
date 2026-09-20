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
    ['MOODLE_405_STABLE', '8.1', 'mariadb'],
    ['MOODLE_405_STABLE', '8.3', 'pgsql'],
    ['MOODLE_500_STABLE', '8.2', 'mariadb'],
    ['MOODLE_500_STABLE', '8.4', 'pgsql'],
    ['MOODLE_501_STABLE', '8.2', 'mariadb'],
    ['MOODLE_501_STABLE', '8.4', 'pgsql'],
    ['MOODLE_502_STABLE', '8.3', 'mariadb'],
    ['MOODLE_502_STABLE', '8.4', 'pgsql'],
    ['v5.3.0-beta', '8.3', 'mariadb'],
    ['v5.3.0-beta', '8.4', 'pgsql']
  ];

  for (const [moodleBranch, phpVersion, database] of expectedProfiles) {
    const profile = [
      `- moodle: ${moodleBranch}`,
      `php: '${phpVersion}'`,
      `database: ${database}`
    ].join('\\s+');

    assert.match(workflowSource, new RegExp(profile));
  }

  assert.match(workflowSource, /MOODLE_BRANCH:\s*\$\{\{ matrix\.moodle \}\}/);
});
