import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const root = new URL('../../', import.meta.url);
const source = await fs.readFile(new URL('amd/src/webmcp.js', root), 'utf8');
const config = { endpoint: 'https://school.test/moodle/local/moodlia/webmcp.php', context: { course_id: 7, module_id: 12 } };
const tool = { name: 'get_course_contents', description: 'Read a course.', inputSchema: { type: 'object', properties: { course_id: { type: 'integer' } } } };

function setup({ supported = true, tools = [tool], failRegistration = false } = {}) {
  let api;
  const calls = [];
  const registered = [];
  const navigations = [];
  const listeners = {};
  const modelContext = { async registerTool(descriptor, options) {
    if (failRegistration) throw new Error('Registration refused');
    registered.push({ descriptor, signal: options.signal });
  } };
  const sandbox = {
    AbortController, URL, URLSearchParams, navigator: {}, document: supported ? { modelContext } : {},
    M: { cfg: { sesskey: 'fictional-session-key' } }, console: { warn() {} },
    window: { isSecureContext: true, location: { href: 'https://school.test/moodle/course/view.php?id=7', origin: 'https://school.test', assign: url => navigations.push(url) },
      setTimeout: callback => callback(), addEventListener: (name, callback) => { listeners[name] = callback; } },
    define: (_dependencies, factory) => { api = factory(); },
    fetch: async (url, init) => {
      calls.push({ url, init });
      if (init.signal?.aborted) throw init.signal.reason;
      const action = init.body.get('action');
      return { ok: true, async json() { return { ok: true, result: action === 'list' ? tools : { id: 17 } }; } };
    },
  };
  vm.runInNewContext(source, sandbox);
  return { api, sandbox, calls, registered, navigations, listeners };
}

test('unsupported browsers do not request a tool catalogue', async () => {
  const state = setup({ supported: false });
  await state.api.init(config);
  assert.equal(state.calls.length, 0);
});

test('insecure contexts and external endpoints cannot send the session key', async () => {
  const insecure = setup();
  insecure.sandbox.window.isSecureContext = false;
  await insecure.api.init(config);
  assert.equal(insecure.calls.length, 0);
  const external = setup();
  await external.api.init({ ...config, endpoint: 'https://other.test/webmcp.php' });
  assert.equal(external.calls.length, 0);
  assert.equal(external.registered.length, 0);
});

test('the legacy navigator API still registers tools without a document API', async () => {
  const state = setup();
  state.sandbox.navigator.modelContext = state.sandbox.document.modelContext;
  delete state.sandbox.document.modelContext;
  await state.api.init(config);
  assert.equal(state.registered.length, 1);
});

test('concurrent initialization does not duplicate tool registrations', async () => {
  const state = setup();
  await Promise.all([state.api.init(config), state.api.init(config)]);
  assert.equal(state.calls.length, 1);
  assert.equal(state.registered.length, 1);
});

test('a failed catalogue fetch can be retried by a later explicit initialization', async () => {
  const state = setup();
  const successful = state.sandbox.fetch;
  state.sandbox.fetch = async () => { throw new Error('Synthetic connection failure'); };
  await state.api.init(config);
  assert.equal(state.registered.length, 0);
  state.sandbox.fetch = successful;
  await state.api.init(config);
  assert.equal(state.registered.length, 1);
});

test('back-forward cache restoration replaces registrations and aborts the old controller', async () => {
  const state = setup();
  await state.api.init(config);
  const first = state.registered[0];
  state.listeners.pagehide({ persisted: true });
  assert.equal(first.signal.aborted, false);
  state.listeners.pageshow({ persisted: true });
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(first.signal.aborted, true);
  assert.equal(state.registered.length, 2);
  assert.equal(state.registered[1].signal.aborted, false);
});

test('network failures during tool execution are not relabelled as definitive Moodle errors', async () => {
  const state = setup();
  await state.api.init(config);
  let calls = 0;
  state.sandbox.fetch = async () => { calls++; throw new Error('Connection interrupted'); };
  await assert.rejects(state.registered[0].descriptor.execute({ course_id: 7 }), /Connection interrupted/);
  assert.equal(calls, 1);
});

