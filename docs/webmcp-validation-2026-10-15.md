# Native WebMCP Validation — 2026-10-15

Two disposable, synthetic Docker profiles were exercised on the explicitly
authorized Oracle ARM64 host. No production Moodle site was modified.

| Profile | PHP / database | Full native suite | WebMCP bridge | Real browser/CLI checks |
| --- | --- | --- | --- | --- |
| Moodle 4.5.15 | PHP 8.3.15 / PostgreSQL 16.15 | 103 tests, 1,509 assertions | 20 tests, 300 assertions | 11 passed |
| Moodle 5.2.4 | PHP 8.3.15 / PostgreSQL 17.11 | 103 tests, 1,471 assertions | 20 tests, 300 assertions | 11 passed |

The bridge's measured Moodle 5.2 statement coverage is 97.48% (116/119).
`tools/check-webmcp-coverage.mjs` now enforces a 95% minimum and fails closed on
missing, malformed, duplicated, inconsistent, or insufficient coverage data.
The Moodle compatibility workflow runs the bridge suite with PCOV and retains
the Clover, JUnit, and validated JSON results. Hosted CI has not been executed
for this working tree.

The full Moodle 4.5 suite and the focused bridge suite passed cleanly. Its
additional coverage-report generation did not finish, so no Moodle 4.5 coverage
percentage is claimed. The CI report command now uses an explicit 1 GiB memory
budget. This adjustment still needs hosted matrix verification.

Moodle 5.2's full suite retains five existing forum/quiz notices and twelve
PHPUnit metadata deprecations. Its focused WebMCP suite is clean.

The real browser paths cover synthetic login, all 251 tool registrations,
session/CSRF enforcement, malformed requests, Page creation, object and boolean
inputs, integer-array completion criteria, section uploads and byte-verified
downloads, native same-tab navigation, registration after navigation, and
student restrictions on writes, gradebooks, and assignment grading. The
separate fictional browser fixture invokes all 250 CLI adapters; it is not a
claim that 250 Moodle operations were invoked through WebMCP on each real site.

The expanded checks found a completion-response bug: native database criterion
types can be numeric strings. The serializer now normalizes them before
comparing with integer constants. Tests check the actual response ids and grade
criterion in addition to persisted database rows. The bridge also rejects
non-empty lists in object fields, and the AMD client rejects a cross-origin
endpoint before sending a session key. Static/client/coverage-gate tests total
46 passing cases.

The functional profiles used the working tree before the final patch metadata
increment to 0.1.219. Later client endpoint-origin validation passed local static
and native Chromium fixture checks. The full supported-version CI matrix was
not run during this session.

Cleanup was verified at `2026-10-15T19:06:22Z`: zero owned containers and
networks, no lab directory or listener, and removal of anonymous volumes and
owned temporary files. Existing images and production volumes were retained.
The SSH tunnel, synthetic credentials, and synthetic browser profile were also
removed. Every initially active production service remained active.

The CLI companion's unit coverage is 96.70% overall and 92.73% across all five
browser runtime modules, with line gates at 95% and 90%. It has 174 passing tests;
the later global coverage pass added 63 tests using local synthetic fixtures,
without rerunning the removed Moodle sites. Global branch/function coverage is
93.82%/90.29%, with gates at 90%/85%, verified on Node 22.13.0 and 24.18.0.
See [the structured evidence](evidence/webmcp-2026-10-15.json).
