import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { unzipSync } from 'fflate';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const versionSource = fs.readFileSync(path.join(projectRoot, 'version.php'), 'utf8');
const release = versionSource.match(/\$plugin->release\s*=\s*'([^']+)'/)?.[1];

if (!release) {
  throw new Error('Unable to read the plugin release from version.php.');
}

const archivePath = path.join(projectRoot, 'empaquetado', `local_moodlia-${release}.zip`);
if (!fs.statSync(archivePath, { throwIfNoEntry: false })?.isFile()) {
  throw new Error(`Marketplace archive not found: ${path.relative(projectRoot, archivePath)}`);
}

const entries = unzipSync(fs.readFileSync(archivePath));
const entryNames = Object.keys(entries);
const requiredEntries = [
  'moodlia/CHANGES.md',
  'moodlia/LICENSE',
  'moodlia/README.md',
  'moodlia/SECURITY.md',
  'moodlia/version.php',
  'moodlia/mcp.php',
  'moodlia/db/access.php',
  'moodlia/db/services.php',
  'moodlia/lang/en/local_moodlia.php',
  'moodlia/classes/privacy/provider.php',
  'moodlia/pix/icon.svg'
];

for (const entry of requiredEntries) {
  if (!entryNames.includes(entry)) {
    throw new Error(`Marketplace archive is missing ${entry}.`);
  }
}

const forbiddenSegments = [
  '/.env',
  '/.git',
  '/.github',
  '/automation/',
  '/contract/',
  '/docs/',
  '/node_modules/',
  '/package.json',
  '/package-lock.json',
  '/tools/'
];

for (const entry of entryNames) {
  if (!entry.startsWith('moodlia/')) {
    throw new Error(`Archive entry is outside the moodlia root: ${entry}`);
  }
  if (forbiddenSegments.some((segment) => entry.includes(segment))) {
    throw new Error(`Development-only file leaked into the Marketplace archive: ${entry}`);
  }
  if (entry.startsWith('moodlia/tests/') && !entry.endsWith('.php')) {
    throw new Error(`Non-PHP development test leaked into the Marketplace archive: ${entry}`);
  }
}

const archivedVersion = Buffer.from(entries['moodlia/version.php']).toString('utf8');
if (!archivedVersion.includes(`$plugin->release = '${release}';`)) {
  throw new Error('The archived version.php does not match the release filename.');
}

console.log(
  `Marketplace archive is valid (${path.basename(archivePath)}, ${entryNames.length} files).`
);
