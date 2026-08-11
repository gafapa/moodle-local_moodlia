import fs from 'node:fs';
import path from 'node:path';
import { fromRoot } from './paths.mjs';

const loaded = new Set();

export function loadEnvFile(filename = '.env.test') {
  const filePath = fromRoot(filename);
  if (loaded.has(filePath) || !fs.existsSync(filePath)) {
    return;
  }

  const raw = fs.readFileSync(filePath, 'utf8');
  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }

    const separatorIndex = trimmed.indexOf('=');
    if (separatorIndex === -1) {
      continue;
    }

    const key = trimmed.slice(0, separatorIndex).trim();
    const value = trimmed.slice(separatorIndex + 1).trim();
    if (key && process.env[key] === undefined) {
      process.env[key] = value;
    }
  }

  loaded.add(filePath);
}

export function getEnv(name) {
  loadEnvFile();
  return process.env[name];
}

export function requireEnv(names) {
  loadEnvFile();
  return names.every((name) => Boolean(process.env[name]));
}

export function getTimeout() {
  const value = Number(getEnv('TEST_TIMEOUT_MS') ?? 30000);
  return Number.isFinite(value) && value > 0 ? value : 30000;
}

export function resolveCliCommand() {
  const value = getEnv('MOODLE_CLI_BIN');
  if (!value) {
    return null;
  }

  const normalized = value.trim();
  if (!normalized) {
    return null;
  }

  if (normalized.includes(path.sep) || normalized.endsWith('.js') || normalized.endsWith('.mjs')) {
    return normalized;
  }

  return normalized;
}
