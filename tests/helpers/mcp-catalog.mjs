import assert from 'node:assert/strict';
import Ajv2020 from 'ajv/dist/2020.js';

export function validateMcpCatalog(tools, expectedNames) {
  assert.ok(Array.isArray(tools) && tools.length > 0, 'tools/list must return a nonempty tool array');
  const names = tools.map(tool => tool.name);
  assert.equal(new Set(names).size, names.length, 'Tool names must be unique');
  if (expectedNames) assert.deepEqual([...names].sort(), [...expectedNames].sort());
  const ajv = new Ajv2020({ allErrors: true, strict: true });
  for (const tool of tools) {
    assert.equal(typeof tool.name, 'string');
    assert.ok(typeof tool.description === 'string' && tool.description.trim(), tool.name);
    assert.equal(tool.inputSchema.type, 'object', tool.name);
    let validate;
    try { validate = ajv.compile(tool.inputSchema); }
    catch (error) { throw new Error(`${tool.name}: invalid JSON Schema: ${error.message}`); }
    const valid = {};
    for (const [name, schema] of Object.entries(tool.inputSchema.properties || {})) {
      valid[name] = schema.enum?.[0] ?? ({ integer: 1, number: 1.5, string: 'fixture', boolean: true, array: [], object: {} })[schema.type];
    }
    assert.ok(validate(valid), `${tool.name}: representative arguments rejected: ${ajv.errorsText(validate.errors)}`);
    assert.equal(validate([]), false, `${tool.name}: arguments cannot be an array`);
    assert.equal(validate({ ...valid, __undeclared_fixture_argument: true }), false, `${tool.name}: undeclared argument accepted`);
    for (const required of tool.inputSchema.required || []) {
      const missing = { ...valid };
      delete missing[required];
      assert.equal(validate(missing), false, `${tool.name}: missing ${required} accepted`);
    }
  }
  return names.length;
}
