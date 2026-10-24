import test from 'node:test';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import net from 'node:net';
import { once } from 'node:events';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { qualifyMcp } from '../helpers/mcp-http-checks.mjs';

const root = fileURLToPath(new URL('../../', import.meta.url));

test('real PHP MCP endpoint passes SDK, lifecycle, catalog, result and rejection checks over HTTP', { timeout: 90000 }, async () => {
  const temporary = await fs.mkdtemp(path.join(os.tmpdir(), 'moodlia-mcp-http-'));
  let server;
  try {
    const reservation = net.createServer();
    reservation.listen(0, '127.0.0.1');
    await once(reservation, 'listening');
    const port = reservation.address().port;
    await new Promise(resolve => reservation.close(resolve));
    const plugin = path.join(temporary, 'local/moodlia');
    await fs.mkdir(path.join(plugin, 'classes/mcp'), { recursive: true });
    for (const file of ['mcp.php', 'version.php', 'classes/mcp/manifest.php', 'classes/mcp/arguments.php']) {
      await fs.copyFile(path.join(root, file), path.join(plugin, file));
    }
    // Only Moodle bootstrap/REST are stubbed. The HTTP parser, dispatcher,
    // serializer, schema catalog and argument adapter are the production files.
    await fs.writeFile(path.join(temporary, 'config.php'), `<?php
namespace core {
    class http_client {
        public function __construct(array $options = []) {}
        public function post($url, array $options) {
            $form = $options['form_params'];
            $payload = $form['wstoken'] !== str_repeat('a', 32)
                ? ['exception' => 'moodle_exception', 'errorcode' => 'invalidtoken', 'message' => 'Invalid fixture token.']
                : ($form['wsfunction'] === 'local_moodlia_get_current_user'
                    ? ['id' => 7, 'username' => 'fixture', 'fullname' => 'Fictional Teacher', 'site_url' => 'http://127.0.0.1:${port}']
                    : ['plugin_release' => 'fixture', 'moodle_release' => 'fixture']);
            return new class($payload) {
                private $payload;
                public function __construct($payload) { $this->payload = $payload; }
                public function getBody() { return json_encode($this->payload, JSON_THROW_ON_ERROR); }
                public function getStatusCode() { return 200; }
            };
        }
    }
}
namespace {
    define('MOODLE_INTERNAL', true);
    define('MATURITY_BETA', 100);
    define('DEBUG_DEVELOPER', 0);
    class invalid_parameter_exception extends \\Exception {}
    function debugging($message, $level) {}
    $CFG = (object) ['wwwroot' => 'http://127.0.0.1:${port}'];
    require __DIR__ . '/local/moodlia/classes/mcp/manifest.php';
    require __DIR__ . '/local/moodlia/classes/mcp/arguments.php';
}
`);
    let output = '';
    server = spawn(process.env.MOODLE_SERVER_PHP || 'php', ['-n', '-S', `127.0.0.1:${port}`, '-t', temporary], { windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] });
    let startupError;
    server.on('error', error => { startupError = error; });
    server.stderr.on('data', chunk => { output = (output + chunk).slice(-4000); });
    const endpoint = `http://127.0.0.1:${port}/local/moodlia/mcp.php`;
    let ready = false;
    for (let attempt = 0; attempt < 50; attempt++) {
      if (startupError || server.exitCode !== null) throw startupError || new Error(output);
      try { ready = (await fetch(endpoint, { signal: AbortSignal.timeout(1000) })).status === 405; }
      catch { /* Wait for the temporary loopback server to bind. */ }
      if (ready) break;
      await new Promise(resolve => setTimeout(resolve, 100));
    }
    if (!ready) throw new Error(`PHP fixture did not start: ${output}`);
    const contract = JSON.parse(await fs.readFile(path.join(root, 'contract/operations.json'), 'utf8'));
    const report = await qualifyMcp({ endpoint, token: 'a'.repeat(32), expectedNames: contract.operations.filter(operation => operation.transports.includes('mcp')).map(operation => operation.name) });
    console.log(`MCP HTTP fixture: ${report.checks} checks, ${report.tools} schemas; Moodle/REST stubbed, no production access.`);
  } finally {
    if (server?.pid && server.exitCode === null) {
      const closed = once(server, 'exit');
      server.kill();
      await closed;
    }
    if (!temporary.startsWith(path.join(os.tmpdir(), 'moodlia-mcp-http-'))) throw new Error('Unsafe fixture cleanup path');
    await fs.rm(temporary, { recursive: true, force: true });
  }
});
