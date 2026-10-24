import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { validateMcpCatalog } from '../helpers/mcp-catalog.mjs';

const root = fileURLToPath(new URL('../../', import.meta.url));
const php = process.env.MOODLE_SERVER_PHP || 'php';
const readRuntimeCatalog = () => JSON.parse(execFileSync(php, ['-n', '-r',
  "define('MOODLE_INTERNAL', true); require 'classes/mcp/manifest.php'; echo json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['tools' => \\local_moodlia\\mcp\\manifest::tools()]], JSON_THROW_ON_ERROR);",
], { cwd: root, encoding: 'utf8' }));

test('all PHP-published schemas compile under JSON Schema 2020-12 and enforce their argument contract', () => {
  validateMcpCatalog(readRuntimeCatalog().result.tools);
});

test('standard schema validation catches the original bug and malformed nested schemas', () => {
  const tool = { name: 'fixture', description: 'Fixture', inputSchema: { type: 'object', properties: {}, required: [], additionalProperties: false } };
  for (const properties of [[], { source: { type: 'not-a-json-schema-type' } }, { files: { type: 'array', items: [] } }]) {
    assert.throws(() => validateMcpCatalog([{ ...tool, inputSchema: { ...tool.inputSchema, properties } }]), /invalid JSON Schema/);
  }
});

test('actual PHP tools/list JSON publishes object properties for every tool, including no-argument tools (issue #5)', () => {
  const response = readRuntimeCatalog();
  assert.equal(response.jsonrpc, '2.0');
  const tools = response.result.tools;
  assert.ok(tools.length > 0);
  for (const tool of tools) {
    const schema = tool.inputSchema;
    assert.equal(schema.type, 'object', tool.name);
    assert.equal(typeof schema.properties, 'object', tool.name);
    assert.ok(schema.properties !== null && !Array.isArray(schema.properties), `${tool.name}: properties must serialize as a JSON object`);
    assert.ok(Array.isArray(schema.required), tool.name);
    for (const name of schema.required) assert.ok(Object.hasOwn(schema.properties, name), `${tool.name}.${name}`);
  }
  for (const name of ['get_current_user', 'get_moodlia_status']) {
    const tool = tools.find(tool => tool.name === name);
    assert.ok(tool, name);
    assert.deepEqual(tool.inputSchema.properties, {}, name);
    assert.deepEqual(tool.inputSchema.required, [], name);
    assert.equal(tool.inputSchema.additionalProperties, false);
  }
});

test('PHP argument normalization supports both in-memory and JSON-decoded empty schema maps', () => {
  const output = execFileSync(php, ['-n', '-r',
    "define('MOODLE_INTERNAL', true); require 'classes/mcp/manifest.php'; require 'classes/mcp/arguments.php'; $schema = \\local_moodlia\\mcp\\manifest::tools()[0]['inputSchema']; echo json_encode([\\local_moodlia\\mcp\\arguments::normalize([], $schema), \\local_moodlia\\mcp\\arguments::normalize([], json_decode(json_encode($schema), true)), \\local_moodlia\\mcp\\arguments::normalize(['value' => true], ['properties' => (object) ['value' => ['type' => 'boolean']]])], JSON_THROW_ON_ERROR);",
  ], { cwd: root, encoding: 'utf8' });
  assert.deepEqual(JSON.parse(output), [[], [], { value: '1' }]);
});
