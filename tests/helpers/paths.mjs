import { fileURLToPath } from 'node:url';
import path from 'node:path';

export const repositoryRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');

export function fromRoot(...segments) {
  return path.join(repositoryRoot, ...segments);
}
