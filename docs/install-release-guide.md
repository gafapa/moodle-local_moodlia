# Install And Release Guide

This guide describes how to install, verify, and release MoodlIA in another Moodle instance.

## Moodle Marketplace Preflight

The Marketplace package is built from the repository root and must remain a
standalone, installable Moodle local plugin. Before creating or uploading a
version, run:

```text
npm run plugin:boilerplate:check
npm run plugin:marketplace:check
npm run release:artifacts
npm run release:check
```

Upload the resulting `empaquetado/local_moodlia-<release>.zip`. The archive must
contain a single `moodlia/` root, declare `local_moodlia` in `version.php`, and
include `LICENSE`, `README.md`, the English language pack, and the plugin icon.
Do not add an empty `db/install.xml`; MoodlIA owns no database tables.

The `Moodle Plugin CI` GitHub Actions workflow runs the same tool family used by
Marketplace against Moodle 5.2 and PHP 8.2/8.4 on PostgreSQL and MariaDB. A
release must not be uploaded while any matrix job is failing.

Marketplace listing metadata:

- Source code: https://github.com/gafapa/moodle-local_moodlia
- Issue tracker: https://github.com/gafapa/moodle-local_moodlia/issues
- Documentation: https://github.com/gafapa/moodle-local_moodlia#readme
- Component: `local_moodlia`
- Plugin type: Local plugin
- Directory name: `moodlia`

## Versioning Policy

