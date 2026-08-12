# Changelog

All notable changes to the MoodlIA Moodle plugin are documented here.

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
