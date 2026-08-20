# Changelog

All notable changes to the MoodlIA Moodle plugin are documented here.

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
