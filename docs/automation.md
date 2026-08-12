# Automation

This document defines the deployment and verification model for the proposed `local_moodlia` Moodle plugin.

## Environment Variables

Use explicit environment-specific configuration. Do not hard-code credentials, paths, course IDs, or tokens.

For local automated tests, copy `.env.example` to `.env.test` and fill only the values required by the test group you want to run. Static contract tests do not require environment variables.

Required Moodle variables:

```text
MOODLE_BASE_URL=https://moodle.example.edu
MOODLE_USERNAME=admin
MOODLE_PASSWORD=...
MOODLE_REST_SERVICE=local_moodlia
MOODLE_REST_TOKEN=...
MOODLE_RESTRICTED_REST_TOKEN=...
MOODLE_MCP_ENDPOINT=https://moodle.example.edu/local/moodlia/mcp.php
MOODLE_TEST_SECTION_NUMBER=3
```

Required deployment variables for a standard Moodle server:

```text
DEPLOY_MODE=direct
SFTP_HOST=moodle.example.edu
SFTP_PORT=22
SFTP_USER=ubuntu
SFTP_AUTH_MODE=key
SFTP_KEY_PATH=C:\path\to\deployment-key.ppk
LOCAL_PLUGIN_SOURCE=.
LOCAL_PLUGIN_PACKAGE_PATH=D:\tmp\moodlia
SFTP_REMOTE_UPLOAD_PATH=/tmp/moodlia
MOODLE_SERVER_ROOT=/var/www/html
MOODLE_SERVER_PLUGIN_PATH=/var/www/html/local/moodlia
MOODLE_SERVER_PHP=php
```

Optional Docker deployment variables for the development/staging target:

```text
DEPLOY_MODE=docker
MOODLE_DOCKER_CONTAINER=moodle
MOODLE_CONTAINER_CLI_ROOT=/var/www/html
MOODLE_CONTAINER_ROOT=/var/www/html/public
MOODLE_CONTAINER_PLUGIN_PATH=/var/www/html/public/local/moodlia
```

Optional variables:

```text
DEPLOY_ENV=staging
MOODLE_ADMIN_UPGRADE_URL=https://moodle.example.edu/admin/index.php
PLAYWRIGHT_BASE_URL=https://moodle.example.edu
CLI_OUTPUT_FORMAT=json
CONTRACT_VERSION=0.1.0
MOODLE_CLI_BIN=cli/moodle-mcp.mjs
TEST_TIMEOUT_MS=30000
```

Use separate `.env` files per environment, for example:

```text
.env.local
.env.staging
.env.production
```

Secrets must stay out of git.

## Local Test Commands

The current automation foundation uses Node's built-in test runner for contract, parity, REST, MCP, and CLI smoke tests.

```text
npm run check:contract
npm run manifests:check
npm run test:static
npm run test:smoke
npm test
```

The smoke command limits Node test-file concurrency to four workers so a remote Moodle target is not overwhelmed by connection bursts and independent data workflows remain isolated enough for reliable cleanup.

## Release Artifact Checksums

The website shell in `dist/` is versioned source. Its operation index, rendered PNG
icons, and downloadable skill archives are derived files ignored by Git. The
`empaquetado/` directory is also ignored and contains only local release outputs.
Generate the complete derived set before running website or release checks:

```text
npm run release:artifacts
```

This command rebuilds the website derivatives, creates the plugin archive for the
release declared in `version.php`, removes obsolete plugin archives,
and writes the SHA-256 manifest. ZIP creation uses a maintained library and stable
timestamps. Set `SOURCE_DATE_EPOCH` when a release pipeline needs a specific
reproducible timestamp; otherwise the ZIP epoch defaults to 1980-01-01 UTC.

To regenerate only the SHA-256 manifest after the artifacts already exist:

```text
npm run release:checksums
```

The manifest is written to `empaquetado/SHA256SUMS.txt`. Release checks verify it with:

```text
npm run release:checksums:check
```

Checksums detect accidental or unauthorised artifact changes. They do not replace cryptographic signing; signing requires a separately managed release key and CI secret policy.

Smoke tests are skipped automatically when their required environment variables are not set:

