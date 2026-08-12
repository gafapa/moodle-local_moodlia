# Contributing To MoodlIA

Thank you for helping improve the MoodlIA Moodle plugin.

## Before Opening A Change

- Use GitHub Issues for bugs and feature proposals.
- Do not report vulnerabilities in public issues; follow `SECURITY.md`.
- Keep documentation, code identifiers, and comments in English.
- Preserve Moodle's permission model and use public Moodle APIs where available.
- Do not add plugin-owned database tables or raw SQL without an approved design
  that explains privacy, upgrade, rollback, and cross-database behavior.

## Development Checks

Install the development dependency and run the local gate:

```bash
npm ci
npm run release:check
```

The local gate validates the operation contract, generated manifests, PHP
boilerplate, Marketplace metadata, static tests, package isolation, the release
ZIP, and its checksum. PHP syntax is mandatory in CI and optional locally when a
PHP runtime is unavailable.

GitHub Actions additionally installs Moodle 5.2 and runs Moodle Plugin CI on PHP
8.3 and 8.4 with PostgreSQL and MariaDB. Moodle coding errors always fail the
build; generated external schemas have a bounded allowance of 200 line-length
and test-coverage warnings.

## Pull Requests

- Keep each change focused.
- Add or update tests for behavior changes.
- Update `CHANGES.md` and user documentation when the public behavior changes.
- Increment `$plugin->version` and `$plugin->release` for a publishable release.
- Do not commit generated ZIP files, checksums, credentials, tokens, `.env`
  files, or `node_modules`.
