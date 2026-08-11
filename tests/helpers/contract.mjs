import fs from 'node:fs/promises';
import assert from 'node:assert/strict';
import { fromRoot } from './paths.mjs';

const allowedTransportNames = new Set(['rest', 'mcp', 'cli']);
const allowedOperationTypes = new Set(['read', 'write']);
const allowedFileModes = new Set(['none', 'upload', 'download', 'metadata']);
const allowedCapabilityModes = new Set(['all', 'any']);

export function toKebabCase(value) {
  return value.replaceAll('_', '-');
}

export function toRestFunctionName(contract, operationName) {
  return `${contract.restPrefix}_${operationName}`;
}

export async function readJson(relativePath) {
  const raw = await fs.readFile(fromRoot(relativePath), 'utf8');
  return JSON.parse(raw.replace(/^\uFEFF/, ''));
}

export async function loadContract() {
  return readJson('contract/operations.json');
}

export function getOperationsByTransport(contract, transport) {
  return contract.operations.filter((operation) => operation.transports.includes(transport));
}

export function assertValidContract(contract) {
  assert.equal(typeof contract.version, 'string', 'Contract version is required.');
  assert.equal(typeof contract.component, 'string', 'Contract component is required.');
  assert.equal(typeof contract.restPrefix, 'string', 'REST prefix is required.');
  assert.ok(Array.isArray(contract.operations), 'Contract operations must be an array.');
  assert.ok(contract.operations.length > 0, 'At least one operation is required.');

  const names = new Set();

  for (const operation of contract.operations) {
    assert.equal(typeof operation.name, 'string', 'Operation name is required.');
    assert.match(operation.name, /^[a-z][a-z0-9_]*$/, `Invalid operation name: ${operation.name}`);
    assert.ok(!names.has(operation.name), `Duplicate operation name: ${operation.name}`);
    names.add(operation.name);

    assert.equal(typeof operation.summary, 'string', `${operation.name} summary is required.`);
    assert.ok(allowedOperationTypes.has(operation.type), `${operation.name} has an invalid type.`);
    assert.equal(typeof operation.context, 'string', `${operation.name} context is required.`);
    assert.ok(Array.isArray(operation.capabilities), `${operation.name} capabilities must be an array.`);
    if (operation.capability_mode !== undefined) {
      assert.ok(
        allowedCapabilityModes.has(operation.capability_mode),
        `${operation.name} has an invalid capability_mode.`
      );
      assert.ok(
        operation.capabilities.length > 0,
        `${operation.name} capability_mode requires at least one capability.`
      );
    }
    if (operation.target_capabilities !== undefined) {
      assert.equal(typeof operation.target_capabilities, 'object', `${operation.name} target_capabilities must be an object.`);
      for (const [parameterName, capabilities] of Object.entries(operation.target_capabilities)) {
        assert.ok(
          Object.hasOwn(operation.parameters, parameterName),
          `${operation.name} target_capabilities references unknown parameter ${parameterName}.`
        );
        assert.ok(Array.isArray(capabilities), `${operation.name}.${parameterName} target capabilities must be an array.`);
        assert.ok(capabilities.length > 0, `${operation.name}.${parameterName} target capabilities must not be empty.`);
        for (const capability of capabilities) {
          assert.equal(typeof capability, 'string', `${operation.name}.${parameterName} target capability must be a string.`);
        }
      }
    }
    assert.ok(Array.isArray(operation.transports), `${operation.name} transports must be an array.`);
    assert.ok(operation.transports.length > 0, `${operation.name} must expose at least one transport.`);
    assert.ok(allowedFileModes.has(operation.files), `${operation.name} has an invalid file mode.`);
    assert.equal(typeof operation.parameters, 'object', `${operation.name} parameters are required.`);
    assert.equal(typeof operation.returns, 'object', `${operation.name} returns are required.`);
    assert.ok(Array.isArray(operation.errors), `${operation.name} errors must be an array.`);
    assert.ok(Array.isArray(operation.tests), `${operation.name} tests must be an array.`);

    for (const transport of operation.transports) {
      assert.ok(allowedTransportNames.has(transport), `${operation.name} has an invalid transport: ${transport}`);
    }

    if (operation.type === 'write') {
      assert.ok(operation.tests.includes('parity'), `${operation.name} must include parity tests.`);
    }

    for (const [parameterName, definition] of Object.entries(operation.parameters)) {
      assert.equal(typeof definition.type, 'string', `${operation.name}.${parameterName} type is required.`);
      if (definition.enum !== undefined) {
        assert.ok(Array.isArray(definition.enum), `${operation.name}.${parameterName} enum must be an array.`);
        assert.ok(definition.enum.length > 0, `${operation.name}.${parameterName} enum must not be empty.`);
        for (const option of definition.enum) {
          assert.equal(typeof option, 'string', `${operation.name}.${parameterName} enum values must be strings.`);
        }
      }
    }
  }
}

export function assertSameSet(actual, expected, label) {
  const actualSet = new Set(actual);
  const expectedSet = new Set(expected);

  assert.deepEqual(
    [...actualSet].sort(),
    [...expectedSet].sort(),
    `${label} must match the canonical contract.`
  );
}