| Test Group | Required Variables |
| --- | --- |
| REST smoke | `MOODLE_BASE_URL`, `MOODLE_REST_TOKEN` |
| MCP smoke | `MOODLE_BASE_URL`, `MOODLE_REST_TOKEN`; `MOODLE_MCP_ENDPOINT` is an optional URL override |
| CLI smoke | `MOODLE_BASE_URL`, `MOODLE_REST_TOKEN`; `MOODLE_CLI_BIN` is optional and defaults to `cli/moodle-mcp.mjs` |
| Restricted permission smoke | `MOODLE_BASE_URL`, `MOODLE_REST_TOKEN`, `MOODLE_RESTRICTED_REST_TOKEN` |
| Browser verification | `PLAYWRIGHT_BASE_URL` or `MOODLE_BASE_URL`, plus `MOODLE_USERNAME` and `MOODLE_PASSWORD` |

Focused transport and advanced-question checks can be run directly:

```text
node --test tests/smoke/protected-target-readonly.test.mjs
node --test tests/smoke/course-integrated-subelements.test.mjs
node --test tests/smoke/transport-parity.test.mjs
node --test tests/smoke/embedded-choice-question.test.mjs
node --test tests/smoke/restricted-permissions.test.mjs
```

The protected-target read-only smoke compares REST, MCP, and CLI result shapes for stable read operations and is safe for production-like targets because it does not create Moodle data. The transport parity smoke also compares REST, MCP, and CLI behavior, but it creates generated course data and should run only on targets where generated test data is acceptable. The embedded-choice smoke creates `gapselect` and `ddwtos` questions through REST, MCP, and CLI, adds them to a quiz, and verifies the quiz slot listing.
The integrated subelements smoke creates one generated course and validates Database URL fields and entries, Feedback captcha, Lesson shortanswer and numerical pages, Folder files, manual completion, manual gradebook items, course content listing, and native backup creation in a single end-to-end flow.

Use the protected release gate for production-like Moodle validation:

```text
npm run release:protected
npm run release:protected:php
```

The restricted permission smoke requires a token for a real non-admin user. That user must be allowed to use the MoodlIA external service and must have `local/moodlia:useapi`, but must not have administrative capabilities such as `moodle/category:manage`, `moodle/course:create`, `moodle/course:update`, `moodle/course:manageactivities`, `moodle/question:add`, `moodle/question:editall`, `mod/quiz:manage`, `moodle/backup:backupcourse`, `moodle/grade:manage`, or `mod/feedback:edititems`. The test first proves the token can call `get_current_user`, then verifies that course category, course, section, module, file, question, quiz-structure, gradebook, backup, and activity subelement writes are rejected across REST, MCP, and CLI coverage.

Print the effective test configuration with secrets redacted:

```text
npm run print:config
```

If the Moodle external service is enabled, request a REST token from username, password, and service shortname:

```text
npm run token:rest
```

The command stores the token as `MOODLE_REST_TOKEN` in `.env.test` and restricts the file permissions where the operating system supports it. It does not print credentials by default. Use `npm run token:rest -- --show-token` only when explicit terminal output is required and the terminal is not being logged.

To create or refresh the restricted permission-test user, role, service authorisation, and token through the Moodle admin UI:

```text
npm run token:restricted
```

This uses Playwright with `MOODLE_USERNAME` and `MOODLE_PASSWORD`, creates or reuses the `moodlia_restricted_api` user and `moodliarestrictedapi` role, grants only `local/moodlia:useapi` at system context, authorises the user for the MoodlIA external service, creates a service token, and stores it as `MOODLE_RESTRICTED_REST_TOKEN` in `.env.test`.

Browser tests use Playwright and require dependencies to be installed first:

```text
npm install
npm run test:browser
```

Print deployment commands for the configured target. `DEPLOY_MODE=direct` prints commands for a normal Moodle server; `DEPLOY_MODE=docker` prints commands for the development/staging container target:

```text
npm run plugin:package
npm run deploy:commands
```

When the target uses a PuTTY `.ppk` private key on Windows, scripted deployment can use WinSCP:

```text
npm run deploy:winscp:test
npm run deploy:winscp
```

Run PHP syntax checks on the Moodle server after deployment. This validates the plugin with the server PHP runtime and Moodle filesystem layout instead of a local developer machine:

```text
npm run plugin:php:lint:server
```

If files are already copied and only Moodle upgrade/cache purge is needed:

```text
npm run deploy:winscp:upgrade
```

## Continuous Integration

