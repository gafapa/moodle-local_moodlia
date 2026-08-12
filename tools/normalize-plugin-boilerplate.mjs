import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const pluginRoot = projectRoot;
const checkOnly = process.argv.includes('--check');
const copyrightLine = ' * @copyright  2026 Pablo Gallego';
const incompleteLicenseHeader = [
  '<?php',
  '// This file is part of Moodle - https://moodle.org/',
  '//',
  '// Moodle is free software: you can redistribute it and/or modify',
  '// it under the terms of the GNU General Public License as published by',
  '// the Free Software Foundation, either version 3 of the License, or',
  '// (at your option) any later version.',
  ''
].join('\n');
const completeLicenseHeader = [
  '<?php',
  '// This file is part of Moodle - http://moodle.org/',
  '//',
  '// Moodle is free software: you can redistribute it and/or modify',
  '// it under the terms of the GNU General Public License as published by',
  '// the Free Software Foundation, either version 3 of the License, or',
  '// (at your option) any later version.',
  '//',
  '// Moodle is distributed in the hope that it will be useful,',
  '// but WITHOUT ANY WARRANTY; without even the implied warranty of',
  '// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the',
  '// GNU General Public License for more details.',
  '//',
  '// You should have received a copy of the GNU General Public License',
  '// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.',
  ''
].join('\n');
const misplacedCompleteLicenseHeader = completeLicenseHeader.replace('<?php\n', '<?php\n\n');
const genericDocblock = [
  '/**',
  ' * MoodlIA plugin implementation.',
  ' *',
  ' * @package    local_moodlia',
  copyrightLine,
  ' * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later',
  ' */',
  ''
].join('\n');
const legacyFullHeader = incompleteLicenseHeader + genericDocblock;
const orphanedHeaderPrefix =
  `${legacyFullHeader}//\n// Moodle is free software: you can redistribute it and/or modify`;

const phpFiles = collectPhpFiles(pluginRoot);
const staleFiles = [];

for (const filePath of phpFiles) {
  const original = fs.readFileSync(filePath, 'utf8');
  const lineEnding = original.includes('\r\n') ? '\r\n' : '\n';
  let normalized = original.replaceAll('\r\n', '\n');

  if (normalized.startsWith(orphanedHeaderPrefix)) {
    normalized = incompleteLicenseHeader + normalized.slice(legacyFullHeader.length);
  }

  if (normalized.startsWith(incompleteLicenseHeader)) {
    normalized = completeLicenseHeader + normalized.slice(incompleteLicenseHeader.length);
  }

  if (normalized.startsWith(misplacedCompleteLicenseHeader)) {
    normalized =
      completeLicenseHeader +
      normalized.slice(misplacedCompleteLicenseHeader.length);
  }

  if (
    normalized.startsWith(completeLicenseHeader) &&
    !normalized.includes('@package    local_moodlia')
  ) {
    normalized =
      completeLicenseHeader +
      genericDocblock +
      normalized.slice(completeLicenseHeader.length);
  } else if (
    normalized.startsWith('<?php\n') &&
    !normalized.includes('@package    local_moodlia')
  ) {
    normalized = completeLicenseHeader + genericDocblock + normalized.slice('<?php\n'.length);
  }

  normalized = normalized.replace(
    /^ \* @copyright\s+2026(?:\s+Pablo Gallego)?\s*$/m,
    copyrightLine
  );

  if (
    countOccurrences(normalized, '<?php') !== 1 ||
    countOccurrences(normalized, '@package    local_moodlia') !== 1 ||
    countOccurrences(normalized, copyrightLine) !== 1 ||
    countOccurrences(
      normalized,
      '@license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later'
    ) !== 1
  ) {
    throw new Error(`Incomplete Moodle boilerplate: ${path.relative(projectRoot, filePath)}`);
  }

  const output = normalized.replaceAll('\n', lineEnding);
  if (output === original) {
    continue;
  }

  staleFiles.push(path.relative(projectRoot, filePath));
  if (!checkOnly) {
    fs.writeFileSync(filePath, output);
  }
}

if (checkOnly && staleFiles.length > 0) {
  for (const filePath of staleFiles) {
    console.error(`Stale Moodle boilerplate: ${filePath}`);
  }
  console.error('Run npm run plugin:boilerplate to normalize plugin PHP headers.');
  process.exit(1);
}

if (checkOnly) {
  console.log(`Moodle PHP boilerplates are current (${phpFiles.length} files).`);
} else {
  console.log(`Normalized ${staleFiles.length} of ${phpFiles.length} Moodle PHP boilerplates.`);
}

function collectPhpFiles(directoryPath) {
  const files = [];
  for (const entry of fs.readdirSync(directoryPath, { withFileTypes: true })) {
    const entryPath = path.join(directoryPath, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectPhpFiles(entryPath));
    } else if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(entryPath);
    }
  }
  return files.sort((firstPath, secondPath) => firstPath.localeCompare(secondPath, 'en'));
}

function countOccurrences(source, searchValue) {
  return source.split(searchValue).length - 1;
}
