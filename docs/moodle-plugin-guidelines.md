# Moodle Plugin Guidelines

This document defines the Moodle-specific rules for the proposed `local_moodlia` plugin. It should be used as the implementation guardrail for PHP code, REST functions, MCP dispatch, CLI automation, and deployment verification.

## Core API Rules

Use Moodle core APIs as the default integration path:

- Use the Access API for permissions, capabilities, roles, and context checks.
- Use External Services and the External API for REST web service functions.
- Use the File API and Moodle web service upload/download endpoints for Moodle-managed files.
- Use the Data Manipulation API through `$DB` only when no Moodle domain API exposes the required context or persistence operation.
- Use the Data Definition API and XMLDB only for install and upgrade schema changes.
- Use Moodle events and logging APIs for auditable state changes.
- Use Moodle testing tools, especially PHPUnit for PHP operation behavior and Behat for Moodle UI behavior.

Avoid raw SQL and direct `$DB` access in this plugin. Prefer Moodle domain APIs such as course, module, question, context, enrolment, role, and file APIs. MoodlIA currently owns no database schema, so plugin code must not use `$DB` for plugin-owned storage or Moodle-core data lookups.

## External Function Pattern

Every Moodle REST function must follow the standard External API pattern:

1. Declare the function in `db/services.php`.
2. Define the function in an autoloaded class under `classes/external`.
3. Validate parameters using `execute_parameters`.
4. Validate context using Moodle context APIs.
5. Check capabilities with `require_capability` or explicit access checks.
6. Call an internal operation class that owns the business behavior.
7. Return only typed data declared by `execute_returns`.
8. Let Moodle validate the return shape.

External functions must not contain the main business logic. They are adapters around operation classes.

## Service Declarations

Declare web service functions in `db/services.php` with consistent naming:

```text
local_moodlia_get_courses
local_moodlia_create_module
local_moodlia_update_question
local_moodlia_delete_folder_file
```

Each declaration should include:

- `classname`: the namespaced external class.
- `description`: a short human-readable description.
- `type`: `read` or `write`.
- `ajax`: `true` only when the Moodle web UI should call it directly.
- `capabilities`: advisory capabilities used by the function.

After adding or changing service declarations, increment `version.php` and run the Moodle upgrade process so Moodle discovers the services.

## Context And Capability Rules

Operations must validate the narrowest relevant context:

- Site-wide metadata: system context.
- Course list or course mutation: course or category context.
- Activity mutation: module or course context.
- Question bank operations: question category, course, or system context as appropriate.
- File operations: the context that owns the file area.

Capability checks should match the actual Moodle action, not the transport. Example checks may include course update, activity management, question editing, and file management capabilities. The operation contract should document required capabilities per operation.

## Component Boundaries

Follow Moodle component communication rules:

- Direct calls are allowed only for core APIs, the same component, parent/sub-plugin relationships, or explicitly declared dependencies where Moodle permits them.
- Do not call another plugin's arbitrary PHP functions unless the dependency and communication path are valid.
- If another component exposes external functions and this plugin needs them, call through Moodle's external function mechanism rather than bypassing external API setup.
- Keep plugin-owned behavior in `local_moodlia` operation classes so REST, MCP, and CLI are not coupled to another component's internals.

## File Handling

Use Moodle file handling rules:

- Store Moodle content files through the File API.
- Respect component, file area, item id, and context ownership.
- Do not read or write another component's private file areas directly.
- Prefer `/webservice/upload.php` for external uploads and `/webservice/pluginfile.php` for external downloads when moving binary file content.
- Use web service file functions only when base64 payloads are appropriate and payload size is controlled.
- Always verify file visibility and permissions from the owning Moodle context.

## Persistence And Schema

The current plugin must not create plugin-owned Moodle database tables. Use existing Moodle core structures and APIs for courses, sections, modules, files, question banks, and quizzes.

Read-only DML is not part of the current plugin architecture. If a Moodle public API cannot resolve a required context, change the operation contract to include the context or owning Moodle selector instead of reading Moodle tables directly.

Only consider plugin tables in a future release when the plugin must own durable state that cannot be represented by Moodle core structures, such as:

- Operation audit records.
- MCP session metadata.
- Contract version records.
- AI generation job metadata.
- User-specific plugin preferences.

Schema changes must be made through XMLDB:

- `db/install.xml` for initial tables.
- `db/upgrade.php` for migrations.
- `version.php` increment for every database, service, autoloaded class, JavaScript, setting, or language pack change that requires Moodle upgrade or cache refresh.

## Privacy

The plugin must implement Moodle Privacy API metadata.

Use `null_provider` only if the plugin truly stores no personal data and does not export personal data to external systems. If the plugin stores or processes personal data, implement the relevant metadata and request providers so user data can be described, exported, and deleted.

Document privacy decisions for:

- The shared Moodle REST token used by REST, MCP, and the Node CLI.
- User IDs included in operation logs.
- AI generation prompts and outputs.
- Uploaded files and generated course content.
- External AI providers, if any are used later.

## Security

Security checks belong inside Moodle operation execution, not only at the transport edge.

Required safeguards:

- Validate every input against the canonical schema and Moodle parameter types.
- Validate Moodle context before permission checks.
- Check capabilities for every operation that reads or mutates protected data.
- Enforce token authentication for REST, MCP, and CLI calls using the same Moodle REST token.
- Never log tokens, passwords, private file content, or raw AI prompts containing personal data unless the privacy design explicitly allows it.
- Return structured errors that do not expose secrets or internal paths.

## Anti-Patterns

Avoid these implementation patterns:

- Adding REST functions with no matching MCP tool, CLI command, or contract entry.
- Putting business logic in `classes/external`.
- Letting CLI commands drift from the canonical contract or bypass normalized REST transport rules.
- Calling raw SQL for course, module, question, or file operations when Moodle APIs exist.
- Creating files outside Moodle's File API for Moodle-managed content.
- Skipping context and capability checks because a token is present.
- Treating Moodle pages as a separate operation surface with their own operation names.
- Using `null_provider` for privacy while storing prompts, user identifiers, logs, or generated content.

## Official References

- Moodle API guides: https://moodledev.io/docs/5.0/apis
- External Services: https://moodledev.io/docs/5.0/apis/subsystems/external
- Function declarations: https://moodledev.io/docs/5.0/apis/subsystems/external/description
- Function definitions: https://moodledev.io/docs/4.1/apis/subsystems/external/functions
- Access API: https://moodledev.io/docs/4.5/apis/subsystems/access
- File API: https://moodledev.io/docs/5.1/apis/subsystems/files
- External file handling: https://moodledev.io/docs/5.0/apis/subsystems/external/files
- Data Manipulation API: https://moodledev.io/docs/5.0/apis/core/dml
- Data Definition API: https://moodledev.io/docs/4.4/apis/core/dml/ddl
- Component communication: https://moodledev.io/general/development/policies/component-communication
- Privacy API: https://moodledev.io/docs/5.2/apis/subsystems/privacy
- Plugin upgrades: https://moodledev.io/docs/5.3/guides/upgrade
