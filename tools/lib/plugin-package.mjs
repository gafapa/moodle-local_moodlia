import fs from 'node:fs/promises';
import path from 'node:path';

export const pluginDirectories = ['classes', 'db', 'lang', 'pix'];
export const pluginFiles = [
  'CHANGES.md',
  'LICENSE',
  'README.md',
  'SECURITY.md',
  'mcp.php',
  'version.php'
];

export async function copyPluginPackage(source, target) {
  await fs.mkdir(target, { recursive: true });

  for (const directory of pluginDirectories) {
    await fs.cp(path.join(source, directory), path.join(target, directory), { recursive: true });
  }

  for (const file of pluginFiles) {
    await fs.copyFile(path.join(source, file), path.join(target, file));
  }

  const phpTests = (await fs.readdir(path.join(source, 'tests'), { withFileTypes: true }))
    .filter((entry) => entry.isFile() && entry.name.endsWith('.php'))
    .map((entry) => entry.name);

  if (phpTests.length > 0) {
    const testTarget = path.join(target, 'tests');
    await fs.mkdir(testTarget, { recursive: true });
    for (const testFile of phpTests) {
      await fs.copyFile(path.join(source, 'tests', testFile), path.join(testTarget, testFile));
    }
  }
}
