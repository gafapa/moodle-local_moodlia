import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDirectory = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

function toKebabCase(value) {
  return value.replaceAll('_', '-');
}

function toRestFunctionName(contract, operationName) {
  return `${contract.restPrefix}_${operationName}`;
}

function stableJson(value) {
  return `${JSON.stringify(value, null, 2)}\n`;
}

export function buildManifests(contract) {
  const byTransport = (transport) => contract.operations.filter((operation) => operation.transports.includes(transport));
  const mcpToolSchemas = {};

  for (const operation of byTransport('mcp')) {
    const enums = {};
    for (const [parameterName, definition] of Object.entries(operation.parameters)) {
      if (Array.isArray(definition.enum)) {
        enums[parameterName] = definition.enum;
      }
    }

    if (Object.keys(enums).length > 0) {
      mcpToolSchemas[operation.name] = enums;
    }
  }

  return {
    'automation/manifests/rest-functions.json': {
      functions: byTransport('rest').map((operation) => toRestFunctionName(contract, operation.name))
    },
    'automation/manifests/mcp-tools.json': {
      tools: byTransport('mcp').map((operation) => operation.name)
    },
    'automation/manifests/cli-commands.json': {
      commands: byTransport('cli').map((operation) => toKebabCase(operation.name))
    },
    'automation/manifests/mcp-tool-schemas.json': {
      tools: mcpToolSchemas
    }
  };
}

async function readJson(relativePath) {
  const raw = await fs.readFile(path.join(rootDirectory, relativePath), 'utf8');
  return JSON.parse(raw.replace(/^\uFEFF/, ''));
}

async function main() {
  const check = process.argv.includes('--check');
  const contract = await readJson('contract/operations.json');
  const manifests = buildManifests(contract);
  const stale = [];

  for (const [relativePath, manifest] of Object.entries(manifests)) {
    const target = path.join(rootDirectory, relativePath);
    const expected = stableJson(manifest);

    if (check) {
      const actual = await fs.readFile(target, 'utf8');
      if (actual.replace(/\r\n/g, '\n') !== expected) {
        stale.push(relativePath);
      }
      continue;
    }

    await fs.mkdir(path.dirname(target), { recursive: true });
    await fs.writeFile(target, expected);
  }

  if (stale.length > 0) {
    throw new Error(`Generated manifest files are stale: ${stale.join(', ')}`);
  }

  if (!check) {
    console.log(`Generated ${Object.keys(manifests).length} manifest files.`);
  }
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  main().catch((error) => {
    console.error(error.message);
    process.exitCode = 1;
  });
}