The default GitHub Actions workflow is `.github/workflows/ci.yml`. It is
intentionally limited to checks that can run without a Moodle target:

```text
npm ci
npm run release:check
```

The CI job runs on Ubuntu and validates JavaScript syntax, PHP syntax on PHP
8.2, generated manifests, the canonical operation contract, static tests,
dependency audit, plugin packaging, and the Marketplace archive.

Remote Moodle smoke tests and browser verification are not in the default CI workflow. They require environment-specific secrets, a reachable Moodle instance, and permission to create generated test data.

The separate `.github/workflows/moodle-ci.yml` workflow installs Moodle 5.2 and
validates the plugin on PHP 8.2 and 8.4 with both PostgreSQL and MariaDB. It runs
PHP lint, Moodle Code Checker, Moodle PHPDoc Checker, structural validation, and
the plugin PHPUnit suite. This workflow is the Marketplace compatibility gate
and must pass before uploading a release.

## Deployment Safeguards

Before any SFTP upload:

- Print the deployment environment name.
- Print the remote host.
- Print the temporary server upload path.
- Print the final Moodle plugin path.
- Confirm the Moodle root contains `admin/cli/upgrade.php`.
- Confirm the final plugin path ends with `/local/moodlia`.
- Exclude `.git`, `node_modules`, local test output, screenshots, coverage, temporary files, and `.env` files.
- Build or validate generated contract artifacts.
- Run local static checks where available.
- Create or identify a rollback artifact.

For production, require a manual confirmation step before upload.

## Suggested Deploy Flow

1. Load environment configuration.
2. Validate required variables.
3. Build or validate contract artifacts.
4. Run static parity tests.
5. Upload the plugin folder by SFTP to `SFTP_REMOTE_UPLOAD_PATH`.
6. Copy the uploaded folder to `<moodle-root>/local/moodlia`, or into the Moodle Docker container when using the development/staging target.
7. Run Moodle plugin upgrade from the Moodle root.
8. Purge Moodle caches.
9. Verify service declarations were discovered.
10. Run API smoke tests.
11. Run MCP smoke tests.
12. Run CLI smoke tests.
13. Run browser verification against the Moodle-visible state.
14. Clean up generated test data.

## Standard Moodle Server Deployment

The local plugin component is `local_moodlia`, so the Moodle plugin folder name is `moodlia`. For a Moodle local plugin, the final path on a standard server is:

```text
<moodle-root>/local/moodlia
```

Examples:

```text
/var/www/html/local/moodlia
/var/www/moodle/local/moodlia
/home/example/public_html/moodle/local/moodlia
```

Upload the plugin folder to the server temporary path:

```text
sftp -P 22 -i "C:\path\to\key.ppk" ubuntu@moodle.example.edu
put -r moodlia /tmp/moodlia
```

If the `.ppk` key is not accepted by Windows OpenSSH, use WinSCP. The repository includes WinSCP scripts under `tools/winscp-*.txt` with the pinned server host key.

Connect by SSH:

```text
ssh -p 22 -i "C:\path\to\key.ppk" ubuntu@moodle.example.edu
```

Then run these commands on a standard Moodle server:

```text
rm -rf /var/www/html/local/moodlia
mkdir -p /var/www/html/local
cp -a /tmp/moodlia /var/www/html/local/moodlia
cd /var/www/html
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

If CLI access is unavailable, use the Moodle admin upgrade page:

```text
<MOODLE_BASE_URL>/admin/index.php
```

## Docker Development Deployment

The development/staging target used by this repository runs Moodle inside Docker and serves Moodle from `/var/www/html/public`. For that target, the final plugin path inside the container is:

```text
/var/www/html/public/local/moodlia
```

Use these commands on the server:

```text
sudo docker cp /tmp/moodlia moodle:/var/www/html/public/local/moodlia
sudo docker exec -w /var/www/html moodle php admin/cli/upgrade.php --non-interactive
sudo docker exec -w /var/www/html moodle php admin/cli/purge_caches.php
```

One-line server command:

```text
sudo docker cp /tmp/moodlia moodle:/var/www/html/public/local/moodlia && sudo docker exec -w /var/www/html moodle php admin/cli/upgrade.php --non-interactive && sudo docker exec -w /var/www/html moodle php admin/cli/purge_caches.php
```

Replace `moodlia` only if the plugin folder name changes. Keep the container root as `/var/www/html/public`.

## Moodle Upgrade Verification

After changing plugin files, run the Moodle upgrade process. On a standard server:

```text
cd /var/www/html
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

