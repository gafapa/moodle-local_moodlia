import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import test from 'node:test';

import { fromRoot } from '../helpers/paths.mjs';

const pluginRoot = fromRoot();

test('plugin contains the required Moodle entry points', async () => {
  for (const relativePath of [
    'version.php',
    'mcp.php',
    'db/access.php',
    'db/services.php',
    'lang/en/local_moodlia.php',
    'classes/privacy/provider.php',
    'classes/mcp/manifest.php'
  ]) {
    await fs.access(path.join(pluginRoot, relativePath));
  }
});

test('plugin does not declare a private database schema', async () => {
  for (const relativePath of ['db/install.xml', 'db/upgrade.php']) {
    await assert.rejects(fs.access(path.join(pluginRoot, relativePath)));
  }
});

test('MCP REST bridge uses Moodle HTTP security and proxy handling', async () => {
  const source = await fs.readFile(path.join(pluginRoot, 'mcp.php'), 'utf8');

  assert.match(source, /new \\core\\http_client\s*\(/);
  assert.match(source, /'http_errors'\s*=>\s*false/);
  assert.doesNotMatch(source, /\bcurl_(?:init|setopt|setopt_array|exec|getinfo|error|close)\s*\(/);
});

test('MCP endpoint supports modern stateless and legacy lifecycle clients', async () => {
  const source = await fs.readFile(path.join(pluginRoot, 'mcp.php'), 'utf8');

  assert.match(source, /LOCAL_MOODLIA_MCP_MODERN_PROTOCOL_VERSION\s*=\s*'2026-07-28'/);
  assert.match(source, /\$method\s*===\s*'server\/discover'/);
  assert.match(source, /io\.modelcontextprotocol\/protocolVersion/);
  assert.match(source, /io\.modelcontextprotocol\/clientCapabilities/);
  assert.match(source, /HTTP_MCP_METHOD/);
  assert.match(source, /HTTP_MCP_NAME/);
  assert.match(source, /'resultType'\]\s*=.*'complete'/);
  assert.match(source, /'ttlMs'\]/);
  assert.match(source, /-32020/);
  assert.match(source, /-32022/);
  assert.match(source, /\$method\s*===\s*'initialize'/);
  assert.match(source, /notifications\/initialized/);
});

test('operation code avoids raw SQL execution', async () => {
  const operationRoot = path.join(pluginRoot, 'classes', 'operation');
  const files = await fs.readdir(operationRoot);

  for (const file of files.filter((name) => name.endsWith('.php'))) {
    const source = await fs.readFile(path.join(operationRoot, file), 'utf8');
    assert.doesNotMatch(source, /->(?:get|set|delete|execute)[a-z_]*_sql\s*\(|->execute\s*\(/, file);
  }
});
