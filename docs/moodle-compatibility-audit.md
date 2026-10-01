# Moodle compatibility audit — 2026-10-02

## Scope and support policy

The canonical contract contains 250 operations: 103 reads and 147 writes. The
supported core series are Moodle 4.5, 5.0, 5.1, 5.2, and preliminary Moodle 5.3
(`v5.3.0-beta`). Moodle versions below 4.5 are outside the plugin's declared
requirements. Qualification concerns the supported series, rather than every
historical Moodle release or every third-party extension and site configuration.

REST, MCP, and CLI share the operation classes. Native PHPUnit exercises the
Moodle behavior; external serialization checks validate the REST response schema;
generated manifest checks validate MCP and CLI contract parity. Contract parity
alone does not establish that an operation works against a particular core.

## Incident and corrections

The inspected `aula.gallego.top` runtime was Moodle 4.5.14, PHP 8.3.15, and
MoodlIA 0.1.214 (2026092400), on both disk and in the plugin database record.
Its deployed deletion implementation lacked the newer fallback and its module
API loader did not include the Resource local library.

| Area | Finding | Correction and regression evidence |
| --- | --- | --- |
| Activity deletion | Moodle 4.5 has no `cmactions::delete()`; production had older code | Retain the guarded `course_delete_module()` fallback; assert deletion removes the course module, activity instance, and context; reread course contents after REST deletion |
| Resource replacement | `resource_update_instance()` calls `resource_set_mainfile()` without loading its definition | Explicitly require Resource local library; exercise upload, replacement, and byte-for-byte download in separate HTTP requests |
| Page and URL updates | Their native update callbacks also depend on local libraries | Explicitly load their local libraries in the shared module API loader and exercise independent requests |
| Resource settings | Database display options require reconstruction into form fields | Normalize update form data; assert popup geometry, display flags, identity, main-file sort order, and revision |
| Graded activity creation | Native callbacks read an omitted `cmidnumber` form field | Supply the standard empty identifier default |
| Workshop creation | Submission and assessment grade categories were missing | Resolve the course's default grade category and provide both native form fields |
| Choice completion | Native update expects option arrays, option ids, and limits | Reconstruct from existing options; verify submitted responses and option ids survive |
| Assignment completion | Generic module form data omits submission/feedback plugin configuration | Reuse Assignment's prepared update data; compare plugin configuration before and after |
| Folder and Resource completion | Native callbacks expect a file-manager field | Provide the keep-existing-files default; verify retained Folder bytes |
| URL completion | Stored dynamic parameters differ from native form fields | Reconstruct parameter/variable pairs; verify serialized parameters survive |
| Forum, Glossary, Database completion | Native callbacks use a rating-time checkbox to retain date windows | Reconstruct the checkbox from existing rating dates |
| Lesson completion | Stored media filename is not a user draft item id | Prepare a real draft from the existing media area before invoking the native callback |
| Backup downloads | The previous unreleased correction removes an invalid `/0/` item-id URL segment | Include that correction in the deployment package; existing backup lifecycle tests qualify it |

## Qualification design

Previously, 155 operations were tagged for native API qualification. This audit
adds the remaining 95 reads: 92 use real course, learner, group, discussion,
glossary, question, file, and Workshop assessment fixtures and validate external
return schemas; three quiz attempt reads run within an actual learner attempt
lifecycle. Every contract operation must now retain an `api` qualification tag.

The independent-request REST suite creates a hidden temporary course, exercises
every declared activity type, updates common completion settings, replaces and
downloads a Resource, rejects a cross-course deletion, deletes activities
synchronously, and removes the temporary course in a `finally` block. Reads that
require complex entity or attempt ids are qualified by the native fixture tests.
Production regression mode restricts fixtures to Page and Resource activities.

| Moodle core | Minimum PHP profile | Upper PHP profile |
| --- | --- | --- |
| 4.5 | PHP 8.1, MariaDB 11.4 | PHP 8.3, PostgreSQL 16 |
| 5.0 | PHP 8.2, MariaDB 11.4 | PHP 8.4, PostgreSQL 16 |
| 5.1 | PHP 8.2, MariaDB 11.4 | PHP 8.4, PostgreSQL 16 |
| 5.2 | PHP 8.3, MariaDB 11.4 | PHP 8.4, PostgreSQL 16 |
| 5.3 beta | PHP 8.3, MariaDB 11.4 | PHP 8.4, PostgreSQL 17 |

Pull requests run one profile for each of the five core series. Main, nightly,
and default manual runs execute all ten profiles. Each profile runs both native
PHPUnit and the independent REST suite and retains their output as artifacts.
Diagnostic `oldest` and `newest` scopes make failures reproducible without
substituting for the final full qualification.

## Source review and intentional differences

Official core source snapshots reviewed:

| Series | Commit |
| --- | --- |
| 4.5 | `1990fc9201b23e0b0c8fdc44d537840a85d73503` |
| 5.0 | `e09c92fa47c4cefab6d49f3bac41e050534f6f1b` |
| 5.1 | `93aaa1919c648f120312b2438dd5271526f1e93f` |
| 5.2 | `16f374ba7822b0df914141e7a9031cc229c15679` |
| 5.3 beta | `e68a1418bea512dd5992c29eb9e36570a3844e94` |

Symbol-presence triage covered the plugin's 547 PHP class files and 154
referenced core declaration names. This is a lexical check, not PHP type or
autoload resolution: `new assign()` was a constructor false positive, not a
missing core function. The executable matrix provides stronger evidence.
Explicit dependency loading and native form callbacks were reviewed separately.

- Moodle 4.5 uses the legacy course context for `course_shared` question banks.
  Standalone Qbank activities and `quiz_private` banks require Moodle 5.0+ and
  return explicit capability errors on 4.5.
- Group-mode changes fall back to `set_coursemodule_groupmode()` when core
  course-format actions do not offer `set_groupmode()`.
- Moodle 5.1+ reorganizes the public tree. Deployment and test runners resolve
  the effective plugin and web roots; plugin dependencies use `$CFG->dirroot`.
- Native grade form values may contain localized decimals. Existing numeric
  normalization remains necessary on strict PostgreSQL installations.
- Completion changes still require explicit `reset_completion_states=true`,
  because Moodle may reset existing completion state even when other activity
  settings and learner submissions remain intact.

## Operational verification

Before deployment, a complete aula backup was taken at
`20261001T221411Z`; the database, data directory, application tree, and protected
Docker configuration all passed their SHA-256 checks.

Final CI and production receipts are recorded below after qualification. Tests
use temporary fixtures; the original reported duplicate pages and course PDF
require their course/module ids and intended replacement file for a separate
content repair.
