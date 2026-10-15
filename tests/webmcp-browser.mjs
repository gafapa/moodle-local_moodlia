import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const root = fileURLToPath(new URL('../', import.meta.url));
const php = process.env.MOODLE_SERVER_PHP || 'php';
const tools = JSON.parse(execFileSync(php, ['-n', '-r',
  "define('MOODLE_INTERNAL', true); require 'classes/mcp/manifest.php'; require 'classes/webmcp/bridge.php'; echo json_encode(\\local_moodlia\\webmcp\\bridge::tools(), JSON_THROW_ON_ERROR);",
], { cwd: root, encoding: 'utf8' }));
assert.equal(tools.length, 251);
const browser = await chromium.launch({ channel: 'chromium', headless: true, args: ['--enable-blink-features=WebMCP'] });
try {
  const page = await browser.newPage();
  const requests = [];
  await page.route('https://moodlia-webmcp.test/**', async route => {
    if (route.request().method() === 'POST') {
      const data = new URLSearchParams(route.request().postData());
      assert.equal(data.get('sesskey'), 'fictional-key');
      requests.push(Object.fromEntries(data));
      const result = data.get('action') === 'list' ? tools : data.get('tool') === 'moodlia_open_page'
        ? { url: 'https://moodlia-webmcp.test/moodle/mod/page/view.php?id=44', destination: 'activity' }
        : { course_id: JSON.parse(data.get('arguments')).course_id, sections: [] };
      await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ ok: true, result }) });
    } else {
      await route.fulfill({ contentType: 'text/html', body: '<!doctype html><html><title>MoodlIA WebMCP fixture</title><body><h1>Fictional course</h1></body></html>' });
    }
  });
  await page.goto('https://moodlia-webmcp.test/moodle/course/view.php?id=7');
  await page.evaluate(() => {
    window.M = { cfg: { sesskey: 'fictional-key' } };
    window.define = (_name, _dependencies, factory) => { window.webmcpModule = factory(); };
  });
  await page.addScriptTag({ path: fileURLToPath(new URL('../amd/build/webmcp.min.js', import.meta.url)) });
  await page.evaluate(() => window.webmcpModule.init({
    endpoint: 'https://moodlia-webmcp.test/moodle/local/moodlia/webmcp.php', context: { course_id: 7, module_id: 0 },
  }));
  const registered = await page.evaluate(async () => (await document.modelContext.getTools()).map(tool => tool.name));
  assert.equal(registered.length, 251);
  assert.ok(registered.includes('save_assignment_grade'));
  console.log('PASS: native Chromium WebMCP accepts all 250 MoodlIA schemas and the navigation tool.');
  const result = await page.evaluate(async () => {
    const tools = await document.modelContext.getTools();
    return document.modelContext.executeTool(tools.find(tool => tool.name === 'get_course_contents'), { course_id: 99 });
  });
  const data = typeof result === 'string' ? JSON.parse(result) : result;
  assert.equal(data.course_id, 99);
  assert.equal(requests.at(-1).tool, 'get_course_contents');
  console.log('PASS: native tool execution targets a different course using the browser session adapter.');
  const navigation = page.waitForURL('https://moodlia-webmcp.test/moodle/mod/page/view.php?id=44');
  await page.evaluate(async () => {
    const tools = await document.modelContext.getTools();
    await document.modelContext.executeTool(tools.find(tool => tool.name === 'moodlia_open_page'), { destination: 'activity', module_id: 44 });
  });
  await navigation;
  assert.equal(browser.contexts()[0].pages().length, 1);
  console.log('PASS: the navigation tool shows the authorized destination in the same tab.');
} finally {
  await browser.close();
}
