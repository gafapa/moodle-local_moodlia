import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const checkOnly = process.argv.includes('--check');
const phpFiles = collectPhpFiles(projectRoot);
const staleFiles = [];

for (const filePath of phpFiles) {
  const original = fs.readFileSync(filePath, 'utf8');
  const lineEnding = original.includes('\r\n') ? '\r\n' : '\n';
  let source = original.replaceAll('\r\n', '\n');

  if (/^namespace\s+[^;]+;/m.test(source)) {
    source = source.replace(/\ndefined\('MOODLE_INTERNAL'\) \|\| die\(\);\n/, '\n');
    source = source.replace(/^(namespace\s+[^;]+;)\n{3,}/m, '$1\n\n');
  }

  source = source.replaceAll('} elseif (', '} else if (');
  source = source.replace(/\bfunction\(/g, 'function (');
  source = source.replace(/\$([a-z][A-Za-z0-9]*_[A-Za-z0-9_]*)/g, (match, name) => {
    return `$${name.replaceAll('_', '')}`;
  });
  source = addMissingDocblocks(source, /^([ \t]*)(?:(?:final|abstract|readonly)\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)\b/gm, classDocblock);
  source = addMissingDocblocks(
    source,
    /^([ \t]*)(?:(?:public|protected|private)\s+)?(?:(?:static|final|abstract)\s+)*function\s+&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(/gm,
    functionDocblock
  );
  source = addMissingDocblocks(
    source,
    /^([ \t]*)(?:(?:public|protected|private)\s+)?const\s+([A-Za-z_][A-Za-z0-9_]*)\b/gm,
    constantDocblock
  );
  source = normalizeFunctionParameterDocs(source);

  const output = source.replaceAll('\n', lineEnding);
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
    console.error(`Stale PHP documentation: ${filePath}`);
  }
  console.error('Run npm run plugin:phpdoc to normalize PHP documentation.');
  process.exit(1);
}

const action = checkOnly ? 'Checked' : 'Normalized';
console.log(`${action} PHP documentation in ${phpFiles.length} files (${staleFiles.length} changed).`);

function addMissingDocblocks(source, pattern, buildDocblock) {
  const insertions = [];
  for (const match of source.matchAll(pattern)) {
    const declarationIndex = match.index + match[1].length;
    if (hasDocblockBefore(source, match.index)) {
      continue;
    }
    insertions.push({
      index: match.index,
      text: buildDocblock(source, match, declarationIndex)
    });
  }

  for (const insertion of insertions.reverse()) {
    source = source.slice(0, insertion.index) + insertion.text + source.slice(insertion.index);
  }
  return source;
}

function hasDocblockBefore(source, index) {
  return source.slice(0, index).trimEnd().endsWith('*/');
}

function classDocblock(source, match) {
  const indent = match[1];
  const name = match[2];
  return [
    `${indent}/**`,
    `${indent} * ${sentenceFromIdentifier(name)} implementation.`,
    `${indent} */`,
    ''
  ].join('\n');
}

function functionDocblock(source, match, declarationIndex) {
  const indent = match[1];
  const name = match[2];
  const openParenthesis = source.indexOf('(', declarationIndex);
  const closeParenthesis = findMatchingParenthesis(source, openParenthesis);
  const parameters = splitParameters(source.slice(openParenthesis + 1, closeParenthesis));
  const returnType = readReturnType(source, closeParenthesis + 1);
  const summary = name === '__construct'
    ? 'Create the object.'
    : name === 'execute'
      ? 'Execute the operation.'
      : `${sentenceFromIdentifier(name)}.`;
  const lines = [
    `${indent}/**`,
    `${indent} * ${summary}`
  ];

  if (parameters.length > 0 || (returnType && !['__construct', '__destruct'].includes(name))) {
    lines.push(`${indent} *`);
  }
  for (const parameter of parameters) {
    lines.push(`${indent} * @param ${parameter.type} $${parameter.name} ${sentenceFromIdentifier(parameter.name)}.`);
  }
  if (returnType && !['__construct', '__destruct'].includes(name)) {
    lines.push(`${indent} * @return ${returnType}`);
  }
  lines.push(`${indent} */`, '');
  return lines.join('\n');
}

function constantDocblock(source, match) {
  const indent = match[1];
  return [
    `${indent}/**`,
    `${indent} * ${sentenceFromIdentifier(match[2])}.`,
    `${indent} */`,
    ''
  ].join('\n');
}

function normalizeFunctionParameterDocs(source) {
  const updates = [];
  const pattern = /^([ \t]*)(?:(?:public|protected|private)\s+)?(?:(?:static|final|abstract)\s+)*function\s+&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(/gm;
  for (const match of source.matchAll(pattern)) {
    const declarationIndex = match.index + match[1].length;
    const openParenthesis = source.indexOf('(', declarationIndex);
    const closeParenthesis = findMatchingParenthesis(source, openParenthesis);
    const parameters = splitParameters(source.slice(openParenthesis + 1, closeParenthesis));
    const prefix = source.slice(0, match.index).trimEnd();
    if (!prefix.endsWith('*/')) {
      continue;
    }
    const docblockEnd = prefix.length;
    const docblockStart = prefix.lastIndexOf('/**');
    if (docblockStart < 0) {
      continue;
    }
    const indent = match[1];
    let docblock = source.slice(docblockStart, docblockEnd);
    docblock = docblock.replace(/^[ \t]*\*[ \t]+@param\b[^\n]*\n/gm, '');
    if (parameters.length > 0) {
      const returnMarker = `\n${indent} * @return`;
      const closeMarker = `\n${indent} */`;
      let insertionIndex = docblock.indexOf(returnMarker);
      if (insertionIndex < 0) {
        insertionIndex = docblock.lastIndexOf(closeMarker);
      }
      const parameterLines = parameters.map((parameter) => {
        return `${indent} * @param ${parameter.type} $${parameter.name} ${sentenceFromIdentifier(parameter.name)}.`;
      }).join('\n');
      docblock = docblock.slice(0, insertionIndex) + `\n${parameterLines}` + docblock.slice(insertionIndex);
    }
    updates.push({ start: docblockStart, end: docblockEnd, text: docblock });
  }

  for (const update of updates.reverse()) {
    source = source.slice(0, update.start) + update.text + source.slice(update.end);
  }
  return source;
}

function findMatchingParenthesis(source, openIndex) {
  let depth = 0;
  let quote = '';
  let escaped = false;
  for (let index = openIndex; index < source.length; index += 1) {
    const character = source[index];
    if (quote) {
      if (escaped) {
        escaped = false;
      } else if (character === '\\') {
        escaped = true;
      } else if (character === quote) {
        quote = '';
      }
      continue;
    }
    if (character === "'" || character === '"') {
      quote = character;
    } else if (character === '(') {
      depth += 1;
    } else if (character === ')') {
      depth -= 1;
      if (depth === 0) {
        return index;
      }
    }
  }
  throw new Error('Unbalanced PHP function declaration.');
}

function splitParameters(source) {
  const parts = [];
  let start = 0;
  let depth = 0;
  let quote = '';
  let escaped = false;
  for (let index = 0; index <= source.length; index += 1) {
    const character = source[index] ?? ',';
    if (quote) {
      if (escaped) {
        escaped = false;
      } else if (character === '\\') {
        escaped = true;
      } else if (character === quote) {
        quote = '';
      }
      continue;
    }
    if (character === "'" || character === '"') {
      quote = character;
    } else if ('([{'.includes(character)) {
      depth += 1;
    } else if (')]}'.includes(character)) {
      depth -= 1;
    } else if (character === ',' && depth === 0) {
      const parameter = parseParameter(source.slice(start, index));
      if (parameter) {
        parts.push(parameter);
      }
      start = index + 1;
    }
  }
  return parts;
}

function parseParameter(source) {
  const declaration = source.split('=')[0].trim();
  const match = declaration.match(/^(.*?)\s*(?:&\s*)?(?:\.\.\.\s*)?\$([A-Za-z_][A-Za-z0-9_]*)$/s);
  if (!match) {
    return null;
  }
  let type = match[1].trim() || 'mixed';
  if (type.startsWith('?')) {
    type = `${type.slice(1)}|null`;
  }
  return { type, name: match[2] };
}

function readReturnType(source, index) {
  const match = source.slice(index).match(/^\s*:\s*([^\s{;]+)/);
  if (!match) {
    return 'mixed';
  }
  return match[1].startsWith('?') ? `${match[1].slice(1)}|null` : match[1];
}

function sentenceFromIdentifier(identifier) {
  const words = identifier
    .replace(/^_+/, '')
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .replaceAll('_', ' ')
    .trim();
  return words ? words[0].toUpperCase() + words.slice(1) : 'Value';
}

function collectPhpFiles(directoryPath) {
  const files = [];
  for (const entry of fs.readdirSync(directoryPath, { withFileTypes: true })) {
    if (entry.isDirectory() && ['.git', 'node_modules', 'empaquetado', 'marketplace'].includes(entry.name)) {
      continue;
    }
    const entryPath = path.join(directoryPath, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectPhpFiles(entryPath));
    } else if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(entryPath);
    }
  }
  return files.sort((firstPath, secondPath) => firstPath.localeCompare(secondPath, 'en'));
}
