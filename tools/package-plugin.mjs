import fs from 'node:fs/promises';
import path from 'node:path';
import { getEnv, loadEnvFile } from '../tests/helpers/env.mjs';
import { fromRoot } from '../tests/helpers/paths.mjs';

loadEnvFile();

const source = fromRoot(getEnv('LOCAL_PLUGIN_SOURCE') || 'plugin/moodlia');
const target = process.argv[2]
  ? path.resolve(process.argv[2])
  : path.resolve(getEnv('LOCAL_PLUGIN_PACKAGE_PATH') || 'D:/tmp/moodlia');

if (source === target) {
  throw new Error('LOCAL_PLUGIN_PACKAGE_PATH must not be the same directory as LOCAL_PLUGIN_SOURCE.');
}

await removePackageTarget(target);
await fs.mkdir(path.dirname(target), { recursive: true });
await fs.cp(source, target, {
  recursive: true,
  filter: (entry) => {
    const name = path.basename(entry);
    return !['.env', 'node_modules', 'vendor', 'test-results', 'playwright-report'].includes(name);
  }
});

console.log(`Packaged plugin from ${source} to ${target}`);

async function removePackageTarget(targetPath) {
  await chmodTree(targetPath).catch(() => {});

  const delays = [100, 250, 500, 1000];
  for (let attempt = 0; attempt <= delays.length; attempt += 1) {
    try {
      await fs.rm(targetPath, { recursive: true, force: true, maxRetries: 3, retryDelay: 100 });
      return;
    } catch (error) {
      if (!isTransientRemoveError(error) || attempt === delays.length) {
        throw new Error(
          `Unable to remove existing package target ${targetPath}: ${error.message}. ` +
          'Close processes using that directory or set LOCAL_PLUGIN_PACKAGE_PATH to a clean writable directory.'
        );
      }

      await sleep(delays[attempt]);
      await chmodTree(targetPath).catch(() => {});
    }
  }
}

async function chmodTree(targetPath) {
  const stats = await fs.lstat(targetPath);
  await fs.chmod(targetPath, stats.isDirectory() ? 0o777 : 0o666);

  if (!stats.isDirectory()) {
    return;
  }

  const entries = await fs.readdir(targetPath);
  await Promise.all(entries.map((entry) => chmodTree(path.join(targetPath, entry))));
}

function isTransientRemoveError(error) {
  return ['EBUSY', 'ENOTEMPTY', 'EPERM', 'EACCES'].includes(error?.code);
}

function sleep(milliseconds) {
  return new Promise((resolve) => {
    setTimeout(resolve, milliseconds);
  });
}