The Moodle plugin and public npm client use independent semantic release streams and repositories. `version.php` is authoritative for this Moodle plugin. The `package.json` in [gafapa/moodlia-cli](https://github.com/gafapa/moodlia-cli) is authoritative for the public npm package.

| Release stream | Current version | Source of truth | Artifact |
| --- | ---: | --- | --- |
| Moodle plugin | `0.1.190` | `version.php` | `local_moodlia-0.1.190.zip` |
| npm CLI/client | `0.1.20` | `gafapa/moodlia-cli` `package.json` | `moodlia@0.1.20` |

These numbers are not expected to match. Release communication and deployment records must always include the stream name, for example “MoodlIA Moodle plugin 0.1.190” or “moodlia npm 0.1.20”, rather than an unqualified version.

## Release Scope

MoodlIA is a Moodle local plugin with component `local_moodlia` and folder name `moodlia`.

The first release uses Moodle core structures only:

- No plugin-owned database tables.
- No `db/install.xml`.
- No raw SQL; Moodle-owned records are accessed through Moodle's cross-database DML API.
- REST functions are declared in `db/services.php`.
- MCP uses the same Moodle REST token and the same canonical operations.
- The Node CLI calls Moodle REST directly.

## Prerequisites

The target Moodle instance must provide:

- Moodle web access.
- Moodle admin access for installing/upgrading the plugin.
- A web service token authorised for the `local_moodlia` external service.
- SFTP/SSH or equivalent file deployment access.
- PHP CLI access for Moodle upgrade and cache purge, or admin web access to trigger upgrade.

Recommended local tools:

- Node.js 22 or newer.
- npm.
- WinSCP on Windows when using a `.ppk` private key.
- Playwright browsers for UI verification.

## Environment

Create a target-specific env file from `.env.example`:

```text
MOODLE_BASE_URL=https://moodle.example.edu
MOODLE_USERNAME=admin
MOODLE_PASSWORD=...
MOODLE_REST_SERVICE=local_moodlia
MOODLE_REST_TOKEN=...
MOODLE_MCP_ENDPOINT=https://moodle.example.edu/local/moodlia/mcp.php
PLAYWRIGHT_BASE_URL=https://moodle.example.edu
SFTP_HOST=example.edu
SFTP_PORT=22
SFTP_USER=ubuntu
SFTP_KEY_PATH=C:\path\to\key.ppk
LOCAL_PLUGIN_SOURCE=.
LOCAL_PLUGIN_PACKAGE_PATH=D:\tmp\moodlia
SFTP_REMOTE_UPLOAD_PATH=/tmp/moodlia
DEPLOY_MODE=direct
MOODLE_SERVER_ROOT=/var/www/html
MOODLE_SERVER_PLUGIN_PATH=/var/www/html/local/moodlia
MOODLE_SERVER_PHP=php
```

For the Docker-based development or staging target, set `DEPLOY_MODE=docker` and add:

```text
MOODLE_DOCKER_CONTAINER=moodle
MOODLE_CONTAINER_CLI_ROOT=/var/www/html
MOODLE_CONTAINER_ROOT=/var/www/html/public
MOODLE_CONTAINER_PLUGIN_PATH=/var/www/html/public/local/moodlia
```

Do not commit env files that contain credentials or tokens.

## Preflight

Run the local release check before uploading:

```text
npm install
npm run release:check
```

The release check validates all JavaScript syntax recursively, PHP syntax when a PHP runtime is available, generated manifests, generated TypeScript operation types, static parity, forbidden database-access patterns, and plugin packaging into a temporary directory. CI makes PHP syntax validation mandatory with PHP 8.2.

The repository also includes a GitHub Actions workflow for checks that do not need a remote Moodle instance. It runs npm package mirror drift checks, the release preflight, plugin packaging, and project website tests on pushes and pull requests. Remote smoke and browser verification still run manually against a configured Moodle target.

Use a faster preflight while iterating:

```text
node tools/release-check.mjs --skip-package
```

Run the protected-target gate when validating a production-like Moodle site where generated test courses are not acceptable:

```text
npm run release:protected
```

This gate is intentionally non-destructive. It runs local static and npm package sync checks, validates the protected smoke syntax, and calls only read-only Moodle operations across REST, MCP, and CLI (`get_moodlia_status`, `get_current_user`, `get_courses`, and `tools/list`) plus a missing-token MCP negative check.

When server file access is available, include server-side PHP syntax validation:

```text
npm run release:protected:php
```

## Package

Create the deployable plugin folder:

```text
npm run plugin:package
```

By default this copies only the installable plugin files from the repository
root to `LOCAL_PLUGIN_PACKAGE_PATH`.

The package excludes development-only folders and local secrets.

## Npm Package

Prepare the public CLI/client package:

```text
npm run npm:sync
npm run npm:sync:check
npm run npm:pack:dry-run
```

The generated npm package lives in:

```text
packages/moodlia
```

It is named `moodlia` and publishes only the external consumer surface:

- `cli/moodlia.mjs`.
- `client/moodle-rest-client.mjs`.
- `client/moodle-rest-client.d.ts`.
- `client/generated/operation-types.d.ts`.
- `contract/operations.json`.
- `README.md`.
- `LICENSE`.
- `package.json`.

It does not include the Moodle plugin PHP source, SFTP deployment scripts, browser automation, smoke tests, reports, local env files, or unpublished transport metadata.

Publish from the generated package directory only:

```text
cd packages/moodlia
npm publish --access public
```

Use an npm authentication method that satisfies the account's two-factor-authentication policy. Never store npm tokens in this repository.

## Deploy To A Standard Moodle Server

The generic deployment target is a normal Moodle codebase on a server, not a Docker container. The plugin folder must be copied to Moodle's `local` directory:

```text
<moodle-root>/local/moodlia
```

Typical examples:

```text
/var/www/html/local/moodlia
/var/www/moodle/local/moodlia
/home/example/public_html/moodle/local/moodlia
```

After packaging locally, upload the packaged `moodlia` folder with SFTP, rsync, SCP, your hosting file manager, or a CI artifact. Then run the Moodle upgrade from the Moodle root:

```text
cd /var/www/html
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

If the web server user owns the Moodle files, run those commands as that user or with the hosting provider's recommended PHP binary. If CLI access is unavailable, open Moodle as an administrator and visit:

```text
https://moodle.example.edu/admin/index.php
```

The repository can print direct-server commands from your `.env` file:

```text
npm run plugin:package
npm run deploy:commands
```

With `DEPLOY_MODE=direct`, `deploy:commands` uses `MOODLE_SERVER_ROOT`, `MOODLE_SERVER_PLUGIN_PATH`, and `MOODLE_SERVER_PHP`.

## Docker Development Deployment

The configured development/staging deployment uses WinSCP plus a Docker-hosted Moodle instance. Use this only when the target Moodle codebase is inside a container:

```text
npm run deploy:winscp:test
npm run deploy:winscp
```

The final plugin path in that container is:

```text
/var/www/html/public/local/moodlia
```

The Moodle upgrade command must run from the Moodle root that contains `admin/cli`. In the configured Docker target this is:

```text
sudo docker exec -w /var/www/html moodle php admin/cli/upgrade.php --non-interactive
sudo docker exec -w /var/www/html moodle php admin/cli/purge_caches.php
```

The plugin path and CLI root are allowed to differ in this container layout. The plugin is installed under `/var/www/html/public/local/moodlia`; the upgrade CLI is run from whichever directory exposes `admin/cli`, commonly `/var/www/html`.

Run PHP syntax checks on the server after copying or deploying the plugin:

```text
npm run plugin:php:lint:server
```

PHP checks should use the target Moodle server runtime. Do not treat local Windows PHP availability as a release requirement.

## Verify

Run static checks locally:

```text
npm run test:static
npm run manifests:check
npm run types:check
```

Run focused remote smoke tests:

```text
node --test tests/smoke/api.test.mjs
node --test tests/smoke/protected-target-readonly.test.mjs
node --test tests/smoke/course-integrated-subelements.test.mjs
node --test tests/smoke/transport-parity.test.mjs
node --test tests/smoke/module-completion-matrix.test.mjs
node --test tests/smoke/module-custom-completion-rules.test.mjs
```

Run broad remote smoke tests when the target can tolerate generated test data:

```text
npm run test:smoke
```

Run browser verification:

```text
npm run test:browser
```

Browser tests require `MOODLE_USERNAME`, `MOODLE_PASSWORD`, and either `PLAYWRIGHT_BASE_URL` or `MOODLE_BASE_URL`.

Before or after broad remote verification, inspect generated Moodle data:

```text
npm run moodle:cleanup-generated
```

Delete matched generated courses and empty generated course categories only when the dry-run output is expected:

```text
npm run moodle:cleanup-generated:execute
```

The cleanup command only targets data with MoodlIA test markers: course full names starting with `MoodlIA`, course short names starting with `moodlia-`, and course category names starting with `MoodlIA`.

## Demo Course

Create a rich demo course without deleting existing courses:

```text
npm run moodle:create-demo-course
```

The output is JSON with the generated course id, category id, activity ids, question categories, question ids, quiz slots, file ids, and verification payloads.

Only use the reset command on disposable Moodle instances:

```text
npm run moodle:reset-full-course
```

This deletes every manageable course returned by the API before creating the demo course.

## Rollback

Before each release:

- Record the currently deployed plugin version.
- Keep a copy of the previously deployed `local/moodlia` folder.
- Confirm whether the release contains schema changes.

Current MoodlIA releases do not create plugin-owned schema, so rollback is file-based:

1. Re-upload the previous `moodlia` folder.
2. Run Moodle upgrade.
3. Purge caches.
4. Run the focused smoke tests.

Moodle does not support arbitrary plugin downgrades as a normal workflow. Plan rollback before deploying.

## Release Checklist

- `npm run release:check` passes.
- Deployment target paths are confirmed.
- Secrets are not committed.
- Plugin is packaged from the repository root.
- Public npm package is synced and dry-run packed when the release includes CLI/client changes.
- Plugin is deployed to `/local/moodlia`.
- Moodle upgrade and cache purge complete.
- REST smoke passes.
- MCP smoke or transport parity passes.
- CLI smoke or transport parity passes.
- Browser verification passes for generated Moodle-visible state.
- Generated failed-test courses are cleaned up.
