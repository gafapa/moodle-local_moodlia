# Changelog

All notable changes to the MoodlIA Moodle plugin are documented here.

## Unreleased

## 0.1.215 - 2026-09-24

- Groups: `create_group` and `update_group` accept `visibility`
  (`all`, `members`, `own`, `none`), `participation`,
  `description_format`, and `enrolment_key`. Responses report them, and
  `has_enrolment_key` instead of the key itself. Visibility cannot change
  while a group has members, participation is off for hidden groups, and
  enrolment keys are unique within a course. This closes the group-field gap
  found by Core-to-MoodlIA synchronization.
- Text formats: every `*_format` parameter accepts `html`, `plain`,
  `markdown`, and `moodle`. Book chapter and Lesson page `content_format`
  accept these names and keep the numeric Moodle constants as aliases.
- Embedded files: forum discussions and replies, glossary entries, Lesson
  pages, and wiki pages accept draft item ids for files referenced as `@@PLUGINFILE@@`
  (forum and glossary also for attachments). Forum replies accept
  `message_format`; updates keep the stored format when it is omitted.
  `create_module` accepts `options.intro_draft_item_id` and
  `options.intro_format` for any activity intro, and Lesson page responses
  report the real `files_count` and `files_size_total` instead of zero.
- The MCP tool list in `classes/mcp/manifest.php` is generated from the
  contract (`npm run manifests:generate`) and checked in CI, fixing 13 tool
  descriptions that had drifted.
- The contract's `tests` tags are now verified: REST parameter parity for all
  250 operations, `api` only where a PHPUnit test exercises the operation
  (31), and no `browser` claims without browser tests.

## 0.1.214 - 2026-09-24

