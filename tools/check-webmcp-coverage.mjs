import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export function readWebMcpCoverage(xml, minimum = 95) {
  if (!Number.isFinite(minimum) || minimum < 0 || minimum > 100) throw new Error('Invalid coverage threshold.');
  if (!/^(?:<\?xml[^?]*\?>\s*)?<coverage\b[\s\S]*<\/coverage>$/.test(xml.trim())) {
    throw new Error('Missing or truncated Clover coverage document.');
  }
  const files = [...xml.matchAll(/<file\b([^>]*)>([\s\S]*?)<\/file>/g)];
  const matches = files.filter(entry => /\bname="([^"]+)"/.exec(entry[1])?.[1]
    .replaceAll('\\', '/').endsWith('/classes/webmcp/bridge.php'));
  if (matches.length !== 1) throw new Error('Coverage must contain exactly one WebMCP bridge source file.');
  const metrics = [...matches[0][2].matchAll(/<metrics\b([^>]*?)\/?\s*>/g)].at(-1)?.[1];
  const number = name => {
    const value = new RegExp(`\\b${name}="(\\d+)"`).exec(metrics || '')?.[1];
    if (value === undefined) throw new Error(`Missing coverage metric: ${name}`);
    return Number(value);
  };
  const statements = number('statements');
  const covered = number('coveredstatements');
  if (!Number.isSafeInteger(statements) || !Number.isSafeInteger(covered) || statements < 1 || covered > statements) {
    throw new Error('Invalid WebMCP coverage metrics.');
  }
  const percentage = 100 * covered / statements;
  if (percentage < minimum) throw new Error(`WebMCP bridge coverage ${percentage.toFixed(2)}% is below ${minimum}%.`);
  return { file: 'classes/webmcp/bridge.php', covered_statements: covered, statements,
    line_coverage_percent: Number(percentage.toFixed(2)), minimum_percent: minimum };
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const source = process.argv[2];
  if (!source) throw new Error('Usage: node tools/check-webmcp-coverage.mjs <clover.xml> [minimum-percent]');
  console.log(JSON.stringify(readWebMcpCoverage(await fs.readFile(source, 'utf8'), Number(process.argv[3] || 95)), null, 2));
}
