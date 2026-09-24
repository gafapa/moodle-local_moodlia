import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Regenerates the MCP tool list in classes/mcp/manifest.php from the canonical
// contract so tool names, descriptions, and argument schemas cannot drift.
// usage: node tools/generate-mcp-manifest.mjs [--check]

const rootDirectory = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = path.join(rootDirectory, 'classes', 'mcp', 'manifest.php');
const maximumLineLength = 120;

function phpString(value) {
  return `'${String(value).replaceAll('\\', '\\\\').replaceAll("'", "\\'")}'`;
}

function phpList(values, indent) {
  const inline = `[${values.map(phpString).join(', ')}]`;
  if (indent.length + inline.length < maximumLineLength - 40) return inline;
  return `[\n${values.map((value) => `${indent}    ${phpString(value)},`).join('\n')}\n${indent}]`;
}

function parameterSchema(name, definition, indent) {
  const pairs = [`'type' => ${phpString(definition.type)}`];
  if (definition.items !== undefined) {
    pairs.push(`'items' => ['type' => ${phpString(definition.items)}]`);
  }
  pairs.push(`'required' => ${definition.required ? 'true' : 'false'}`);
  const hasEnum = Array.isArray(definition.enum) && definition.enum.length > 0;
  const inlineEnum = hasEnum ? `, 'enum' => [${definition.enum.map(phpString).join(', ')}]` : '';
  const inline = `${indent}${phpString(name)} => [${pairs.join(', ')}${inlineEnum}],`;
  if (inline.length <= maximumLineLength) return inline;
  const inner = `${indent}    `;
  const lines = pairs.map((pair) => `${inner}${pair},`);
  if (hasEnum) lines.push(`${inner}'enum' => ${phpList(definition.enum, inner)},`);
  return `${indent}${phpString(name)} => [\n${lines.join('\n')}\n${indent}],`;
}

export function renderTools(contract) {
  const tools = contract.operations.filter((operation) => operation.transports.includes('mcp'));
  const entries = tools.map((operation) => {
    const parameters = Object.entries(operation.parameters ?? {});
    const schema = parameters.length === 0
      ? 'self::schema([])'
      : `self::schema([\n${parameters.map(([name, definition]) => parameterSchema(name, definition, '                    ')).join('\n')}\n                ])`;
    return [
      '            [',
      `                'name' => ${phpString(operation.name)},`,
      `                'description' => ${phpString(operation.summary)},`,
      `                'inputSchema' => ${schema},`,
      '            ],'
    ].join('\n');
  });
  return `        return [\n${entries.join('\n')}\n        ];`;
}

export function replaceTools(source, contract) {
  const start = source.indexOf('    public static function tools(): array {\n');
  if (start < 0) throw new Error('manifest.php has no tools() method.');
  const bodyStart = source.indexOf('        return [\n', start);
  const bodyEnd = source.indexOf('\n        ];\n    }\n', bodyStart);
  if (bodyStart < 0 || bodyEnd < 0) throw new Error('manifest.php tools() has an unexpected shape.');
  return `${source.slice(0, bodyStart)}${renderTools(contract)}${source.slice(bodyEnd + '\n        ];'.length)}`;
}

async function main() {
  const check = process.argv.includes('--check');
  const contract = JSON.parse((await fs.readFile(path.join(rootDirectory, 'contract', 'operations.json'), 'utf8')).replace(/^﻿/, ''));
  const current = await fs.readFile(manifestPath, 'utf8');
  const generated = replaceTools(current, contract);
  if (check) {
    if (generated !== current) {
      console.error('classes/mcp/manifest.php is stale; run node tools/generate-mcp-manifest.mjs.');
      process.exitCode = 1;
      return;
    }
    console.log('MCP manifest matches the contract.');
    return;
  }
  await fs.writeFile(manifestPath, generated);
  console.log(`Generated ${contract.operations.filter((operation) => operation.transports.includes('mcp')).length} MCP tools.`);
}

if (process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1])) {
  await main();
}
