# MoodlIA Moodle Plugin

The MoodlIA Moodle Plugin is a local plugin for controlled Moodle automation from LLM tools, scripts, and developer workflows.

The plugin exposes a broad Moodle operation layer through Moodle REST web services and a Moodle-hosted MCP endpoint. It is designed for setups where Moodle should remain the system of record while an LLM, agent, CI job, or command-line workflow can create, inspect, update, audit, and repair Moodle content through explicit Moodle permissions.

The plugin component is `local_moodlia` and the plugin folder name is `moodlia`.

## Project Links

- Source code: https://github.com/gafapa/moodle-local_moodlia
- Issue tracker: https://github.com/gafapa/moodle-local_moodlia/issues
- Documentation: https://github.com/gafapa/moodle-local_moodlia#readme

## What It Is For

MoodlIA is intended to make Moodle usable from modern automation interfaces without bypassing Moodle itself.

Typical use cases include:

- Connecting an LLM agent to Moodle through MCP so it can inspect courses, create activities, manage question banks, and run controlled course workflows.
- Using the `moodlia` CLI from a terminal, CI job, or external script to automate repetitive Moodle administration and course-building tasks.
- Building course templates as portable JSON blueprints, then creating or applying them to Moodle courses.
- Auditing course readiness, completion rules, gradebook setup, module structure, activity subelements, and publish state before making a course visible.
- Managing question banks, quizzes, activity content, enrolments, groups, cohorts, files, assignments, workshops, glossaries, forums, wikis, and backup/restore workflows through one consistent operation contract.
- Inspecting installed plugins, dependencies, cached updates, and guarded enabled-state changes without allowing remote plugin code installation or removal.

MoodlIA does not replace Moodle's UI or Moodle's permission model. It wraps Moodle APIs and component behavior into explicit operations that can be called safely by authorised service users.

## LLM And Agent Integration

MoodlIA is especially useful when Moodle needs to be operated by an LLM-based assistant.

There are two main integration paths:

- **MCP endpoint:** the Moodle plugin exposes `/local/moodlia/mcp.php`. MCP-compatible clients can use this endpoint to list and call MoodlIA tools with the same Moodle REST token used by the REST API.
- **CLI package:** the public npm package `moodlia` provides a command-line interface that an LLM agent, local developer, CI runner, or automation worker can execute from a shell.

Both paths use the same canonical operation names and the same Moodle-side permission checks. For example, an operation available as the CLI command `moodlia get-courses` is backed by the same Moodle operation contract as the corresponding MCP tool and REST function.

## Requirements

- Moodle 5.2 or newer.
- Moodle web services enabled.
- A Moodle REST token authorised for the MoodlIA external service.
- Users calling the service must have `local/moodlia:useapi` and the Moodle capabilities required by each operation.
- Plugin inventory and state operations additionally require `local/moodlia:manageplugins`. This capability is not granted to any role archetype by default.
- Node.js 22 or newer when using the public CLI package.

This release has been validated on Moodle 5.2.

MoodlIA is free, open-source software. It does not require a MoodlIA-hosted
service, a vendor account, a paid subscription, or an API key issued by the
plugin author.

## Moodle Plugin Installation

Install MoodlIA like any standard Moodle local plugin:

1. Copy the `moodlia` folder to `<moodle-root>/local/moodlia`.
2. Run the Moodle upgrade process from the Moodle root:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

You can also complete the upgrade from the Moodle web administration interface after copying the plugin folder.

After installation, enable and configure Moodle web services:

1. Enable Moodle web services if they are not already enabled.
2. Enable the MoodlIA external service.
3. Create or assign a token for a user allowed to call MoodlIA.
4. Grant `local/moodlia:useapi` and the relevant Moodle capabilities for the operations that user should perform.

Plugin administration is intentionally separated from normal course automation. Grant `local/moodlia:manageplugins` only to a dedicated, tightly controlled service account that needs plugin inventory or state operations.

Use a dedicated service user for automation whenever possible. Avoid reusing a full administrator token for external agents unless the environment is strictly controlled.

## CLI Installation

MoodlIA also provides a public command-line client named `moodlia`.

Install it globally from npm on any machine that needs to automate Moodle:

```bash
npm install -g moodlia
```

You can also install it inside a Node project:

```bash
npm install moodlia
```

For project-local usage, run it through `npx` or a package script:

```bash
npx moodlia get-current-user
```

Configure the Moodle site URL and REST token in the shell where the CLI runs:

```bash
export MOODLE_BASE_URL="https://your-moodle.example"
export MOODLE_REST_TOKEN="your-token"
```

On Windows PowerShell:

```powershell
$env:MOODLE_BASE_URL = "https://your-moodle.example"
$env:MOODLE_REST_TOKEN = "your-token"
```

The CLI can also read a `.env` file from the current working directory:

```text
MOODLE_BASE_URL=https://your-moodle.example
MOODLE_REST_TOKEN=your-token
```

Basic examples:

