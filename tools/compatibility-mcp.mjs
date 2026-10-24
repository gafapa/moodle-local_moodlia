import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';
import { qualifyMcp } from '../tests/helpers/mcp-http-checks.mjs';

if (process.env.MOODLIA_COMPATIBILITY_SITE !== '1') throw new Error('MOODLIA_COMPATIBILITY_SITE=1 is required.');
const base = new URL(process.env.MOODLE_BASE_URL || 'http://127.0.0.1:8000');
if (!['127.0.0.1', 'localhost', '[::1]'].includes(base.hostname) || base.username || base.password || base.search || base.hash) {
  throw new Error('Only a disposable loopback Moodle installation is allowed.');
}
const token = process.env.MOODLE_REST_TOKEN;
if (!token) throw new Error('MOODLE_REST_TOKEN is required.');
const endpoint = new URL(`${base.pathname.replace(/\/$/, '')}/local/moodlia/mcp.php`, base.origin);
const contract = JSON.parse(await fs.readFile(new URL('../contract/operations.json', import.meta.url), 'utf8'));
const report = await qualifyMcp({ endpoint, token, expectedNames: contract.operations.filter(operation => operation.transports.includes('mcp')).map(operation => operation.name) });

// Exercise a real write/read/delete cycle independently of the REST qualification.
const client = new Client({ name: 'moodlia-write-qualification', version: '1.0.0' });
const transport = new StreamableHTTPClientTransport(endpoint, {
  requestInit: { headers: { Authorization: `Bearer ${token}` } },
  fetch: (url, init) => fetch(url, { ...init, redirect: 'error', signal: AbortSignal.timeout(20000) }),
});
let courseId;
const call = async (name, args) => {
  const result = await client.callTool({ name, arguments: args });
  assert.equal(result.isError, false, name);
  assert.deepEqual(JSON.parse(result.content[0].text), result.structuredContent, name);
  report.results.push(`official SDK write cycle: ${name}`);
  report.checks++;
  return result.structuredContent;
};
try {
  await client.connect(transport);
  const course = await call('create_course', { fullname: 'Disposable MCP qualification', shortname: `mcp-${Date.now()}`, visible: false });
  courseId = Number(course.course_id ?? course.id);
  assert.ok(courseId > 0);
  const module = await call('create_module', { course_id: courseId, section_number: 0, module_type: 'page', name: 'MCP source fixture', options: { content: '<p>Original MCP fixture.</p>' } });
  const moduleId = Number(module.course_module_id);
  assert.ok(moduleId > 0);
  const content = '<p>Updated through MCP: quotes " &amp; Unicode á.</p>';
  await call('update_module', { course_id: courseId, module_id: moduleId, options: { visible: false } });
  await call('update_page', { course_id: courseId, module_id: moduleId, content });
  const details = await call('get_module_details', { course_id: courseId, module_id: moduleId });
  assert.equal(JSON.parse(details.extra_json).activity.content, content);
  assert.equal(Boolean(details.visible), false);
  const invalid = await fetch(endpoint, { method: 'POST', redirect: 'error', signal: AbortSignal.timeout(20000),
    headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ jsonrpc: '2.0', id: 'required-argument', method: 'tools/call', params: { name: 'get_course_details', arguments: {} } }) });
  const error = await invalid.json();
  assert.ok(error.error && !error.result);
  report.results.push('Moodle rejects missing required operation arguments');
  report.checks++;
} finally {
  try {
    if (courseId) {
      const deleted = await call('delete_course', { course_id: courseId });
      assert.ok(deleted.deleted);
    }
  } finally { await client.close(); }
}
console.log(JSON.stringify({ ...report, scope: 'Actual loopback Moodle HTTP endpoint, official SDK and temporary native write cycle; no external AI provider.' }, null, 2));