On the development/staging Docker target:

```text
sudo docker exec -w /var/www/html moodle php admin/cli/upgrade.php --non-interactive
sudo docker exec -w /var/www/html moodle php admin/cli/purge_caches.php
```

If CLI access is unavailable, use the Moodle admin upgrade page:

```text
<MOODLE_BASE_URL>/admin/index.php
```

After service changes, verify that `db/services.php` declarations were discovered through Moodle web service administration or API documentation:

```text
Site administration > Server > Web services > API Documentation
```

## API Verification

Minimum REST smoke checks:

```text
get_current_user
get_courses
get_course_categories
get_course_contents
get_calendar_events
get_enrolled_users
get_groups
get_group_members
```

Recommended write checks in generated test courses:

```text
create_course_category
update_course_category
delete_course_category
move_course
create_calendar_event
update_calendar_event
delete_calendar_event
enrol_user
unenrol_user
create_group
update_group
delete_group
add_group_member
remove_group_member
create_section
update_section
delete_section
create_module
update_module
duplicate_module
move_module
delete_module
upload_folder_file
get_folder_files
download_folder_file
get_resource_files
download_resource_file
delete_folder_file
get_question_banks
get_question_categories
create_question_category
update_question_category
delete_question_category
create_question
update_question
get_quiz_questions
start_quiz_attempt
get_quiz_attempts
add_question_to_quiz
```

Every write check must include a read-after-write assertion that verifies the Moodle-visible result.

## MCP Verification

The Moodle-hosted MCP endpoint is:

```text
<MOODLE_BASE_URL>/local/moodlia/mcp.php
```

MCP uses the same token as REST. Send `MOODLE_REST_TOKEN` as the bearer token for MCP requests.

Minimum MCP smoke checks:

```text
server/discover (2026-07-28 request metadata and routing headers)
tools/list
tools/call get_current_user
tools/call get_courses
tools/call get_course_categories
tools/call get_course_contents
tools/call get_calendar_events
tools/call get_enrolled_users
tools/call get_groups
tools/call get_group_members
```

Required MCP assertions:

- Modern requests are independently authenticated and include matching protocol, method, and tool-name metadata in the HTTP headers and JSON-RPC body.
- Legacy clients can still complete `initialize` and `notifications/initialized` before using the tool surface.
- Every expected operation appears in `tools/list`.
- Every tool schema matches the canonical contract.
- Every listed tool has a dispatch handler.
- `tools/call` results match REST result shape for compatible operations.
- Shared advanced-question workflows return the same canonical shape as REST when called through MCP.

## CLI Verification

The Node CLI uses the Moodle REST API directly through `client/moodle-rest-client.mjs`. It requires `MOODLE_BASE_URL` and `MOODLE_REST_TOKEN` and does not require an MCP endpoint.

Minimum CLI smoke checks:

```text
node cli/moodle-mcp.mjs get-current-user --format json
node cli/moodle-mcp.mjs get-courses --limit 5 --format json
node cli/moodle-mcp.mjs get-course-categories --format json
node cli/moodle-mcp.mjs get-calendar-events --course-id 42 --time-from 1893452400 --time-to 1893463200 --format json
node cli/moodle-mcp.mjs get-enrolled-users --course-id 42 --format json
node cli/moodle-mcp.mjs get-groups --course-id 42 --format json
```

When the public package is linked or installed, the same commands can be run as:

```text
moodlia get-current-user --format json
moodlia get-courses --limit 5 --format json
```

Write commands use the same canonical operation names in kebab-case. Object parameters are JSON strings:

```text
node cli/moodle-mcp.mjs create-module --course-id 42 --section-number 1 --module-type page --name "Reading" --options "{\"content\":\"<p>Hello</p>\"}" --format json
node cli/moodle-mcp.mjs create-module --course-id 42 --section-number 0 --module-type qbank --name "MoodlIA Question Bank" --format json
node cli/moodle-mcp.mjs move-course --course-id 42 --category-id 12 --format json
```

On Windows PowerShell, prefer the PowerShell shim when passing complex JSON options:

```powershell
$options = '{ "content": "<p>Hello from PowerShell.</p>" }'
.\node_modules\.bin\moodlia.ps1 create-module --course-id 42 --section-number 1 --module-type page --name "Reading" --options $options
```

Use `--raw` or `--no-validate-response` only to skip response-shape validation for a usable Moodle response. The CLI still validates command parameters before making the REST request.

Required CLI assertions:

- Every CLI command maps to a canonical operation.
- Command arguments validate against the operation schema.
- JSON output matches the shared result shape.
- Non-zero exits are used for failed operations.
- Errors use normalized error codes.
- Shared advanced-question workflows return the same canonical shape as REST when called through the CLI.

## Npm Package Verification

The public npm package is generated from the development repository and must stay minimal:

```text
npm run npm:sync
npm run npm:sync:check
npm run npm:pack:dry-run
```

The generated package is `packages/moodlia` and the installed binary is `moodlia`.

Required npm package assertions:

- The generated package contains only the public CLI/client files documented in `docs/npm-package.md`.
- The package README documents installation, configuration, command usage, programmatic client usage, published files, and security expectations.
- The published contract excludes unpublished transport metadata.
- No local credentials, env files, deployment scripts, browser reports, smoke tests, or development-only tools are included.
- `npm pack --dry-run` reports the expected file list before publishing.

## Client Verification

Node automation can call the same REST-backed operation surface without shelling out to the CLI:

```js
import {
  createMoodleClient,
  loadContractFromFile
} from './client/moodle-rest-client.mjs';

const contract = loadContractFromFile('contract/operations.json');
const client = createMoodleClient({
  baseUrl: process.env.MOODLE_BASE_URL,
  token: process.env.MOODLE_REST_TOKEN,
  contract
});

const courses = await client.get_courses({ limit: 5 });
const page = await client.create_module({
  course_id: 42,
  section_number: 1,
  module_type: 'page',
  name: 'Reading',
  options: {
    content: '<p>Hello</p>'
  }
});
```

Required client assertions:

- Canonical snake_case methods map directly to contract operations.
- Required parameters, enum values, booleans, numbers, integers, and object parameters validate against `contract/operations.json`.
- Object parameters are serialized once for Moodle REST.
- Moodle REST payload errors are reported with the target function name.

## Generated Manifests

Transport manifests are generated from `contract/operations.json`:

```text
npm run manifests:generate
npm run manifests:check
```

Run `manifests:generate` after adding, removing, or renaming an operation. `manifests:check` fails when generated manifest files drift from the canonical contract.

## Browser Verification

Use Playwright for browser-visible Moodle workflows that can run outside Moodle's Behat test environment. These tests verify Moodle core pages and generated course state; they are not tests for a custom MoodlIA operation UI.

The browser suite logs in with `MOODLE_USERNAME` and `MOODLE_PASSWORD`. It does not require a single fixed course. For broad Moodle UI checks, it validates the dashboard and course index. For targeted manual course visibility checks, define a comma-separated list:

```text
MOODLE_TEST_COURSE_IDS=42,43,44
```

Recommended checks:

- Login or authenticated session setup.
- Open generated or explicitly configured courses.
- Verify created sections are visible.
- Verify created modules are visible in the expected section.
- Open uploaded/downloaded files where possible.
- Verify updated names and descriptions are rendered.
- Verify deleted entities are absent.

Browser tests should not pass only because an API response was successful. They must inspect the actual Moodle page state.

### Full Generated Course Fixture

To create one rich generated course without deleting existing courses, run:

```text
npm run moodle:create-demo-course
```

To reset the remote Moodle course list first, use the explicit reset command only on disposable Moodle instances:

```text
npm run moodle:reset-full-course
```

The reset command deletes every manageable course returned by `get_courses`, then creates a fresh visible course. Both commands create a visible course containing:

- A generated Moodle course category containing the generated course.
- A generated course calendar event visible in Moodle's calendar day view.
- A generated content section.
- The current token user enrolled as a student through Moodle manual enrolment.
- A generated group containing the enrolled user.
- A page activity.
- A book activity with generated chapters and a generated subchapter.
- An assignment activity with online text submissions enabled, a generated online-text submission, submitted-for-grading status, saved grade, feedback comment, gradebook item, and user grade row.
- A label activity rendered inline on the course page.
- A URL activity with a visible external link.
- A forum activity with a generated description, generated discussion, generated reply, and updated reply.
- A glossary activity with a generated entry, search verification, and updated entry.
- A wiki activity with a generated page, page listing verification, and updated page content.
- A folder activity with an uploaded file.
- A visible course shared question bank named `MoodlIA Question Bank`.
- A quiz activity.
- A course shared question category.
- A quiz-private question category.
- A true/false question in the course shared bank.
- A multiple-choice question in the course shared bank.
- A numerical question in the course shared bank.
- An essay question in the course shared bank.
- A short-answer question in the quiz-private bank.
- All generated questions added to the quiz.
- A started quiz attempt or preview for the authenticated test user.
- A `MoodlIA Question Bank Map` page with direct links to the shared bank, private bank, and quiz questions page.

The fixture command prints a JSON payload with the generated course, category, module, file, question, quiz, and attempt ids. Keep that output for follow-up browser checks or cleanup.

### Generated Data Cleanup

Use the selective cleanup command before using the destructive reset command:

```text
npm run moodle:cleanup-generated
```

The default mode is a dry run. It inspects generated courses and course categories visible to `MOODLE_REST_TOKEN`, then prints the cleanup plan as JSON. A course is eligible only when its full name starts with `MoodlIA` or its short name starts with `moodlia-`. A course category is eligible only when its name starts with `MoodlIA`.

To delete matched generated data:

```text
npm run moodle:cleanup-generated:execute
```

To limit cleanup to known ids:

```text
node tools/cleanup-generated-test-data.mjs --course-id=42,43 --category-id=12 --execute
```

Explicit ids are still checked against the generated-data markers before deletion. Non-empty generated course categories are skipped, so the command can be run repeatedly after generated courses are removed.

The quiz questions page shows questions used by the quiz and can include questions stored outside the quiz-private bank. To verify storage ownership, inspect the actual question bank category URLs.

For a generated course, inspect both banks with:

```text
npm run moodle:inspect-question-banks -- --shared-cmid=<qbank_cmid> --shared-category=<category_id> --shared-context=<context_id> --quiz-cmid=<quiz_cmid> --private-category=<category_id> --private-context=<context_id>
```

The same state should also be visible through the shared API contract:

```text
node cli/moodle-mcp.mjs get-question-banks --course-id=<course_id>
node cli/moodle-mcp.mjs get-course-categories
node cli/moodle-mcp.mjs get-calendar-events --course-id=<course_id> --time-from=<start_timestamp> --time-to=<end_timestamp>
node cli/moodle-mcp.mjs get-enrolled-users --course-id=<course_id>
node cli/moodle-mcp.mjs get-groups --course-id=<course_id>
node cli/moodle-mcp.mjs get-group-members --course-id=<course_id> --group-id=<group_id>
node cli/moodle-mcp.mjs get-course-contents --course-id=<course_id>
node cli/moodle-mcp.mjs get-folder-files --course-id=<course_id> --module-id=<folder_cmid>
node cli/moodle-mcp.mjs get-book-chapters --course-id=<course_id> --module-id=<book_cmid> --include-content=true
node cli/moodle-mcp.mjs search-glossary-entries --course-id=<course_id> --module-id=<glossary_cmid> --query="MoodlIA shared interface" --full-search=true --include-not-approved=true
node cli/moodle-mcp.mjs get-wiki-pages --course-id=<course_id> --module-id=<wiki_cmid> --include-content=true
node cli/moodle-mcp.mjs duplicate-module --course-id=<course_id> --module-id=<cmid> --section-number=<section_number> --name="Duplicated activity"
node cli/moodle-mcp.mjs move-module --course-id=<course_id> --module-id=<cmid> --section-number=<section_number>
node cli/moodle-mcp.mjs get-quiz-questions --quiz-module-id=<quiz_cmid>
node cli/moodle-mcp.mjs start-quiz-attempt --quiz-module-id=<quiz_cmid>
node cli/moodle-mcp.mjs get-quiz-attempts --quiz-module-id=<quiz_cmid> --status=all --include-previews=true
node cli/moodle-mcp.mjs get-question-categories --course-id=<course_id> --bank-scope=course_shared --question-bank-module-id=<qbank_cmid>
node cli/moodle-mcp.mjs get-question-categories --course-id=<course_id> --bank-scope=quiz_private --quiz-module-id=<quiz_cmid>
```

The expected result is:

- The course shared bank contains only the shared-bank questions.
- The generated course appears inside the generated course category page.
- The generated calendar event appears on the Moodle calendar day page.
- The generated forum discussion appears in the forum activity and the updated reply appears on the discussion page.
- The generated glossary entry appears in the glossary activity and search results.
- The generated wiki page appears in the wiki activity with the updated content.
- The generated assignment submission appears in Moodle's assignment grading view, with the submitted status, online text, saved grade, and feedback comment visible.
- Moodle's user grade report shows the generated assignment grade item, saved grade, and grade range.
- Subsection, Book, LTI, Database, Lesson, and Workshop pages expose their generated subelements or Moodle-visible empty-state controls. The Book page must show generated chapter navigation and rendered chapter content.
- Database fields and the updated generated entry appear in the Database activity page.
- Workshop phase, submission table, assessment instructions, and generated submission detail page are visible in Moodle.
- The quiz-private bank contains only the quiz-private question.
- The quiz questions page can show all generated questions because all are used by the quiz.
- The quiz view can continue or open the generated attempt, and the attempt page renders each generated question subelement.
- The participants page shows the enrolled user, email when visible, and the assigned student role.
- The groups page shows the generated group and the enrolled member.

## Behat Verification

Use Moodle Behat when the test must validate Moodle UI behavior inside Moodle's own acceptance test framework.

Appropriate Behat targets:

- Moodle role/capability behavior visible in UI.
- Course page behavior after plugin-created changes.
- Activity rendering through Moodle pages.
- Workflows where Moodle core UI state matters more than external transport behavior.

Behat should run in Moodle's test environment, not against production.

## Test Data Isolation

All automated tests should use generated names:

```text
MoodlIA Test <timestamp> <random>
```

Test data should include cleanup hooks for:

- Sections.
- Modules.
- Folder files.
- Question categories.
- Questions.
- Quizzes or quiz slots created during tests.

Cleanup should run even when assertions fail.

For broad post-failure cleanup, run:

```text
npm run moodle:cleanup-generated
npm run moodle:cleanup-generated:execute
```

## Rollback Notes

Before deployment:

- Record the current plugin version.
- Keep a copy or release artifact of the previous plugin files.
- Know whether the release includes database migrations.

Rollback options:

- Re-upload the previous plugin files when no irreversible schema change exists.
- Restore from a database backup for releases with destructive or incompatible schema changes.
- Disable new external functions or tokens if the issue is limited to API exposure.

Moodle does not support plugin downgrades as a normal upgrade path, so rollback planning must happen before deployment.

## Excluded Files

Deployment uploads should exclude:

```text
.git/
.env
.env.*
node_modules/
coverage/
test-results/
playwright-report/
screenshots/
tmp/
*.log
*.local
```

## Verification Matrix

| Capability | REST | MCP | CLI | Browser | Behat |
| --- | --- | --- | --- | --- | --- |
| Current user | Required | Required | Required | Optional | Optional |
| Course list | Required | Required | Required | Optional | Optional |
| Course category CRUD | Required | Required | Required | Required | Optional |
| Course calendar event CRUD | Required | Required | Required | Required | Optional |
| Forum discussion and reply CRUD-like flow | Required | Required | Required | Required | Optional |
| Enrol/unenrol user | Required | Required | Required | Required | Recommended |
| Participant list | Required | Required | Required | Required | Recommended |
| Group CRUD and membership | Required | Required | Required | Required | Recommended |
| Create/update/delete section | Required | Required | Required | Required | Optional |
| Create/update/delete activity | Required | Required | Required | Required | Optional |
| Upload/download/delete file | Required | Required | Required | Required | Optional |
| Create/update/delete question category | Required | Required | Required | Optional | Optional |
| Create/update question | Required | Required | Required | Optional | Optional |
| Add question to quiz | Required | Required | Required | Required | Recommended |

## Official References

- External Services: https://moodledev.io/docs/5.0/apis/subsystems/external
- External file handling: https://moodledev.io/docs/5.0/apis/subsystems/external/files
- Plugin upgrades: https://moodledev.io/docs/5.3/guides/upgrade
- PHPUnit testing: https://moodledev.io/docs/5.3/guides/testing
- Behat: https://moodledev.io/general/development/tools/behat
- Running Behat tests: https://moodledev.io/general/development/tools/behat/running