```bash
moodlia get-current-user
moodlia get-courses --limit 10
moodlia get-course-contents --course-id 42
moodlia audit-course --course-id 42
moodlia list-plugins --source additional
moodlia create-module --course-id 42 --section-number 1 --module-type page --name "Reading" --options "{\"content\":\"<p>Hello</p>\"}"
```

Object parameters are passed as JSON strings. Commands return JSON by default, which makes them suitable for scripts, agent tools, and CI pipelines.

The CLI uses Moodle REST directly. It requires this Moodle plugin to be installed and a token authorised for the MoodlIA external service.

## MCP Endpoint

The plugin includes a Moodle-hosted MCP endpoint:

```text
https://your-moodle.example/local/moodlia/mcp.php
```

MCP clients should authenticate with a bearer token using a Moodle REST token authorised for the MoodlIA service.

The endpoint supports the standard tool discovery and tool call flow used by MCP clients. It exposes the same operation contract as the REST and CLI surfaces, so tool names, parameter schemas, enum values, and permission expectations stay aligned across integrations.

The server supports both MCP protocol eras on the same Streamable HTTP endpoint. Modern `2026-07-28` clients use stateless, self-describing requests with `server/discover`, per-request `_meta`, and the standard routing headers. Legacy `2025-03-26`, `2025-06-18`, and `2025-11-25` clients continue to use `initialize` and `notifications/initialized`. Both eras support `ping`, `tools/list`, and `tools/call`; modern responses include `resultType`, server metadata, and private cache hints where the protocol permits them. Tool calls return standard text content plus `structuredContent`. Browser-originated requests are accepted only from the Moodle site's own origin; non-browser clients normally omit the `Origin` header.

This is useful for LLM clients that can connect to an MCP server and need structured, permission-checked access to Moodle without screen scraping or browser-only automation.

## Capabilities

This release exposes 243 Moodle external functions.

Major operation areas include:

- Course and category management.
- Course blueprints, structure copy, publish states, readiness audits, and backup/restore.
- Sections, modules, files, common module settings, and completion rules.
- Enrolments, cohorts, users, roles, groups, and groupings.
- Calendar events and course progress reports.
- Gradebook categories, manual grade items, grades, user grades, and assignment grading.
- Assignment, Book, Choice, Database, Feedback, Folder, Forum, Glossary, Lesson, Page, Quiz, Resource, URL, Wiki, Workshop, Question bank, LTI, Label, and Subsection workflows.
- Question category and question CRUD, question movement, question bank blueprint import/export, quiz slot management, random questions, quiz attempts, quiz reports, and quiz review data.
- Activity subelements such as Book chapters, Database fields and entries, Feedback items, Lesson pages, Workshop submissions and assessment forms, Forum discussions and posts, Glossary entries, Wiki pages, and controlled file operations.
- Administrative plugin inventory, plugin details, dependency resolution, cached or refreshed update checks, and guarded enable/disable requests for plugin types that implement Moodle's state API.

MoodlIA does not install, update, uninstall, upload, or delete plugin code. Those actions remain server deployment responsibilities because they require filesystem access and Moodle upgrade procedures. Enabled-state changes are limited to up-to-date plugins with Moodle state support, enforce dependency safeguards, and refuse to change MoodlIA itself.

## Architecture

MoodlIA keeps one canonical operation contract and exposes it through several surfaces:

```text
Moodle PHP operation classes
    -> Moodle REST external functions
        -> Public Node CLI and REST client
    -> Moodle-hosted MCP endpoint for AI integrations
```

The CLI does not call MCP. It calls Moodle REST directly through `/webservice/rest/server.php`.

The MCP endpoint calls the same Moodle operation layer and uses the same REST token model. This keeps LLM tool calls, CLI commands, and direct REST calls aligned.

## Release Notes

The canonical, complete release history is maintained in [`CHANGES.md`](CHANGES.md).

### 0.1.200

- Preserves portable HTML section summaries in REST and MCP responses, including `<details>` and `<summary>` content.
- Preserves section summary formats and resolves Moodle file URLs during section updates.
- Adds grade-pass and global course-completion operations to the default MoodlIA external service.

### 0.1.190

- Makes the repository root directly installable as `local/moodlia`, following
  the Moodle Marketplace source layout.
- Removes the automatic manager-role grant for the remote automation
  capability; administrators must now grant it explicitly to dedicated service
  users.
- Declares the full configuration, data-loss, personal-data, and spam risk set
  associated with the broad write-capable automation API.
- Adds reproducible Marketplace archive validation, listing copy, security
  policy, release notes, and stricter Moodle Plugin CI checks.

### 0.1.189

- Adds the stateless MCP `2026-07-28` request model, `server/discover`, per-request metadata, routing-header validation, modern result envelopes, and cache hints.
- Preserves the legacy Streamable HTTP initialization lifecycle for MCP `2025-03-26` through `2025-11-25` clients on the same endpoint.
- Updates the public Node transport to negotiate both protocol eras, retain legacy session identifiers, close stateful sessions, and parse request-scoped SSE responses.

