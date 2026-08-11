# Development Roadmap

This roadmap turns the design into a phased implementation plan. Each phase should leave the project in a verifiable state.

## Current Status

Implemented:

- Moodle local plugin scaffold for `local_moodlia`.
- Canonical JSON operation contract.
- REST external functions for user/course reads, course category CRUD, course calendar event CRUD, enrolment, groups and groupings, course progress reporting, course CRUD, section CRUD, module create/update/duplicate/move/delete including Moodle Question bank and Subsection modules, Choice option/response/result operations, Database field and entry operations, Feedback activity creation/details, course listing, view-event registration, access/status reads, item creation/update for supported types, item listing/deletion, Assignment submission/grade reads and view-event registration, Workshop activity creation/details, Workshop phase switching, accumulative, comments, number-of-errors, and rubric grading-form setup, user-plan reads, grade reads, grade-report reads, and submission CRUD, forum course listing, view-event registration, discussion and post operations, glossary course listing, view events, entry browse/read/search/create/update/delete operations, category reads, author reads, author-filtered entry reads, pending-approval entry reads, wiki page create/list/update operations, wiki subwiki/file reads, wiki view-event registration, folder file operations, question category CRUD, question create/update, adding questions to quizzes, quiz course listing, quiz attempt reads/starts, quiz attempt access/data/summary/review reads, quiz attempt save/process operations, quiz attempt view-event registration, quiz access reads, quiz combined review option reads, quiz view registration, quiz user best-grade reads, quiz results reports, quiz grade-feedback reads, and quiz required question-type reads.
- `get_module_details` returns common module metadata plus activity-specific details for Assignment, Book, Choice, Database, Feedback, Lesson, LTI/External tool, Folder, Forum, Glossary, Label, Page, Question bank, Quiz, Resource, Subsection, URL, Wiki, and Workshop using Moodle public APIs, File API helpers, question APIs, and course format APIs.
- Moodle-hosted MCP endpoint with `tools/list` and `tools/call` over the same REST-backed operation surface.
- MCP token validation for `tools/list` and `tools/call` using the shared Moodle REST token.
- `MoodleClient` facade with `RestTransport` and `McpTransport`, canonical snake_case methods, contract-backed parameter validation, transport-aware serialization, lazy MCP lifecycle negotiation, and normalized REST/JSON-RPC errors.
- Node CLI at `cli/moodle-mcp.mjs` that maps contract operations to kebab-case commands and calls Moodle REST directly.
- Public npm package generator for `moodlia`, containing only the external CLI, REST client, generated types, filtered operation contract, README, and license.
- Static contract and parity checks.
- Runtime transport parity checks compare REST, MCP, and CLI response shapes for stable read operations.
- REST smoke/write tests that generate course categories, courses, calendar events, assignment course listing, assignment submissions and grades, gradebook item/user-grade/progress-report checks, Choice course listing, view, submission, response deletion, and result operations, Database field and entry CRUD, Feedback creation, course listing, view, access/status reads, item creation/update/listing, Lesson course listing, settings/details, content page lifecycle, access/page/jump/view/grade/timer/attempt-report operations, LTI, Question bank, Subsection, Workshop activities, Workshop accumulative, comments, number-of-errors, and rubric grading forms, Workshop user plans, Workshop grades, Workshop grade reports, Workshop submissions, forum course listing, view, discussions, replies, discussion pin/favourite/subscription/lock state, forum post deletion, glossary course listing, view events, entry browse/search/create/update/delete operations, category reads, author reads, and wiki page create/update/delete operations as needed, including `truefalse`, `shortanswer`, `multichoice`, `numerical`, `essay`, `matching`, `description`, `randomsamatch`, `gapselect`, `ddwtos`, `ordering`, `multianswer`, `ddmarker`, `ddimageortext`, `calculatedsimple`, `calculated`, and `calculatedmulti` questions.
- MCP smoke/write lifecycle tests for generated course categories, courses, calendar events, modules including duplication and movement, Choice course listing, view, submission, response deletion, and result operations, Database field and entry operations, Feedback, Feedback item creation/update/listing, Lesson course listing, settings/details, content page lifecycle, access/page/jump/view/grade/timer/attempt-report operations, LTI, Question bank, Subsection, Workshop phases, accumulative, comments, number-of-errors, and rubric grading forms, user plans, grades, grade reports, and submissions, assignment course listing, assignment submissions and grades, gradebook item/user-grade checks, forum discussions, forum replies, glossary entries, wiki pages, files, question categories, `truefalse`, `shortanswer`, `multichoice`, `numerical`, `essay`, `matching`, `description`, `randomsamatch`, `gapselect`, `ddwtos`, `ordering`, `multianswer`, `ddmarker`, `ddimageortext`, `calculatedsimple`, `calculated`, and `calculatedmulti` questions, quizzes, and quiz attempts.
- MCP negative validation tests for missing bearer tokens, invalid tokens, unknown methods, unknown tools, invalid tool arguments, malformed JSON, invalid JSON-RPC envelopes, and non-POST requests.
- CLI smoke/write lifecycle tests for generated course categories, courses, calendar events, enrolment, groups, groupings, sections, assignment, assignment course listing, Book course listing, chapter creation/listing/update/movement/deletion and view registration, Choice course listing, view, submission, response deletion, and result operations, Database field and entry operations, Feedback item creation/update/listing, Lesson course listing, settings/details, content page lifecycle, access/page/jump/view/grade/timer/attempt-report operations, LTI, Question bank, Subsection, Workshop phases, accumulative, comments, number-of-errors, and rubric grading forms, user plans, grades, grade reports, and submissions, page, label, URL, forum, glossary, and wiki modules, assignment submissions and grades, gradebook item/user-grade checks, forum discussions, forum replies, glossary entries, wiki pages, folder files, question categories, `truefalse`, `shortanswer`, `multichoice`, `numerical`, `essay`, `matching`, `description`, `randomsamatch`, `gapselect`, `ddwtos`, `ordering`, `multianswer`, `ddmarker`, `ddimageortext`, `calculatedsimple`, `calculated`, and `calculatedmulti` questions, quizzes, and quiz attempts.
- CLI negative validation tests for unknown commands, missing required options, invalid booleans, and invalid JSON object parameters.
- Restricted-token permission smoke tests prove a non-admin token can authenticate through REST/MCP/CLI but cannot call administrative course, module, file, question, quiz-structure, gradebook, backup, and activity subelement operations without the required Moodle capabilities.
- Playwright browser checks for login, course index, and generated course visibility.
- Playwright browser checks verify generated activity subelements for Subsection, Page, Assignment, Book, Label, URL, LTI, Choice, Database, Lesson, Workshop, Forum, Glossary, Wiki, Folder, Resource, Quiz, question bank views, gradebook, groups, participants, calendar, and category pages.
- Generic Moodle server deployment documentation plus SFTP/WinSCP automation for the configured Docker-based development target.
- Protected-target release gate for production-like Moodle validation using only read-only REST, MCP, and CLI operations, with optional server-side PHP syntax validation.
- GitHub Actions CI for local checks that do not require a Moodle target.
- Selective generated-data cleanup for courses and empty course categories marked with MoodlIA test prefixes.
- High-level course workflow operations for portable blueprints, blueprint application, structure copy, Book chapter and Feedback item round-tripping, manual enrolment sync, publishing states, and readiness audit.
- Administrative plugin inventory, detail, dependency, cached update inspection, and guarded enabled-state operations protected by `local/moodlia:manageplugins`, without remote code installation, replacement, or removal.

