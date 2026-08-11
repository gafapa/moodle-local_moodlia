import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';

const excludedDirectories = new Set(['.git', 'empaquetado', 'node_modules', 'playwright-report', 'playwright-report-site', 'test-results']);
const files = await findJavaScriptFiles(process.cwd());

for (const file of files) {
  await checkSyntax(file);
}

console.log(`JavaScript syntax check passed for ${files.length} files.`);

async function findJavaScriptFiles(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const results = [];

  for (const entry of entries) {
    if (entry.isDirectory() && excludedDirectories.has(entry.name)) {
      continue;
    }

    const target = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      results.push(...await findJavaScriptFiles(target));
    } else if (/\.(?:js|mjs|cjs)$/.test(entry.name)) {
      results.push(target);
    }
  }

  return results.sort();
}

function checkSyntax(file) {
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, ['--check', file], {
      cwd: process.cwd(),
      stdio: 'pipe'
    });
    let stderr = '';

    child.stderr.on('data', (chunk) => {
      stderr += chunk;
    });
    child.on('error', reject);
    child.on('exit', (code) => {
      if (code === 0) {
        resolve();
      } else {
        reject(new Error(`JavaScript syntax check failed for ${path.relative(process.cwd(), file)}:\n${stderr.trim()}`));
      }
    });
  });
}