- Fix MCP `tools/call` for object-typed arguments such as `options`,
  `criteria`, `answers`, and `definition`. They were flattened into indexed
  form fields (`options[0]`), so Moodle rejected `create_module`,
  `update_module`, and similar calls with `invalidparameter`
  ([#3](https://github.com/gafapa/moodle-local_moodlia/issues/3)). Arguments
  are now normalised against each tool's input schema and sent as JSON strings.
- Report the installed plugin release as the MCP `serverInfo.version` instead of
  a hard-coded value that had stayed at `0.1.207`.

## 0.1.213 - 2026-09-22

- Allow authenticated file downloads through the built-in MoodlIA service so
  synchronization can transfer editor and activity assets.

## 0.1.212 - 2026-09-22

- Publish Page editor-file URLs with the Page revision expected by Moodle's
  pluginfile endpoint while retaining storage item id `0` internally.
- Add regression coverage for authenticated Page asset manifests used by
  cross-site synchronization.

## 0.1.211 - 2026-09-21

- Import every file in an authenticated Book chapter draft as one editor-file publication.
- Preserve Unicode filenames and nested draft paths while returning the complete uploaded-file manifest.
- Add regression coverage for multi-file Book chapter updates used by cross-site synchronization.
- Add identity-preserving `update_page`, `update_label`, and `update_url` operations across REST, MCP, and CLI.
- Publish native editor files for Page, Text and media, and URL activities while preserving existing files and unrelated settings.
- Add cross-version regression coverage for typed content updates, dynamic URL parameters, and multi-file drafts.
- Expose portable raw section summaries and complete section editor-file manifests alongside rendered HTML.
- Expose separate assignment description and activity-instruction file manifests with Moodle content hashes.
- Publish every file from one section or assignment draft while preserving nested paths and unrelated existing files.
- Report contextual question-bank read and authoring evidence for adaptive synchronization.
- Report contextual Database-field and Feedback-item authoring evidence for adaptive synchronization.
- Report contextual course-completion configuration evidence for adaptive synchronization.
- Report contextual Quiz structure authoring evidence for adaptive synchronization.
- Export portable Lesson page definitions and report contextual Lesson authoring evidence.
- Report contextual gradebook authoring evidence for adaptive synchronization.

## 0.1.210 - 2026-09-21

- Corrected Moodle coding-standard boilerplate and method documentation for
  the synchronization capability and Workshop grading-form endpoints.
- Re-ran the full static, packaging, and Moodle compatibility release gates.

## 0.1.209 - 2026-09-21

- Added contextual synchronization capability evidence for category and course permissions without treating operation presence as authorization.
- Added portable Workshop grading-form export for accumulative, comments, number-of-errors, and rubric strategies, including rubrics with more than four levels.
- Added grouping membership, Book chapter file manifests, resource/folder content hashes, and advanced-grading options to synchronization reads.
- Extended the canonical contract and generated REST, CLI, and MCP manifests for adaptive cross-site synchronization.
- Validated installation build `2026092110` in disposable Moodle 4.5.14, 5.0.8, 5.1.5, 5.2.2, and 5.3 beta environments.

## 0.1.208 - 2026-09-21

- Extended declared support and CI through the official Moodle 5.3 beta line.

## 0.1.207 - 2026-09-21

- Extended the declared Moodle core compatibility range from Moodle 4.5 LTS
  through Moodle 5.2.
- Added Moodle Plugin CI coverage for Moodle 4.5, 5.0, 5.1, and 5.2 across
  each branch's supported PHP range, PostgreSQL, and MariaDB.
- Made the standalone question bank activity optional so Moodle 4.5 can use its
  course-context shared question bank without breaking unrelated operations.

## 0.1.206 - 2026-09-20

- Fixed `update_assignment` under locales that use a decimal comma. Moodle form
  defaults such as `gradepass=4,50` are converted back to numeric values before
  the module update API writes them to the gradebook.
- Added regression coverage for name-only assignment updates with a non-zero
  grade-to-pass value, preserving the grade item id and stored threshold.

## 0.1.205 - 2026-09-20

- Added `update_resource` across REST, MCP, and CLI so a File resource can
  replace its stored file without changing its course-module or instance id.
- Added streamed draft uploads, optional name and description updates, stored
  file metadata, and native backup/restore coverage for replaced resources.
- Added regression coverage proving that `course_shared` creates a standalone
  Moodle question bank module and that its categories survive backup/restore.

## 0.1.204 - 2026-09-19

- Preserved active advanced-grading methods, rubric definitions, grade items,
  grading settings, and assignment submission and feedback plugin settings
  when `update_assignment` changes authoring content.
- Added a Moodle regression test that updates an assignment with an active
  rubric and verifies stable rubric criteria, identifiers, grade configuration,
  and plugin settings.
- Added correlation identifiers and structured server-side diagnostics for
  assignment database-write failures without exposing SQL to REST, MCP, or CLI
  callers.

## 0.1.203 - 2026-09-15

- Added native Book chapter editor-file uploads to `create_book_chapter` and
  `update_book_chapter` across REST, MCP, and CLI contract surfaces.
- Stored uploaded files in `mod_book/chapter` under the chapter id, resolved
  `@@PLUGINFILE@@` references in rendered responses, preserved unrelated
  chapter files during updates, and added native backup/restore coverage.

## 0.1.202 - 2026-09-15

- Added `update_assignment` across REST, MCP, and CLI contract surfaces for
  changing an assignment name, description, or activity instructions without
  resetting unrelated assignment settings.
- Added native editor-file uploads to the assignment description and activity
  instruction areas, including uploaded-file metadata and Moodle backup/restore
  portability coverage.

## 0.1.201 - 2026-09-15

- Added native section summary file uploads to `update_section` across REST,
  MCP, and CLI contract surfaces, including uploaded-file metadata and Moodle
  backup/restore portability coverage.
- Added CLI documentation for combining a UTF-8 `--summary-file` with one
  streamed `--upload-file` without sending local paths to Moodle.
- Added an illustrated, end-to-end guide for installing the plugin ZIP through
  Moodle 5.2's web administration interface and verifying the installed
  component and version.
- Added an illustrated guide for creating a least-privilege MoodlIA service
  user, enabling REST, assigning the required system capabilities, authorising
  the external service, creating a protected token, and connecting through CLI
  or MCP.
- Added a detailed ChatGPT and Claude MCP client configuration guide covering
  secure token injection, direct client setup, safe verification, hosted-client
  authentication limits, troubleshooting, and token rotation.

## 0.1.200 - 2026-08-28

- Kept portable HTML section summaries intact in MoodlIA API responses so
  `<details>` and `<summary>` elements render for REST and MCP consumers.

## 0.1.199 - 2026-08-28

- Preserved the existing section summary format when a section summary is
  updated without an explicit format.
- Rendered HTML section summaries with Moodle's file URL rewriting, preserving
  existing section files and resolving `@@PLUGINFILE@@` references in responses.
- Added coverage for portable HTML `<details>` and `<summary>` section content.

## 0.1.198 - 2026-08-27

- Added the grade-pass and global course-completion operations to the default
  `local_moodlia` external service so existing authorised tokens can invoke
  them after the plugin upgrade.

## 0.1.197 - 2026-08-27

- Fixed the Moodle coding-style format of the grade-item validation condition.

## 0.1.196 - 2026-08-27

- Restored the complete Moodle source boilerplate in the new course-completion
  classes so the official Moodle coding-style check can run successfully.
- Made the GitHub release workflow idempotent when a release already exists.

## 0.1.195 - 2026-08-26

- Added course-total passing-grade configuration and global course completion
  criteria across REST, MCP, and the CLI contract.
- Added safe gradebook updates for module-owned grade items, including passing
  grades, category placement, aggregation weights, visibility, and locking.
- Expanded grade item and category responses with the settings required to
  verify gradebook and completion configuration.

## 0.1.194 - 2026-08-21

- Added explicit `html` and `plain` summary formats to section creation and
  updates across REST, MCP, and the CLI contract.
- Returned the stored section summary format from create and update operations.

## 0.1.193 - 2026-08-20

- Restored backups when Moodle reports warnings without fatal precheck errors.
- Returned Moodle restore precheck details through REST, MCP, and the CLI.
- Removed incomplete courses created by failed new-course restores.

## 0.1.192 - 2026-08-19

- Added Moodle core multipart draft uploads for the CLI and Node client.
- Changed large file operations to consume a `draft_item_id` without Base64
  expansion in PHP memory.
- Retained `upload_reference` as a backward-compatible legacy input.
- Enabled file uploads on the MoodlIA external service and added draft ownership
  validation.

## 0.1.191 - 2026-08-19

- Removed the 2 MB, 20 MB, and 32 MB MoodlIA upload and MCP request caps.
- Applied Moodle's configured upload limit when files are written directly through the Moodle File API.
- Left PHP and web-server request limits unchanged.

## 0.1.190 - 2026-08-12

- Added stateless MCP 2026-07-28 support while retaining legacy MCP lifecycle
  compatibility.
- Changed the repository to the standard directly installable Moodle plugin
  layout.
- Removed the default manager-role grant for `local/moodlia:useapi`.
- Declared the complete risk profile for the remote write-capable API.
- Added reproducible Moodle Marketplace packaging and validation.
- Expanded continuous integration with Moodle metadata, coding-style, PHPDoc,
  PostgreSQL, and MariaDB checks.

## 0.1.189 - 2026-08-12

- Added stateless MCP 2026-07-28 discovery, metadata, routing headers, result
  envelopes, and cache hints.
- Preserved MCP 2025-03-26 through 2025-11-25 compatibility.

Earlier development releases are described in `README.md`.