test('same-origin loopback HTTP navigation remains available to disposable Moodle labs', async () => {
  const state = setup({ tools: [{ ...tool, name: 'moodlia_open_page' }] });
  state.sandbox.window.location.href = 'http://127.0.0.1:18097/course/view.php?id=7';
  state.sandbox.window.location.origin = 'http://127.0.0.1:18097';
  await state.api.init({ ...config, endpoint: 'http://127.0.0.1:18097/local/moodlia/webmcp.php' });
  state.sandbox.fetch = async () => ({ ok: true, json: async () => ({ ok: true, result: { url: 'http://127.0.0.1:18097/course/view.php?id=9' } }) });
  await state.registered[0].descriptor.execute({ destination: 'course', course_id: 9 });
  assert.deepEqual(state.navigations, ['http://127.0.0.1:18097/course/view.php?id=9']);
});

test('definitive Moodle failures are structured tool errors without retrying', async () => {
  const state = setup();
  await state.api.init(config);
  let count = 0;
  state.sandbox.fetch = async () => {
    count++;
    return { ok: false, json: async () => ({ ok: false, error: 'Permission denied by Moodle.' }) };
  };
  const result = await state.registered[0].descriptor.execute({ course_id: 99 });
  assert.equal(result.isError, true);
  assert.equal(result.content[0].text, 'Permission denied by Moodle.');
  assert.equal(count, 1);
});

test('tools use the logged-in session and explicit target arguments across courses', async () => {
  const state = setup();
  await state.api.init(config);
  const registered = state.registered[0].descriptor;
  assert.match(registered.description, /not a scope restriction/);
  await registered.execute({ course_id: 99 });
  const { init } = state.calls[1];
  assert.equal(init.credentials, 'same-origin');
  assert.equal(init.redirect, 'error');
  assert.equal(init.body.get('sesskey'), 'fictional-session-key');
  assert.equal(init.body.get('tool'), 'get_course_contents');
  assert.deepEqual(JSON.parse(init.body.get('arguments')), { course_id: 99 });
  assert.equal(state.calls[1].url.includes('sesskey'), false);
});

test('native execution cancellation reaches the server request without automatic retries', async () => {
  const state = setup();
  await state.api.init(config);
  const controller = new AbortController();
  controller.abort(new Error('Cancelled'));
  await assert.rejects(state.registered[0].descriptor.execute({ course_id: 99 }, { signal: controller.signal }), /Cancelled/);
  assert.equal(state.calls.length, 2);
});

test('navigation changes the existing tab only after the server authorizes the destination', async () => {
  const state = setup({ tools: [{ ...tool, name: 'moodlia_open_page' }] });
  await state.api.init(config);
  state.sandbox.fetch = async () => ({ ok: true, json: async () => ({ ok: true, result: { url: 'https://school.test/moodle/mod/page/view.php?id=32' } }) });
  await state.registered[0].descriptor.execute({ destination: 'activity', module_id: 32 });
  assert.deepEqual(state.navigations, ['https://school.test/moodle/mod/page/view.php?id=32']);
  state.sandbox.fetch = async () => ({ ok: true, json: async () => ({ ok: true, result: { url: 'https://other.test/' } }) });
  await assert.rejects(state.registered[0].descriptor.execute({ destination: 'activity', module_id: 32 }), /invalid navigation/);
  assert.equal(state.navigations.length, 1);
});

test('registration failure is isolated and page lifecycle releases tools', async () => {
  const failure = setup({ failRegistration: true });
  await failure.api.init(config);
  const state = setup();
  await state.api.init(config);
  state.listeners.pagehide({ persisted: false });
  assert.equal(state.registered[0].signal.aborted, true);
});

test('the installable AMD module matches its source', async () => {
  const build = await fs.readFile(new URL('amd/build/webmcp.min.js', root), 'utf8');
  assert.equal(build, source.replace('define([], function()', 'define("local_moodlia/webmcp", [], function()'));
});
