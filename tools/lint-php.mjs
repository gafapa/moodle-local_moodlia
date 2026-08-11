import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';

const phpCommand = process.env.MOODLE_SERVER_PHP || 'php';
const required = process.argv.includes('--required');
const files = await findPhpFiles(path.join(process.cwd(), 'plugin', 'moodlia'));

try {
  await checkPhpAvailable();
} catch (error) {
  if (required) {
    throw error;
  }
  console.log(`PHP syntax check skipped because ${phpCommand} is unavailable. Use --required in CI.`);
  process.exit(0);
}

for (const file of files) {
  await lintPhp(file);
}

console.log(`PHP syntax check passed for ${files.length} files.`);

async function findPhpFiles(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const results = [];

  for (const entry of entries) {
    const target = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      results.push(...await findPhpFiles(target));
    } else if (entry.name.endsWith('.php')) {
      results.push(target);
    }
  }

  return results.sort();
}

function checkPhpAvailable() {
  return runPhp(['-v'], 'PHP runtime');
}

function lintPhp(file) {
  return runPhp(['-l', file], path.relative(process.cwd(), file));
}

function runPhp(args, label) {
  return new Promise((resolve, reject) => {
    const child = spawn(phpCommand, args, {
      cwd: process.cwd(),
      stdio: 'pipe',
      shell: false
    });
    let output = '';

    child.stdout.on('data', (chunk) => {
      output += chunk;
    });
    child.stderr.on('data', (chunk) => {
      output += chunk;
    });
    child.on('error', (error) => {
      reject(new Error(`${label} failed: ${error.message}`));
    });
    child.on('exit', (code) => {
      if (code === 0) {
        resolve();
      } else {
        reject(new Error(`${label} failed:\n${output.trim()}`));
      }
    });
  });
}
