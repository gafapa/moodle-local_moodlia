import fs from 'node:fs';
import path from 'node:path';
import { zipSync } from 'fflate';

export function createZipFromDirectory({
  sourceDirectory,
  archiveRoot = path.basename(sourceDirectory),
  sourceDateEpoch = process.env.SOURCE_DATE_EPOCH
} = {}) {
  if (!sourceDirectory || !fs.statSync(sourceDirectory, { throwIfNoEntry: false })?.isDirectory()) {
    throw new Error(`ZIP source directory not found: ${sourceDirectory ?? '(missing)'}`);
  }

  const entries = Object.create(null);
  const archiveTimestamp = resolveArchiveTimestamp(sourceDateEpoch);

  for (const filePath of collectFiles(sourceDirectory)) {
    const relativePath = path.relative(sourceDirectory, filePath).replaceAll(path.sep, '/');
    const archiveName = archiveRoot ? `${archiveRoot}/${relativePath}` : relativePath;
    entries[archiveName] = [
      fs.readFileSync(filePath),
      {
        mtime: archiveTimestamp,
        os: 3,
        attrs: 0o100644 << 16
      }
    ];
  }

  return Buffer.from(zipSync(entries, {
    level: 9,
    mtime: archiveTimestamp,
    os: 3
  }));
}

function collectFiles(directoryPath) {
  const files = [];
  for (const entry of fs.readdirSync(directoryPath, { withFileTypes: true })) {
    const entryPath = path.join(directoryPath, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectFiles(entryPath));
    } else if (entry.isFile()) {
      files.push(entryPath);
    }
  }
  return files.sort((firstPath, secondPath) => firstPath.localeCompare(secondPath, 'en'));
}

function resolveArchiveTimestamp(sourceDateEpoch) {
  const parsedEpoch = Number.parseInt(String(sourceDateEpoch ?? ''), 10);
  const minimumEpoch = Date.UTC(1980, 0, 1) / 1000;
  const maximumEpoch = Date.UTC(2107, 11, 31, 23, 59, 58) / 1000;
  const requestedEpoch = Number.isInteger(parsedEpoch) ? parsedEpoch : minimumEpoch;
  const clampedEpoch = Math.min(maximumEpoch, Math.max(minimumEpoch, requestedEpoch));
  return new Date(clampedEpoch * 1000);
}
