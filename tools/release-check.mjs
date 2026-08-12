import { spawn } from 'node:child_process';
import os from 'node:os';
import path from 'node:path';

const includePackage = !process.argv.includes('--skip-package');

const checks = [
  ['npm', ['run', 'lint:js']],
  ['npm', ['run', 'lint:php']],
  ['npm', ['run', 'contract:check']],
  ['npm', ['run', 'plugin:boilerplate:check']],
  ['npm', ['run', 'plugin:phpdoc:check']],
  ['npm', ['run', 'plugin:marketplace:check']],
  ['npm', ['run', 'manifests:check']],
  ['npm', ['run', 'test:static']]
];

if (includePackage) {
  checks.push(['npm', ['run', 'plugin:package', '--', path.join(os.tmpdir(), `moodlia-release-check-${process.pid}`)]]);
  checks.push(['npm', ['run', 'release:artifacts']]);
}

for (const [command, args] of checks) {
  await run(command, args);
}

console.log('MoodlIA release checks completed.');

function run(command, args) {
  const label = `${command} ${args.join(' ')}`;
  console.log(`\n> ${label}`);
  const resolved = resolveCommand(command, args);

  return new Promise((resolve, reject) => {
    const child = spawn(resolved.command, resolved.args, {
      cwd: process.cwd(),
      stdio: 'inherit',
      shell: false
    });

    child.on('error', reject);
    child.on('exit', (code) => {
      if (code === 0) {
        resolve();
        return;
      }

      reject(new Error(`${label} exited with code ${code ?? 1}`));
    });
  });
}

function resolveCommand(command, args) {
  if (command === 'npm') {
    if (!process.env.npm_execpath) {
      throw new Error('Run release checks through npm run release:check.');
    }
    return {
      command: process.execPath,
      args: [process.env.npm_execpath, ...args]
    };
  }

  return { command, args };
}
