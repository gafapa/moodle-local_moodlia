import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { unzipSync } from 'fflate';

import { fromRoot } from '../helpers/paths.mjs';
import { createZipFromDirectory } from '../../tools/lib/reproducible-zip.mjs';
import { copyPluginPackage } from '../../tools/lib/plugin-package.mjs';

test('Marketplace package contains only the installable Moodle plugin', async () => {
  const stagingRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'moodlia-package-test-'));
  const pluginRoot = path.join(stagingRoot, 'moodlia');

  try {
    await copyPluginPackage(fromRoot(), pluginRoot);

    for (const requiredPath of [
      'version.php',
      'mcp.php',
      'db/services.php',
      'classes/privacy/provider.php',
      'tests/plugin_management_test.php'
    ]) {
      await fs.access(path.join(pluginRoot, requiredPath));
    }

    for (const forbiddenPath of [
      '.env.example',
      '.github',
      'automation',
      'contract',
      'docs',
      'marketplace',
      'node_modules',
      'package.json',
      'tools',
      'tests/static'
    ]) {
      await assert.rejects(fs.access(path.join(pluginRoot, forbiddenPath)));
    }

    const firstArchive = createZipFromDirectory({
      sourceDirectory: pluginRoot,
      archiveRoot: 'moodlia'
    });
    const secondArchive = createZipFromDirectory({
      sourceDirectory: pluginRoot,
      archiveRoot: 'moodlia'
    });

    assert.deepEqual(firstArchive, secondArchive);
    assert.ok(Object.keys(unzipSync(firstArchive)).every((entry) => entry.startsWith('moodlia/')));
  } finally {
    await fs.rm(stagingRoot, { recursive: true, force: true });
  }
});