### 0.1.188

- Adds Lesson essay and matching question-page creation and update through Moodle's native Lesson page classes.
- Completes MoodlIA coverage of Moodle 5.0 core Feedback item types, Lesson question page types, and Workshop grading strategies.
- Publishes an MCP transport for the shared Node client with lazy lifecycle negotiation and native JSON parameter encoding.

### 0.1.187

- Adds plugin inventory, detail, dependency, and update-check operations across REST, MCP, and CLI.
- Adds guarded enable/disable support for compatible Moodle plugin types with post-change verification.
- Protects plugin administration behind the new `local/moodlia:manageplugins` capability, which has no default role archetype grant.
- Keeps plugin installation, code updates, and uninstallation outside the remote operation surface.

### 0.1.186

- Loads complete system cohort records through the paginated Moodle cohort API so create, update, and delete responses retain all fields.

### 0.1.185

- Completes the Moodle 5.2 cohort lookup fix by performing visibility checks from the site course context.

### 0.1.184

- Fixes system cohort lookup on Moodle 5.2 by passing the required context object to the core cohort API.

### 0.1.183

- Implements the MCP initialization lifecycle and standard structured tool results.
- Validates MCP origins, JSON content types, and bearer token structure.
- Streams large external files through Moodle's core multipart draft endpoint and passes only a draft item id to MoodlIA operations, avoiding Base64 expansion in PHP memory.
- Applies Moodle's configured upload limit to files without adding MoodlIA-specific byte caps; PHP and the web server may still enforce their own request limits.
- Preserves Moodle subdirectory paths in Node clients and automation tools.
- Replaces existing folder and private backup files atomically through Moodle draft files.
- Resolves token users from the authenticated Moodle session instead of assuming user id 2.

### 0.1.182

- Adds the MoodlIA local plugin REST service with 235 external functions.
- Adds the Moodle-hosted MCP endpoint at `/local/moodlia/mcp.php` for LLM and agent integrations.
- Publishes the `moodlia` npm CLI for shell, CI, and agent-driven Moodle automation.
- Exposes course/category CRUD, enrolments, groups, cohorts, users, roles, calendar events, sections, modules, completion, gradebook, course backup/restore, and course workflow operations.
- Supports activity workflows for Assignment, Book, Choice, Database, Feedback, Folder, Forum, Glossary, Lesson, Page, Quiz, Resource, URL, Wiki, Workshop, Question bank, LTI, Label, and Subsection modules.
- Adds question bank operations for category management, question CRUD, question movement, question bank blueprint export/import, quiz question slot management, random questions, and quiz attempt workflows.
- Adds portable course blueprint operations for export, course creation from blueprint, applying blueprints to existing courses, copying course structure, enrolment synchronisation, publishing state changes, and course audits.
- Adds completion audit and repair operations for stale grade-based completion rules.
- Uses Moodle core APIs and component APIs wherever available; the plugin does not define plugin-owned database tables.
- Implements the Moodle Privacy API as a null provider because the plugin stores no personal data of its own.

## Privacy

MoodlIA does not create plugin-owned database tables and does not store personal data in plugin-owned storage. It operates on existing Moodle data through Moodle permissions and APIs.

The plugin can read or modify Moodle data only through the capabilities granted to the authenticated Moodle user or token owner.

MoodlIA does not send data to the plugin author, does not require a vendor-hosted
service, and does not require a paid subscription. Administrators choose which
REST, MCP, CLI, or AI clients connect to their Moodle site. An authorised client
can receive personal data already held by Moodle, including user identity,
enrolment, submission, grade, and activity data when the token owner has the
corresponding Moodle capabilities. Administrators are responsible for assessing,
configuring, and protecting those external clients and for granting the minimum
required capabilities.

## Security

MoodlIA is intended for trusted automation.

Recommended security practices:

- Use dedicated service users for automation.
- Grant only the Moodle capabilities required for the intended workflow.
- Do not grant `local/moodlia:manageplugins` to general course-authoring agents.
- Use separate tokens for development, testing, and production.
- Rotate tokens if they are exposed to an external agent or CI system.
- Avoid giving administrator tokens to general-purpose LLM agents.
- Review generated course changes before publishing courses to learners.

Security reports should follow the private reporting instructions in
[`SECURITY.md`](SECURITY.md). General bugs and feature requests belong in the
public issue tracker.

## Support

Use the public issue tracker for reproducible bugs, compatibility reports, and
feature proposals: https://github.com/gafapa/moodle-local_moodlia/issues

This community plugin is independently developed and is not affiliated with or
endorsed by Moodle Pty Ltd.

## License

GNU GPL v3 or later.

## Quality Checks

Run the complete local validation and static test suite with:

```bash
npm run check
```

Run the release gate, including a disposable package build, with:

```bash
npm run release:check
```

PHP syntax validation is optional when PHP is unavailable locally and mandatory in CI.
