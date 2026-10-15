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
