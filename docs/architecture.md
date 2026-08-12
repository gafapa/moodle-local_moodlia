# Architecture

The proposed architecture exposes one Moodle operation surface through three functional interfaces:

- Moodle REST web services.
- MCP `tools/list` and `tools/call`.
- A Node CLI.

All interfaces share the same canonical operation contract, generated or validated client types, and Moodle PHP operation classes.

## Core Principle

Moodle PHP operation classes own the business behavior. Everything else is an adapter.

```text
docs/interface-contract.md or generated contract artifact
    -> PHP operation registry
        -> operation classes
            -> Moodle core APIs
    -> REST external classes
    -> MCP tool registry and dispatcher
    -> TypeScript client
    -> Node CLI commands
    -> parity tests
```

This keeps authorization, validation, Moodle context handling, and side effects consistent even when the operation is called from different transports.

## Layer Responsibilities

### Canonical Contract

The contract defines:

- Operation names.
- Parameter schema.
- Return schema.
- Required Moodle context.
- Required capabilities.
- Read/write classification.
- Error shape.
- File transfer mode.
- Test fixtures and cleanup expectations.

The contract is the source of truth for generated or validated adapters.

### Moodle Operation Layer

The operation layer is implemented in PHP classes under the plugin namespace. It should:

- Accept validated operation input.
- Resolve and validate Moodle context.
- Check capabilities.
- Call Moodle core APIs.
- Return plain typed arrays matching the contract.
- Throw normalized domain exceptions for adapter-level formatting.

Operation classes should not know whether they were called through REST, MCP, or CLI.

Shared domain helpers may be used inside the operation layer, but they must keep Moodle
responsibilities separated:

- `module_tools` owns activity creation, update, deletion, section placement, and module
  configuration orchestration through Moodle course APIs.
- `module_lookup_tools` owns module API loading, content item lookup, course module
  resolution, quiz module type checks, and canonical module response formatting.
- `module_common_tools` owns settings shared by multiple Moodle modules, including
  visibility, course-page visibility, id number, language, group mode, grouping,
  availability restrictions, tags, and downloadable content.
- `module_content_tools` owns simple content module settings for page, question bank,
  book, folder, label, file resource, subsection, and URL modules.
- `module_assignment_tools` owns assignment-specific creation settings, including
  submission controls, date validation, group submission options, marking workflow,
  attempt reopening, and feedback plugin defaults.
- `module_quiz_tools` owns quiz-specific creation settings, including timing,
  overdue handling, question behaviour, review flags, navigation, password, browser
  security, and attempt controls.
- `module_interaction_tools` owns interaction-oriented module settings for choice,
  feedback, database, forum, glossary, and wiki modules.
- `module_advanced_tools` owns advanced activity settings for lesson, workshop, and
  LTI modules, including lesson navigation and presentation options, workshop grading
  strategy and submission windows, and LTI launch, privacy, and URL validation.
- `module_file_tools` owns folder/resource file handling through the Moodle File API,
  including draft files, stored files, and pluginfile download URLs.
- `question_tools` owns question-bank locations, question CRUD, question-type form
  construction, quiz structure, and quiz question slots.
- `question_quiz_attempt_tools` owns quiz attempts, access information, view events,
  attempt payload decoding, and quiz result formatting.
- `book_chapter_tools` owns the only approved Moodle-DML write boundary for Book
  chapters. Moodle Book does not expose a public chapter writer API, so this helper
  mirrors the owning component's own edit/delete/move behavior, validates Book
  ownership and `mod/book:edit`, updates revision and page order, triggers Book
  events, and must not use raw SQL or plugin-owned tables.
- `plugin_management_tools` owns the system plugin inventory boundary through
  `core_plugin_manager`, dependency resolution, cached update metadata, and guarded
  enabled-state support. It never writes plugin files, installs packages, runs an
  upgrade, uninstalls code, or allows MoodlIA to change its own enabled state.
- Additional helpers should follow the same rule: group behavior by Moodle API boundary,
  not by transport.

### REST Adapter

REST functions are Moodle External API classes. Each REST adapter should:

