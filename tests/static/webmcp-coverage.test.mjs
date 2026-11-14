import assert from 'node:assert/strict';
import test from 'node:test';
import { readWebMcpCoverage } from '../../tools/check-webmcp-coverage.mjs';

const file = (metrics, name = '/moodle/local/moodlia/classes/webmcp/bridge.php') =>
  `<coverage><project><file name="${name}"><class name="bridge"><metrics statements="1000" coveredstatements="1000"/></class><metrics ${metrics}/></file></project></coverage>`;

test('WebMCP coverage gate uses file metrics and handles Unix and Windows Clover paths', () => {
  for (const name of ['/moodle/local/moodlia/classes/webmcp/bridge.php', 'D:\\moodle\\local\\moodlia\\classes\\webmcp\\bridge.php']) {
    const report = readWebMcpCoverage(file('statements="119" coveredstatements="116"', name));
    assert.equal(report.line_coverage_percent, 97.48);
    assert.equal(report.covered_statements, 116);
  }
});

test('WebMCP coverage gate fails closed on missing, corrupt, duplicated or insufficient data', () => {
  for (const xml of [
    '<coverage/>', file('statements="0" coveredstatements="0"'), file('statements="10" coveredstatements="11"'),
    file('statements="100" coveredstatements="100"').replace('</coverage>', ''),
    file('statements="10"'), file('statements="NaN" coveredstatements="9"'),
    file('statements="100" coveredstatements="94"'),
    file('statements="100" coveredstatements="100"') + file('statements="100" coveredstatements="100"'),
  ]) assert.throws(() => readWebMcpCoverage(xml));
  assert.throws(() => readWebMcpCoverage(file('statements="1" coveredstatements="1"'), NaN));
});

test('plugin coverage report sums plugin sources, ignores tests and enforces file gates', async () => {
  const { reportPluginCoverage } = await import('../../tools/report-plugin-coverage.mjs');
  const entry = (name, statements, covered, lines = '') =>
    `<file name="${name}">${lines}<metrics statements="${statements}" coveredstatements="${covered}"/></file>`;
  const xml = `<coverage><project>${[
    entry('/m/local/moodlia/classes/operation/course_backup_tools.php', 10, 8,
      '<line num="7" type="stmt" count="0"/><line num="8" type="method" count="0"/><line num="9" type="stmt" count="1"/>'),
    entry('D:\\m\\public\\local\\moodlia\\classes\\external\\backup_course_module.php', 4, 4),
    entry('/m/local/moodlia/tests/course_item_backup_test.php', 50, 0),
  ].join('')}</project></coverage>`;

  const passed = reportPluginCoverage(xml, { 'classes/external/backup_course_module.php': 100 });
  assert.deepEqual(passed.failures, []);
  assert.equal(passed.report.files, 2);
  assert.equal(passed.report.line_coverage_percent, 85.71);
  assert.equal(passed.report.directories['classes/operation'].line_coverage_percent, 80);

  const failed = reportPluginCoverage(xml, { 'classes/operation/course_backup_tools.php': 90, 'classes/missing.php': 1 });
  assert.deepEqual(failed.report.gated['classes/operation/course_backup_tools.php'].uncovered_lines, [7]);
  assert.equal(failed.failures.length, 2);
  assert.throws(() => reportPluginCoverage('<coverage></coverage>'));
});
