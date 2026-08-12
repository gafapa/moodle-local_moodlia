import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const pluginRoot = projectRoot;
const requiredFiles = [
  'CHANGES.md',
  'LICENSE',
  'README.md',
  'SECURITY.md',
  'version.php',
  'lang/en/local_moodlia.php',
  'pix/icon.svg'
];
const requiredReadmeLinks = [
  'https://github.com/gafapa/moodle-local_moodlia',
  'https://github.com/gafapa/moodle-local_moodlia/issues'
];
const phpFiles = collectFiles(pluginRoot).filter((filePath) => filePath.endsWith('.php'));

for (const relativePath of requiredFiles) {
  const filePath = path.join(pluginRoot, relativePath);
  if (!fs.statSync(filePath, { throwIfNoEntry: false })?.isFile()) {
    throw new Error(`Required Marketplace file is missing: ${relativePath}`);
  }
}

const versionSource = fs.readFileSync(path.join(pluginRoot, 'version.php'), 'utf8');
if (!versionSource.includes("$plugin->component = 'local_moodlia';")) {
  throw new Error('version.php must declare local_moodlia.');
}
if (!/\$plugin->requires\s*=\s*2026042000\s*;/.test(versionSource)) {
  throw new Error('The Marketplace package must declare Moodle 5.2 as its minimum version.');
}
if (!/\$plugin->supported\s*=\s*\[502,\s*502\]\s*;/.test(versionSource)) {
  throw new Error('The Marketplace package must declare Moodle 5.2 compatibility explicitly.');
}
if (!/\$plugin->maturity\s*=\s*MATURITY_(?:BETA|STABLE)\s*;/.test(versionSource)) {
  throw new Error('The Marketplace package must declare beta or stable maturity.');
}

const languageSource = fs.readFileSync(path.join(pluginRoot, 'lang', 'en', 'local_moodlia.php'), 'utf8');
if (!languageSource.includes("$string['pluginname'] = 'MoodlIA';")) {
  throw new Error('The English language pack must declare the MoodlIA plugin name.');
}

const readme = fs.readFileSync(path.join(pluginRoot, 'README.md'), 'utf8');
for (const requiredLink of requiredReadmeLinks) {
  if (!readme.includes(requiredLink)) {
    throw new Error(`Plugin README must publish this Marketplace link: ${requiredLink}`);
  }
}
for (const requiredHeading of ['## Requirements', '## Privacy', '## Security', '## Support', '## License']) {
  if (!readme.includes(requiredHeading)) {
    throw new Error(`Plugin README must contain ${requiredHeading}.`);
  }
}

const accessSource = fs.readFileSync(path.join(pluginRoot, 'db', 'access.php'), 'utf8');
if (!/['"]local\/moodlia:useapi['"][\s\S]*?['"]captype['"]\s*=>\s*['"]write['"]/.test(accessSource)) {
  throw new Error('The broad MoodlIA API capability must be declared as write-capable.');
}
for (const requiredRisk of ['RISK_CONFIG', 'RISK_DATALOSS', 'RISK_PERSONAL', 'RISK_SPAM']) {
  if (!accessSource.includes(requiredRisk)) {
    throw new Error(`The MoodlIA API capability must declare ${requiredRisk}.`);
  }
}
if (!/['"]local\/moodlia:useapi['"][\s\S]*?['"]archetypes['"]\s*=>\s*\[\s*\]/.test(accessSource)) {
  throw new Error('The remote automation capability must not be granted to a role archetype by default.');
}

if (fs.existsSync(path.join(pluginRoot, 'plugin', 'moodlia'))) {
  throw new Error('The repository root must be the directly installable plugin root.');
}

for (const filePath of phpFiles) {
  const source = fs.readFileSync(filePath, 'utf8');
  const relativePath = path.relative(projectRoot, filePath);
  if (countOccurrences(source, '<?php') !== 1) {
    throw new Error(`PHP file must contain one opening tag: ${relativePath}`);
  }
  if (countOccurrences(source, '@package    local_moodlia') !== 1) {
    throw new Error(`Missing local_moodlia package declaration: ${relativePath}`);
  }
  if (countOccurrences(source, '@copyright  2026 Pablo Gallego') !== 1) {
    throw new Error(`Missing named copyright holder: ${relativePath}`);
  }
  if (
    countOccurrences(
      source,
      '@license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later'
    ) !== 1
  ) {
    throw new Error(`Missing Moodle GPL licence tag: ${relativePath}`);
  }
}

const installXmlPath = path.join(pluginRoot, 'db', 'install.xml');
if (fs.existsSync(installXmlPath)) {
  const installXml = fs.readFileSync(installXmlPath, 'utf8');
  for (const match of installXml.matchAll(/<TABLE\s+NAME="([^"]+)"/g)) {
    if (!match[1].startsWith('local_moodlia')) {
      throw new Error(`Database table must use the local_moodlia prefix: ${match[1]}`);
    }
  }
}

console.log(`Marketplace source package is valid (${phpFiles.length} PHP files).`);

function collectFiles(directoryPath) {
  const files = [];
  for (const entry of fs.readdirSync(directoryPath, { withFileTypes: true })) {
    const entryPath = path.join(directoryPath, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectFiles(entryPath));
    } else if (entry.isFile()) {
      files.push(entryPath);
    }
  }
  return files;
}

function countOccurrences(source, searchValue) {
  return source.split(searchValue).length - 1;
}
