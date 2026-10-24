import assert from 'node:assert/strict';
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';
import { validateMcpCatalog } from './mcp-catalog.mjs';

export async function qualifyMcp({ endpoint, token, expectedNames }) {
  const checks = [];
  let sequence = 0;
  const modern = '2026-07-28';
  const metadata = {
    'io.modelcontextprotocol/protocolVersion': modern,
    'io.modelcontextprotocol/clientCapabilities': {},
    'io.modelcontextprotocol/clientInfo': { name: 'moodlia-qualification', version: '1.0.0' },
  };
  const transportFetch = (url, init = {}) => fetch(url, { ...init, redirect: 'error',
    signal: init.signal ? AbortSignal.any([init.signal, AbortSignal.timeout(20000)]) : AbortSignal.timeout(20000) });
  const raw = async (method, params = {}, options = {}) => {
    const id = options.notification ? undefined : options.id ?? ++sequence;
    const headers = { Accept: 'application/json, text/event-stream', 'Content-Type': 'application/json', Authorization: `Bearer ${token}`, ...options.headers };
    if (options.noAuthorization) delete headers.Authorization;
    const response = await transportFetch(endpoint, { method: options.httpMethod || 'POST', headers,
      body: options.httpMethod === 'GET' ? undefined : options.body ?? JSON.stringify({ jsonrpc: '2.0', ...(id === undefined ? {} : { id }), method, params }) });
    const text = await response.text();
    assert.ok(!text.includes(token), 'MCP response leaked the supplied token');
    assert.match(response.headers.get('cache-control') || '', /no-store/);
    const body = text ? JSON.parse(text) : null;
    if (body) assert.equal(body.jsonrpc, '2.0');
    return { response, body, id, text };
  };
  const success = async (method, params = {}, options = {}) => {
    const result = await raw(method, params, options);
    assert.equal(result.response.status, 200, method);
    assert.ok(!result.body.error, `${method}: ${JSON.stringify(result.body.error)}`);
    assert.equal(result.body.id, result.id);
    checks.push(`${method}: success`);
    return result.body.result;
  };
  const failure = async (label, method, params, options, status, code) => {
    const result = await raw(method, params, options);
    assert.equal(result.response.status, status, label);
    assert.equal(result.body.error?.code, code, label);
    assert.ok(!Object.hasOwn(result.body, 'result'), label);
    checks.push(label);
  };

  // An independent published SDK exercises the actual HTTP lifecycle and result validators.
  const client = new Client({ name: 'moodlia-sdk-qualification', version: '1.0.0' });
  const transport = new StreamableHTTPClientTransport(new URL(endpoint), {
    requestInit: { headers: { Authorization: `Bearer ${token}` } }, fetch: transportFetch,
  });
  try {
    await client.connect(transport);
    assert.ok(client.getServerVersion()?.version);
    checks.push('official SDK: initialize and initialized notification');
    const catalog = await client.listTools();
    validateMcpCatalog(catalog.tools, expectedNames);
    checks.push('official SDK: complete tools/list and standard schema compilation');
    await client.ping();
    checks.push('official SDK: ping result validation');
    for (const name of ['get_current_user', 'get_moodlia_status']) {
      const result = await client.callTool({ name, arguments: {} });
      assert.equal(result.isError, false);
      assert.equal(result.content[0].type, 'text');
      assert.deepEqual(JSON.parse(result.content[0].text), result.structuredContent);
      assert.ok(result.structuredContent && !Array.isArray(result.structuredContent));
      checks.push(`official SDK: ${name} empty arguments and structured/text parity`);
    }
  } finally { await client.close(); }

  for (const version of ['2025-03-26', '2025-06-18', '2025-11-25']) {
    const headers = { 'MCP-Protocol-Version': version };
    const result = await success('initialize', { protocolVersion: version, capabilities: {}, clientInfo: { name: 'fixture', version: '1' } }, { headers });
    assert.equal(result.protocolVersion, version);
    const notification = await raw('notifications/initialized', {}, { notification: true, headers });
    assert.equal(notification.response.status, 202);
    assert.equal(notification.text, '');
    checks.push(`${version}: notification has no response body`);
    const call = await success('tools/call', { name: 'get_current_user', arguments: {} }, { headers, id: `identity-${version}` });
    assert.ok(call.structuredContent.id > 0);
  }
  const modernRequest = (method, params = {}, options = {}) => success(method, { ...params, _meta: metadata }, {
    ...options, headers: { 'MCP-Protocol-Version': modern, 'Mcp-Method': method, ...(params.name ? { 'Mcp-Name': params.name } : {}), ...options.headers },
  });
  const discovery = await modernRequest('server/discover');
  assert.ok(discovery.supportedVersions.includes(modern));
  assert.equal(discovery.resultType, 'complete');
  const modernCatalog = await modernRequest('tools/list');
  validateMcpCatalog(modernCatalog.tools, expectedNames);
  assert.equal(modernCatalog.cacheScope, 'private');
  const modernCall = await modernRequest('tools/call', { name: 'get_current_user', arguments: {} });
  assert.ok(modernCall.structuredContent.id > 0);
  assert.deepEqual(JSON.parse(modernCall.content[0].text), modernCall.structuredContent);
  assert.equal((await modernRequest('ping')).resultType, 'complete');

  await failure('missing bearer token', 'tools/list', {}, { noAuthorization: true }, 401, -32001);
  await failure('invalid bearer token', 'tools/list', {}, { headers: { Authorization: 'Bearer invalid-fixture-token' } }, 200, -32005);
  await failure('cross-origin browser request', 'tools/list', {}, { headers: { Origin: 'https://unrelated.invalid' } }, 403, -32003);
  await failure('wrong content type', 'tools/list', {}, { headers: { 'Content-Type': 'text/plain' } }, 415, -32600);
  await failure('unsupported HTTP method', 'tools/list', {}, { httpMethod: 'GET' }, 405, -32600);
  await failure('malformed JSON', 'tools/list', {}, { body: '{broken' }, 200, -32700);
  await failure('invalid JSON-RPC version', 'tools/list', {}, { body: JSON.stringify({ jsonrpc: '1.0', id: 7, method: 'tools/list' }) }, 200, -32600);
  await failure('unknown method', 'missing-fixture-method', {}, {}, 200, -32601);
  await failure('unknown tool', 'tools/call', { name: 'missing_fixture_tool', arguments: {} }, {}, 200, -32601);
  await failure('missing tool name', 'tools/call', {}, {}, 200, -32602);
  await failure('scalar tool arguments', 'tools/call', { name: 'get_current_user', arguments: 'invalid' }, {}, 200, -32602);
  await failure('unsupported legacy version', 'tools/list', {}, { headers: { 'MCP-Protocol-Version': '1900-01-01' } }, 400, -32602);
  await failure('unacceptable legacy response type', 'tools/list', {}, { headers: { Accept: 'text/plain' } }, 406, -32600);
  await failure('modern missing protocol header', 'server/discover', { _meta: metadata }, {}, 400, -32020);
  const modernHeaders = { 'MCP-Protocol-Version': modern, 'Mcp-Method': 'tools/list' };
  await failure('modern protocol metadata mismatch', 'tools/list', { _meta: { ...metadata, 'io.modelcontextprotocol/protocolVersion': '1900-01-01' } }, { headers: modernHeaders }, 400, -32020);
  await failure('modern missing capabilities', 'tools/list', { _meta: { 'io.modelcontextprotocol/protocolVersion': modern } }, { headers: modernHeaders }, 400, -32602);
  await failure('modern method routing mismatch', 'tools/list', { _meta: metadata }, { headers: { ...modernHeaders, 'Mcp-Method': 'ping' } }, 400, -32020);
  await failure('modern name routing mismatch', 'tools/call', { name: 'get_current_user', arguments: {}, _meta: metadata }, { headers: { ...modernHeaders, 'Mcp-Method': 'tools/call', 'Mcp-Name': 'get_moodlia_status' } }, 400, -32020);
  await failure('modern unacceptable response types', 'tools/list', { _meta: metadata }, { headers: { ...modernHeaders, Accept: 'application/json' } }, 406, -32600);
  const identity = await success('tools/call', { name: 'get_current_user' });
  assert.ok(identity.structuredContent.id > 0);
  return { checks: checks.length, tools: expectedNames.length, results: checks };
}
