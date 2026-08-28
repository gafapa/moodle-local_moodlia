# Changelog

All notable changes to the MoodlIA Moodle plugin are documented here.

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