- Map the Moodle web service function name to the canonical operation name.
- Validate parameters through Moodle external structures.
- Call the operation dispatcher.
- Return the typed response declared by `execute_returns`.

REST function names use the Moodle component prefix:

```text
local_moodlia_get_courses
local_moodlia_create_module
local_moodlia_update_question
```

Course movement is exposed both as `update_course` with `category_id` and as the explicit `move_course` operation. Both paths use Moodle's course update API and require the source course update capability plus target category course-creation capability.

Direct section creation uses Moodle's section APIs. If a target Moodle course format rejects direct section creation, `module_type=subsection` is the supported fallback because it creates Moodle-visible delegated sections through the standard module creation path.

### MCP Adapter

The Moodle-hosted MCP adapter is exposed at `/local/moodlia/mcp.php`. It supports both protocol eras and exposes the same canonical operation names through:

- `server/discover`: advertise the stateless `2026-07-28` protocol, capabilities, identity metadata, and cache hints.
- Per-request `_meta`, `MCP-Protocol-Version`, `Mcp-Method`, and `Mcp-Name`: make modern requests self-describing and independently routable.
- `initialize` and `notifications/initialized`: preserve negotiation for `2025-03-26` through `2025-11-25` clients.
- `ping`: verify authenticated transport availability.
- `tools/list`: returns tool schemas derived from the contract.
- `tools/call`: dispatches a tool name and arguments through the existing Moodle REST external functions.

The HTTP adapter is stateless and does not issue an MCP session id. Modern requests can therefore be served by any application instance without shared transport state. The endpoint validates same-origin browser requests, modern header/body consistency, JSON POST bodies, request size, and the Moodle bearer token on every call. Modern complete results carry `resultType` and server identity metadata; cacheable authenticated results use private cache scope. Legacy responses retain their original wire shape so strict 2025 clients remain compatible.

MCP tool names stay in `snake_case`:

```text
get_courses
create_module
update_question
delete_folder_file
```

The MCP adapter should not define independent schemas by hand. The current PHP manifest is checked against the canonical contract by static parity tests.

### TypeScript Client Layer

The shared client layer provides a `MoodleClient` facade over interchangeable REST and MCP transports without changing canonical operation semantics:

```text
MoodleClient
    -> RestTransport
    -> McpTransport
```

Client methods use canonical snake_case names only:

```text
get_courses() -> get_courses
create_module() -> create_module
update_question() -> update_question
```

The current client is implemented in `client/moodle-rest-client.mjs`. It validates and coerces parameters against `contract/operations.json`, maps canonical operations to `local_moodlia_*` REST functions or MCP `tools/call`, and normalizes REST and JSON-RPC errors. Object and boolean parameters use form-compatible encoding for REST and native JSON encoding for MCP.

`McpTransport` derives `/local/moodlia/mcp.php` from the Moodle base URL or accepts an explicit endpoint. In automatic mode it probes `server/discover` and uses stateless `2026-07-28` requests when available. If the server only supports the earlier era, it falls back to `initialize`, sends `notifications/initialized`, retains any server-issued `Mcp-Session-Id`, and can terminate that session with `close()`. The transport accepts JSON and request-scoped SSE responses, exposes `ping`, `listTools`, and canonical operation calls, and authenticates every request with the Moodle bearer token.

### Node CLI

The current Node CLI calls the Moodle REST API directly through `MoodleClient`. It reads the canonical operation contract, maps `snake_case` operations to kebab-case commands, validates command arguments, and invokes the matching `local_moodlia_*` web service function.

CLI commands use kebab-case names derived from canonical operations:

```text
moodle-mcp get-courses
moodle-mcp create-module
moodle-mcp update-question
moodle-mcp delete-folder-file
```

The development repository keeps the internal script at `cli/moodle-mcp.mjs` for continuity with existing automation. The public npm package rewrites that entry point to the external binary name:

```text
moodlia get-courses
moodlia create-module
moodlia update-question
moodlia delete-folder-file
```

The CLI should:

