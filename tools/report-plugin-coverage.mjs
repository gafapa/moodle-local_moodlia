import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { readCloverFiles } from './check-webmcp-coverage.mjs';

const percent = (covered, statements) => (statements === 0 ? 100 : Number((100 * covered / statements).toFixed(2)));

// Summarises a full-suite Clover report: plugin totals, one line per directory, and gated files.
// requirements maps a plugin-relative path to its minimum line coverage percentage.
export function reportPluginCoverage(xml, requirements = {}) {
  const files = readCloverFiles(xml)
    .map(file => ({ ...file, path: /\/local\/moodlia\/(.+)$/.exec(file.name)?.[1] }))
    .filter(file => file.path && !file.path.startsWith('tests/') && !file.path.startsWith('vendor/'));
  if (files.length === 0) throw new Error('Coverage contains no plugin source files.');

  const sum = list => list.reduce((total, file) => ({
    statements: total.statements + file.statements,
    covered: total.covered + file.covered,
  }), { statements: 0, covered: 0 });
  const totals = sum(files);
  const directories = {};
  for (const directory of [...new Set(files.map(file => path.posix.dirname(file.path)))].sort()) {
    const total = sum(files.filter(file => path.posix.dirname(file.path) === directory));
    directories[directory] = { ...total, line_coverage_percent: percent(total.covered, total.statements) };
  }

  const gated = {};
  const failures = [];
  for (const [filePath, minimum] of Object.entries(requirements)) {
    const file = files.find(entry => entry.path === filePath);
    if (!file) {
      failures.push(`${filePath} is missing from the coverage report.`);
      continue;
    }
    const value = percent(file.covered, file.statements);
    gated[filePath] = {
      statements: file.statements,
      covered: file.covered,
      line_coverage_percent: value,
      minimum_percent: minimum,
      uncovered_lines: file.uncovered,
    };
    if (value < minimum) failures.push(`${filePath} coverage ${value}% is below ${minimum}%.`);
  }

  return {
    report: {
      files: files.length,
      statements: totals.statements,
      covered: totals.covered,
      line_coverage_percent: percent(totals.covered, totals.statements),
      directories,
      gated,
    },
    failures,
  };
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const [source, ...gates] = process.argv.slice(2);
  if (!source) throw new Error('Usage: node tools/report-plugin-coverage.mjs <clover.xml> [path=minimum ...]');
  const requirements = Object.fromEntries(gates.map(gate => {
    const [filePath, minimum] = gate.split('=');
    if (!filePath || !Number.isFinite(Number(minimum))) throw new Error(`Invalid gate: ${gate}`);
    return [filePath, Number(minimum)];
  }));
  const { report, failures } = reportPluginCoverage(await fs.readFile(source, 'utf8'), requirements);
  console.log(JSON.stringify(report, null, 2));
  if (failures.length > 0) {
    console.error(failures.join('\n'));
    process.exitCode = 1;
  }
}
