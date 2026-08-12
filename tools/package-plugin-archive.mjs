import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { unzipSync } from 'fflate';
import { createZipFromDirectory } from './lib/reproducible-zip.mjs';
import { copyPluginPackage } from './lib/plugin-package.mjs';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const pluginRoot = projectRoot;
const outputRoot = path.join(projectRoot, 'empaquetado');
const versionSource = await fs.readFile(path.join(pluginRoot, 'version.php'), 'utf8');
const release = versionSource.match(/\$plugin->release\s*=\s*'([^']+)'/)?.[1];

if (!release) {
  throw new Error('Unable to read the MoodlIA plugin release from version.php.');
}

await fs.mkdir(outputRoot, { recursive: true });
const outputName = `local_moodlia-${release}.zip`;
const outputPath = path.join(outputRoot, outputName);
const stagingRoot = await fs.mkdtemp(path.join(os.tmpdir(), 'local-moodlia-archive-'));
const stagingPluginRoot = path.join(stagingRoot, 'moodlia');
await copyPluginPackage(pluginRoot, stagingPluginRoot);
const archive = createZipFromDirectory({ sourceDirectory: stagingPluginRoot, archiveRoot: 'moodlia' });
await fs.rm(stagingRoot, { recursive: true, force: true });
const archiveEntries = Object.keys(unzipSync(archive));
for (const requiredEntry of [
  'moodlia/LICENSE',
  'moodlia/README.md',
  'moodlia/version.php',
  'moodlia/lang/en/local_moodlia.php'
]) {
  if (!archiveEntries.includes(requiredEntry)) {
    throw new Error(`Plugin archive is missing ${requiredEntry}.`);
  }
}
if (archiveEntries.some((entryName) => !entryName.startsWith('moodlia/'))) {
  throw new Error('Plugin archive contains a file outside the moodlia root directory.');
}

for (const entry of await fs.readdir(outputRoot, { withFileTypes: true })) {
  if (
    entry.isFile() &&
    /^local_moodlia-[\w.-]+\.zip$/.test(entry.name) &&
    entry.name !== outputName
  ) {
    await fs.unlink(path.join(outputRoot, entry.name));
    console.log(`Removed obsolete plugin archive ${entry.name}.`);
  }
}

await fs.writeFile(outputPath, archive);
console.log(
  `Packaged ${path.relative(projectRoot, outputPath)} ` +
  `(${archive.length} bytes, ${archiveEntries.length} files).`
);