- Load environment configuration.
- Validate command arguments against generated schemas.
- Call Moodle REST directly through the shared contract rules.
- Print JSON by default for automation.
- Offer concise table output only as an optional presentation mode.

This keeps the CLI faster and simpler for automation than routing through MCP. The shared Node client can select REST or MCP without changing canonical operation names or response validation.

### Public Npm Package

The generated npm package under `packages/moodlia` contains only the external consumer surface:

- `cli/moodlia.mjs`.
- `client/moodle-rest-client.mjs`.
- `client/moodle-rest-client.d.ts`.
- `client/generated/operation-types.d.ts`.
- `contract/operations.json`.
- `README.md`, `LICENSE`, and `package.json`.

It is generated by `npm run npm:sync` and checked by `npm run npm:sync:check` plus the static package test. The package must not include Moodle plugin PHP source, deployment scripts, test fixtures, reports, local env files, or unpublished transport metadata.

### Moodle Pages

The plugin does not need a Moodle operation UI. If a Moodle page is kept, it should remain a small administration or installation-status page only. It must not introduce a fourth functional surface, duplicate operation names, or bypass REST/MCP/CLI contract rules.

Browser workflows are still useful, but only as verification that Moodle core pages show the state created by REST, MCP, or CLI operations.

## Data Flow

Read operation:

```text
Caller
    -> adapter validation
    -> canonical operation dispatcher
    -> context validation
    -> capability check
    -> Moodle core API read
    -> typed response
```

Write operation:

```text
Caller
    -> adapter validation
    -> canonical operation dispatcher
    -> context validation
    -> capability check
    -> Moodle core API mutation
    -> event/logging where appropriate
    -> typed response
    -> verification read in tests
```

File operation:

```text
Caller
    -> upload/download endpoint or file metadata operation
    -> Moodle File API
    -> owning context and file area
    -> typed metadata response
```

## Contract Ownership

The project should choose one durable contract format during implementation. Recommended options:

- A PHP operation registry that generates JSON schemas for MCP, TypeScript, and CLI.
- A JSON/YAML contract file that generates PHP declarations and TypeScript types.
- A hybrid where PHP operation classes provide metadata and a build command emits a checked contract artifact.

Whichever format is chosen, the invariant is the same: adding an operation in only one interface must fail tests.

## AI Course Generation Scope

Ideas from `moodlia_php` should be incorporated only when they fit the shared operation model:

- Generate course outline.
- Generate sections.
- Generate activities or resources.
- Generate question categories and questions.
- Add generated questions to quizzes.
- Track AI generation jobs and outputs.

AI-specific behavior should still call Moodle operation classes for final Moodle mutations. The generation layer can propose content, but the operation layer creates, updates, and deletes Moodle entities.

## Current PHP Structure

```text
local/moodlia/
    version.php
    db/
        services.php
        access.php
    classes/
        contract/
            operation_registry.php
        operation/
            get_courses.php
            create_module.php
            update_question.php
            module_tools.php
            module_lookup_tools.php
            module_common_tools.php
            module_content_tools.php
            module_assignment_tools.php
            module_quiz_tools.php
            module_interaction_tools.php
            module_advanced_tools.php
            module_file_tools.php
        external/
            get_courses.php
            create_module.php
            update_question.php
        mcp/
            tools_list.php
            tools_call.php
            dispatcher.php
        privacy/
            provider.php
    tests/
        operation/
        external/
        contract/
```

Do not add `db/install.xml` or `db/upgrade.php` unless the plugin genuinely needs its own durable schema. The current implementation intentionally uses Moodle core structures only.

## Suggested Node Structure

```text
client/
    src/
        contract/
        transports/
            rest.ts
            mcp.ts
        moodle-client.ts
cli/
    src/
        commands/
        config.ts
        index.ts
tests/
    parity/
    api/
    cli/
    browser/
```

## Reuse Rules

- One operation name, one contract entry, one Moodle operation implementation.
- REST and MCP adapters may differ in transport details but not in behavior.
- The CLI calls REST through shared contract rules.
- Tests compare REST, MCP, and CLI outputs for compatible operations.
- Browser tests verify Moodle-visible effects, not only adapter success.