Still pending:

- Optional third-party subelement extension contracts, only when a deployed Moodle site identifies a concrete plugin type and provides a stable component API.
- Deployment-specific capability matrices and cryptographic artifact signing, which require the target organisation's role policy and separately managed signing keys. SHA-256 release manifests are implemented.

## Phase 0: Documentation And Decisions

Status: complete.

Deliverables:

- README.
- Moodle plugin guidelines.
- Architecture document.
- Interface contract document.
- Automation document.
- Development roadmap.

Decisions to make before Phase 1:

- Exact Moodle plugin component name.
- Canonical contract representation: PHP metadata, JSON/YAML file, or generated hybrid.
- MCP server placement: inside Moodle PHP, separate Node service, or bridge process.
- Initial operation subset for the first release.
- The shared Moodle REST token is used for REST, MCP, and CLI automation.

## Phase 1: Moodle Plugin Scaffold

Status: complete for the current REST implementation.

Deliverables:

- `version.php`.
- `db/services.php`.
- `db/access.php`.
- Privacy provider.
- Operation dispatcher skeleton.
- Contract registry skeleton.
- First read operations: `get_current_user`, `get_courses`.

Verification:

- Moodle plugin installs.
- Moodle upgrade runs cleanly.
- Service functions appear in Moodle web service API documentation.
- PHPUnit smoke tests pass for operation registry and external functions.

## Phase 2: Contract Tooling

