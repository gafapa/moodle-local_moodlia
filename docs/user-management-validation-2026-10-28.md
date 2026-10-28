# User management validation - 2026-10-28

## Scope

Local source changes in `moodlia-moodle-plugin` and `moodlia-cli`; plugin build `2026102800`. No production deployment, public release, or real user data was used. Disposable Moodle installations use MariaDB 11.4.8 and PHP 8.3.35 on Windows.

## Checks

- Plugin `npm run check`: 51 static tests passed, including actual PHP MCP catalog serialization, JSON Schema validation, service/contract parity, syntax, generated manifests, and package structure checks.
- CLI `npm run check`: 175 tests passed, including contract-driven command coverage, generated TypeScript checks, exact search filters, and true/false suspension encoding over a local synthetic REST server.
- CLI `npm run contract:check`: bundled contract matches all 252 plugin operations.
- CLI `npm run pack:check`: package dry run passed.
- Moodle 4.5.14+ native PHPUnit: 7 user management tests and 34 assertions passed.
- Moodle 5.2.3+ native PHPUnit: 7 user management tests and 34 assertions passed.

Native tests exercise the external function boundary for search, profile creation/update/deletion, suspension/reactivation, database readback, page limits, account protections, and permission denial. Literal SQL wildcard input is checked. The list query selects all Moodle name fields so `fullname()` works without debugging warnings.

The account lifecycle tests call Moodle external functions inside PHPUnit. CLI HTTP checks use a synthetic server; MCP checks validate the published catalog and existing transport mechanics. An authenticated end-to-end account lifecycle over an installed production HTTP endpoint has not been performed.

## Activation

Run Moodle's normal plugin upgrade to register the new service functions, use the matching CLI source/package, and reconnect MCP clients. The release label remains unchanged until a separately authorized publication.
