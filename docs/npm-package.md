# Npm Package

The public npm package is named `moodlia`.

It is the consumer package for the Node CLI and reusable REST client. It is not the Moodle plugin package.

## Purpose

The npm package lets external users automate an installed MoodlIA Moodle site from Node.js or a shell:

- Run `moodlia <command>` from a terminal or CI job.
- Import the REST client from Node.js code.
- Read the publishable operation contract and generated TypeScript declarations.

The CLI and Node client call Moodle REST directly through `/webservice/rest/server.php`. MCP remains a separate plugin integration at `/local/moodlia/mcp.php` and is not bundled into the npm package. REST does not require a browser session, and the package does not include deployment or test automation.

User-facing CLI examples are documented in `docs/cli-usage.md` and summarized in the generated package README.

## Published Contents

The published package from the `moodlia-cli` repository must contain only:

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

The dedicated `moodlia-cli` repository is the source of truth for the executable, REST client, declarations, and public package documentation. Its bundled operation contract must stay aligned with the Moodle plugin contract.

## Package Metadata

The `package.json` includes:

- `name: moodlia`.
- A version managed independently from the Moodle plugin.
- Public GPL-3.0-or-later license metadata.
- `bin.moodlia` mapped to `cli/moodlia.mjs`.
- `main`, `types`, and `exports` for client imports.
- Repository and bug URLs pointing to `gafapa/moodlia-cli`.
- `files` limited to the publishable runtime surface.
- `engines.node >= 22`.

## Runtime Configuration

External users configure the package with:

```text
MOODLE_BASE_URL=https://moodle.example.edu
MOODLE_REST_TOKEN=...
```

The CLI also reads a local `.env` file from the current working directory and from the installed package root when present.

The programmatic REST client accepts the same `baseUrl` and `token` values as the CLI environment configuration.

Tokens must never be committed, embedded in examples, passed as CLI arguments, or published to npm.

## Verification

Before publishing:

```text
npm run check
npm run pack:check
```

The static package test verifies that:

- The package is named `moodlia`.
- The binary is `moodlia`.
- Only the approved files are present.
- No development-only tooling references are present.
- The public operation contract does not expose unpublished transport metadata.

The dry-run pack command shows the exact files and package size npm will publish.

## Publish

Publish from the `moodlia-cli` repository root only:

```text
npm publish --access public
```

Use an npm authentication method that satisfies the account security policy, such as a granular automation token with publish permission and the required two-factor-authentication mode.

Never paste npm tokens into source files, documentation, logs, or issue comments.

## Versioning

The npm package has an independent version. Use a minor version change for breaking public API changes while the package remains below `1.0.0`.

Increment the root version when:

- A command is added or removed.
- A parameter or return shape changes.
- The REST client import surface changes.
- The required Node.js version changes.
- The package README or published file list changes in a release-worthy way.

Keep `package.json` and `package-lock.json` versions aligned.