Status: complete for the current contract model. The JSON contract, static parity checks, client facade validation, TypeScript declaration coverage, and generated per-operation TypeScript request/response types exist.

Deliverables:

- Machine-readable operation contract.
- Schema exporter for MCP tools.
- TypeScript type generation or validation.
- CLI command manifest generation or validation.
- Static parity tests.

Verification:

- Adding a fake operation to only one surface fails parity tests.
- `tools/list` schema matches the contract.
- TypeScript client types match operation inputs and outputs.
- CLI command list matches canonical operations marked for CLI.

## Phase 3: TypeScript Client And Node CLI

Status: complete. The Node CLI calls REST directly through the shared `MoodleClient` facade, while Node consumers can select `RestTransport` or `McpTransport`. The MCP transport negotiates the Moodle-hosted endpoint lifecycle and dispatches the same canonical operations through `tools/call`.

Deliverables:

- `RestTransport`.
- `McpTransport`.
- `MoodleClient` facade.
- Node CLI binary.
- JSON output mode.
- Environment configuration loader.
- Error normalization.

Initial CLI commands:

```text
moodle-mcp get-current-user
moodle-mcp get-courses
```

The current CLI exposes every operation marked for CLI in the contract. MCP-specific commands should be added only after the MCP adapter exists.

Verification:

- CLI smoke tests pass against a test Moodle site.
- REST and CLI result shapes match for `get_current_user` and `get_courses`.
- Invalid token and missing parameter tests return normalized errors.

## Phase 4: Course And Activity Operations

Status: implemented for REST, MCP, and CLI and covered by API/browser verification. Lifecycle coverage exists for generated course categories, courses, calendar events, enrolment, groups, groupings, sections, assignment course listing, assignment submissions and grades, Book, Choice, Database fields and entries, Feedback, Lesson, Question bank, Subsection, page, label, URL, forum, glossary, wiki, folder, resource, questions, quizzes, and quiz attempts. `get_module_details` includes activity-specific details for Assignment, Book, Choice, Database fields and entries, Feedback, Lesson, Folder, Forum, Glossary, Label, Page, Question bank, Quiz, Resource, Subsection, URL, and Wiki.

Deliverables:

- Section CRUD operations.
- Course category CRUD operations.
- Course calendar event CRUD operations.
- Manual enrolment operations and participant listing.
- Group CRUD, grouping CRUD, group-to-grouping assignment, and group membership operations.
- Activity/resource CRUD operations.
- Browser verification for course page state.
- Cleanup helpers.

Candidate operations:

```text
create_section
update_section
delete_section
get_course_categories
create_course_category
update_course_category
delete_course_category
get_calendar_events
create_calendar_event
update_calendar_event
delete_calendar_event
create_module
update_module
delete_module
```

Verification:

- API, MCP, and CLI write tests create controlled test data.
- Browser tests confirm created and updated entities appear in Moodle.
- Cleanup removes test sections and modules.
- Selective cleanup can remove generated courses and empty generated course categories left by failed lifecycle tests.
- Negative tests cover missing capabilities and wrong course context.

## Phase 5: File Operations

Status: implemented for controlled folder file operations through Moodle APIs.

Deliverables:

- File metadata operations.
- Upload flow through Moodle's recommended upload endpoint.
- Download flow through Moodle's recommended file endpoint.
- Delete flow through Moodle File API rules.

Candidate operations:

```text
upload_folder_file
get_folder_files
download_folder_file
get_resource_files
download_resource_file
delete_folder_file
```

Verification:

- Upload creates a Moodle-visible file in the expected activity.
- Download retrieves the expected file content or URL.
- Delete removes the file from Moodle-visible state.
- Tests cover file permissions and invalid paths.

## Phase 6: Question Bank And Quiz Operations

Status: implemented for category CRUD, Choice options/responses/results, `truefalse`, `shortanswer`, `multichoice`, `numerical`, `essay`, `matching`, `description`, `randomsamatch`, `gapselect`, `ddwtos`, `ordering`, `multianswer`, `ddmarker`, `ddimageortext`, `calculatedsimple`, and `calculatedmulti` question create/update, adding a question to a quiz, listing course quizzes, starting a quiz attempt or preview, listing quiz attempts, reading quiz results reports, reading attempt access/data/summary/review, reading combined review option visibility, saving attempt data, processing/finalizing attempts, and registering quiz, attempt, summary, and review view events.

Deliverables:

- Question category CRUD.
- Question create/update.
- Add question to quiz.
- Browser or Behat verification of quiz-visible state.

