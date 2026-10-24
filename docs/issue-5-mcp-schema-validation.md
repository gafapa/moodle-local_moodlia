# Issue #5: empty MCP schema properties

## Failure and correction

The reporter's screenshot shows HTTP 400 and `[] is not of type "object"` for
`mcp__moodlia__get_current_user`. The PHP manifest reproduced that exact container
mismatch: `self::schema([])` produced `"properties":[]`. This rejects tool
discovery before the requested quiz operation runs, while the REST-based CLI
does not consume that catalog.

The shared manifest now emits `new stdClass()` for empty properties so PHP
serializes `{}`. Non-empty maps, required parameter lists and
`additionalProperties: false` retain their existing meanings. The argument
normalizer casts the schema map to an array for internal lookup. No operation,
permission, authentication or transport configuration changes are required.

The two affected no-argument tools are `get_current_user` and
`get_moodlia_status`. WebMCP already had a defensive conversion; it now receives
the correct shared descriptors directly. Its existing fallback is retained.

## Local validation on 24 October 2026

- Reproduced the original PHP-serialized `get_current_user` catalog entry.
- `npm run check`: passed all checks, including PHP syntax on 582 files,
  contract/generated-manifest parity and 48 tests, with no skipped tests.
- New Node regressions invoke actual PHP without a Moodle database, check every
  one of the 250 serialized tool schemas, both no-argument tools and argument
  normalization before and after a JSON round trip.
- `npm run test:webmcp:browser`: native Chromium accepted all 250 operation
  descriptors plus navigation; fixture execution and same-tab navigation passed.
- Native Moodle PHPUnit regressions were added for catalog serialization and
  empty-argument normalization. Their execution requires the Moodle CI matrix;
  they are not claimed as locally executed against a Moodle installation.

PHP was provided through `MOODLE_SERVER_PHP` using an existing local portable
runtime. No computer-wide settings, credentials, production Moodle data or
client configuration were changed. The reporter's private Claude Code setup was
not accessed. Install a build containing this unreleased fix and reconnect or
restart the MCP client to reload its catalog. This document does not claim an
end-to-end Claude Code or production deployment verification.
