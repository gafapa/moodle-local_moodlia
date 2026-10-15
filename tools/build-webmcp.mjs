import fs from 'node:fs/promises';

const sourcePath = new URL('../amd/src/webmcp.js', import.meta.url);
const buildPath = new URL('../amd/build/webmcp.min.js', import.meta.url);
const source = await fs.readFile(sourcePath, 'utf8');
const output = source.replace('define([], function()', 'define("local_moodlia/webmcp", [], function()');
if (process.argv.includes('--check')) {
  if (await fs.readFile(buildPath, 'utf8') !== output) throw new Error('The WebMCP AMD build is stale.');
} else {
  await fs.mkdir(new URL('../amd/build/', import.meta.url), { recursive: true });
  await fs.writeFile(buildPath, output);
}
console.log('WebMCP AMD build is current.');