Candidate operations:

```text
create_question_category
update_question_category
delete_question_category
create_question
update_question
add_question_to_quiz
```

Verification:

- Created questions appear in the expected category.
- Updated question fields are visible through Moodle APIs or UI.
- Added questions appear in the quiz.
- Cleanup removes generated question data safely.

## Phase 6B: Activity Subelements

Status: implemented where Moodle exposes stable public APIs, with one documented exception for Book chapter writes. MoodlIA covers all Moodle 5.0 core Feedback item types, all core Lesson question page types, and all core Workshop grading strategies, alongside the other documented activity subelements. Book chapter mutation is isolated in `book_chapter_tools` because Moodle Book has no public writer API; it mirrors Moodle Book's own edit/delete/move scripts, uses Moodle DML without raw SQL, validates ownership/capabilities, and triggers Book events. Third-party subelement types, Lesson structural cluster/end markers, direct Feedback response mutation, and standalone Workshop assessment creation remain intentionally unavailable until they meet the boundary in `docs/subelement-api-boundaries.md`.

Verification:

- Subelement reads return Moodle-visible identifiers and metadata through REST, MCP, and CLI.
- Subelement writes validate ownership against the selected activity before mutation.
- Tests leave generated courses in Moodle when failures occur so the broken state can be inspected.

## Phase 7: AI Course Generation Workflow

Status: implemented as an external AI workflow rather than provider-specific Moodle operations. The downloadable `design-portable-moodle-content` skill produces portable HTML and interactive content, while `operate-moodle-with-moodlia` selects canonical CLI or MCP operations, validates identifiers and payloads, publishes content, and verifies Moodle-visible state.

Architectural decision:

- MoodlIA exposes deterministic Moodle capabilities, not AI-provider jobs such as `generate_course_outline` or `generate_activity_content`.
- AI clients may propose course plans, content, and question sets, then validate and apply them through the existing operation contract, blueprints, CLI, or MCP.
- Prompts and model outputs remain outside Moodle unless the user explicitly publishes their result, avoiding a new plugin-owned privacy and retention surface.
- Approval remains an external client concern; Moodle permissions and MoodlIA's guarded write operations remain the final mutation boundary.

Verification:

- Both skills are packaged as downloadable archives and checked for drift.
- Generated portable content can be published without requiring MoodlIA at course-consumption or backup/restore time.
- Applied plans still pass contract validation and use Moodle-visible operations with normal capability checks.

## Phase 8: Deployment Automation

Status: implemented for generic Moodle server documentation and the configured WinSCP plus Docker development deployment flow.

Deliverables:

- Environment loader.
- SFTP upload script.
- Exclusion rules.
- Remote path confirmation.
- Moodle upgrade trigger or documented manual step.
- Post-deploy verification runner.

Verification:

- Staging deployment completes from a clean local checkout.
- Upgrade verification confirms service discovery.
- API, MCP, CLI, and browser smoke checks run after deployment.
- Protected production-like targets can run `npm run release:protected` without creating Moodle data.
- Rollback procedure is documented and tested for non-schema releases.

## Phase 9: Hardening

Deliverables:

- Capability matrix per operation.
- Privacy metadata review.
- Structured logging.
- Rate limiting or abuse controls where needed.
- Contract versioning policy.
- CI pipeline.

Verification:

- Negative tests cover invalid token, missing capability, wrong context, missing parameters, invalid file access, and not found cases.
- Parity tests run in CI.
- Browser and Behat tests cover Moodle-visible workflows.
- Secrets never appear in logs or test output.

## Release Readiness Checklist

- Documentation is current and in English.
- Operation names are stable across REST, MCP, and CLI.
- Contract parity tests pass.
- Moodle plugin upgrade runs cleanly.
- Privacy provider matches actual stored and exported data.
- API smoke tests pass.
- MCP smoke tests pass.
- CLI smoke tests pass.
- Browser or Behat tests verify Moodle-visible state.
- Deployment excludes local and secret files.
- Public npm package sync, static checks, and dry-run pack pass when CLI/client artifacts are part of the release.
- Rollback notes exist for the release.

## Recommended First Milestone

Status: superseded by the current implementation.

The first useful milestone is a read-only release:

- Plugin scaffold.
- `get_current_user`.
- `get_courses`.
- REST adapter.
- MCP `tools/list` and `tools/call`.
- TypeScript client.
- Node CLI for the two read operations.
- Static parity tests.
- Deployment documentation validated against staging.

This milestone proves the shared interface model before higher-risk write operations are added.
