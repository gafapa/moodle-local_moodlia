import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDirectory = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const checkOnly = process.argv.includes('--check');
const versionSource = await fs.readFile(path.join(rootDirectory, 'version.php'), 'utf8');
const release = versionSource.match(/\$plugin->release\s*=\s*'([^']+)'/)?.[1];

if (!release) {
  throw new Error('Unable to read the MoodlIA plugin release from version.php.');
}

const artifactPaths = [
  path.join(rootDirectory, 'empaquetado', `local_moodlia-${release}.zip`),
  ...await zipFiles(path.join(rootDirectory, 'dist', 'downloads'))
];
const lines = [];

for (const artifactPath of artifactPaths.sort()) {
  const content = await fs.readFile(artifactPath);
  const digest = crypto.createHash('sha256').update(content).digest('hex');
  const relativePath = path.relative(rootDirectory, artifactPath).replaceAll(path.sep, '/');
  lines.push(`${digest}  ${relativePath}`);
}

const output = `${lines.join('\n')}\n`;
const targetPath = path.join(rootDirectory, 'empaquetado', 'SHA256SUMS.txt');

if (checkOnly) {
  const current = await fs.readFile(targetPath, 'utf8').catch(() => '');
  if (current !== output) {
    throw new Error('Release checksums are stale. Run npm run release:checksums.');
  }
  console.log(`Release checksums are current (${artifactPaths.length} artifacts).`);
} else {
  await fs.writeFile(targetPath, output, 'utf8');
  console.log(`Generated ${path.relative(rootDirectory, targetPath)} for ${artifactPaths.length} artifacts.`);
}

async function zipFiles(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true }).catch((error) => {
    if (error.code === 'ENOENT') {
      return [];
    }
    throw error;
  });
  return entries
    .filter((entry) => entry.isFile() && entry.name.endsWith('.zip'))
    .map((entry) => path.join(directory, entry.name));
}
