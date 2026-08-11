# Npm Package

The public npm package is named `moodlia`.

It is a consumer package for the Node CLI and shared REST/MCP client. It is not the Moodle plugin package and it is not the development repository.

## Purpose

The npm package lets external users automate an installed MoodlIA Moodle site from Node.js or a shell:

- Run `moodlia <command>` from a terminal or CI job.
- Import the REST or MCP client from Node.js code.
- Read the publishable operation contract and generated TypeScript declarations.

The CLI calls Moodle REST directly through `/webservice/rest/server.php`. Node consumers may use the same contract through REST or the plugin endpoint at `/local/moodlia/mcp.php`. Neither transport requires a browser session, and the package does not include deployment or test automation.

User-facing CLI examples are documented in `docs/cli-usage.md` and summarized in the generated package README.

## Published Contents

The generated package under `packages/moodlia` must contain only:

```text
package.json
README.md
LICENSE
cli/moodlia.mjs
client/moodle-rest-client.mjs
client/moodle-rest-client.d.ts
client/generated/operation-types.d.ts
contract/operations.json
```

The npm package intentionally excludes:

- Moodle plugin PHP source.
- SFTP, Docker, and server deployment scripts.
- Browser tests, smoke tests, test fixtures, screenshots, and reports.
- Local env files and credentials.
- Developer-only tools.
- Server-side MCP manifests and transport metadata.

## Source Of Truth

Do not edit `packages/moodlia` manually.

The package is generated from the development repository:

```text
npm run npm:sync
```

The sync command copies and adapts:

- `cli/moodle-mcp.mjs` to `cli/moodlia.mjs`.
- `client/moodle-rest-client.mjs`.
- `client/moodle-rest-client.d.ts`.
- `client/generated/operation-types.d.ts`.
- `contract/operations.json`, filtered to the public CLI/client surface.
- A generated package README.
- A generated package license notice.

Check drift with:

```text
npm run npm:sync:check
```

## Package Metadata

The generated `package.json` includes:

- `name: moodlia`.
- Version copied from the root development package.
- Public GPL-3.0-or-later license metadata.
- `bin.moodlia` mapped to `cli/moodlia.mjs`.
- `main`, `types`, and `exports` for client imports.
- Repository, bugs, and homepage URLs pointing to `gafapa/moodle-local_moodlia`.
- `files` limited to the publishable runtime surface.
- `engines.node >= 22`.

## Runtime Configuration

External users configure the package with:

```text
MOODLE_BASE_URL=https://moodle.example.edu
MOODLE_REST_TOKEN=...
```

The CLI also reads a local `.env` file from the current working directory and from the installed package root when present.

The programmatic MCP client accepts the same token as `token` and either `baseUrl` or an explicit `endpoint`. The CLI remains REST-based.

Tokens must never be committed, embedded in examples, passed as CLI arguments, or published to npm.

## Verification

Before publishing:

```text
npm run npm:sync
npm run npm:sync:check
npm run test:static
npm run npm:pack:dry-run
```

The static package test verifies that:

- The package is named `moodlia`.
- The binary is `moodlia`.
- Only the approved files are present.
- No development-only tooling references are present.
- The public operation contract does not expose unpublished transport metadata.

The dry-run pack command shows the exact files and package size npm will publish.

## Publish

Publish from the generated package directory only:

```text
cd packages/moodlia
npm publish --access public
```

Use an npm authentication method that satisfies the account security policy, such as a granular automation token with publish permission and the required two-factor-authentication mode.

Never paste npm tokens into source files, documentation, logs, or issue comments.

## Versioning

The npm version follows the root project version.

Increment the root version when:

- A command is added or removed.
- A parameter or return shape changes.
- The REST/MCP client import surface changes.
- The required Node.js version changes.
- The package README or published file list changes in a release-worthy way.

Run `npm run npm:sync` after changing the root version.
