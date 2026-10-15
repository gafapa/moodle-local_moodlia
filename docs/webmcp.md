# WebMCP Browser Adapter

WebMCP belongs to `local_moodlia`. It is not an independent Moodle plugin or an AI service. The existing REST and server MCP interfaces remain available.

## Runtime

1. An administrator enables `local_moodlia/webmcp_enabled` (default: off).
2. The Moodle output hook loads the packaged AMD module only for authenticated, non-guest users with the existing system-level `local/moodlia:useapi` capability.
3. A supported secure browser registers the full canonical catalogue plus `moodlia_open_page`. Unsupported browsers return before making any WebMCP requests.
4. The same-origin POST endpoint requires a logged-in session and `sesskey`, checks that WebMCP is still enabled, and resolves operation names against the plugin manifest and AJAX service declarations.
5. The bridge converts JSON-object parameters to the existing external API encoding, keeps native arrays intact, and calls `external_api::call_external_function(..., true)`. Moodle validates parameters, context, capabilities, and return values using the same external adapters as the existing transports.

The PHP operation layer is the authority. The client does not embed tokens, supply a user identity, bypass capabilities, or restrict an operation to the course currently displayed. File-related operations retain their existing draft ownership and Moodle file access checks. From 0.1.218, the session endpoint accepts a multipart `upload` action with `repo_upload_file` and the current sesskey. It delegates to the enabled native upload repository, checks its capability, scans for viruses, enforces Moodle's user upload limit, and creates a fresh user-owned draft. It returns `draft_item_id` and `filename` for normal operation parameters. Binary data is never included in WebMCP tool arguments.

## Optional CLI Bridge

MoodlIA CLI 0.5.0 bundles an optional `moodlia-browser` component using Playwright
1.64.1 and visible Chromium. `moodlia browser install` installs it separately;
the base CLI has no Playwright dependency. Open a Moodle URL, sign in manually,
and call a direct plugin operation with `--transport webmcp`. The browser stays
open between CLI processes and navigation shows work in the same tab. IPC is
local and authenticated; no arbitrary script execution or TCP debug endpoint is
exposed. Core and adaptive commands retain their REST transports.

```bash
moodlia browser install
moodlia browser open --url https://moodle.example/course/view.php?id=7
# Sign in manually, then discover tools.
moodlia browser tools
moodlia get-course-contents --course-id 7 --transport webmcp
moodlia browser close
```

The browser component preserves the normal upload flags and can download native
Moodle file URLs using the signed-in session. See the CLI's `browser/README.md`
for the profile directory, file limits, installation/removal, and exact commands.
WebMCP exposes operation tools, not MCP protocol resources, prompts, or
notifications. Failed or cancelled writes are never retried automatically.

## Visible Navigation

Call `moodlia_open_page` with one of these destinations:

| Destination | Required argument | Additional access requirement |
| --- | --- | --- |
| `course` | `course_id` | Native course access and `moodle/course:view` |
| `activity` | `module_id` | Native course/module access and current-user module visibility |
| `assignment_grader` | `module_id` | Assignment activity and `mod/assign:grade` |
| `gradebook` | `course_id` | Native course access and `moodle/grade:viewall` |

An optional `course_id` on activity navigation must match the activity's course. The server constructs the destination URL; arbitrary URLs are not accepted. The client changes the current tab only after a successful server response and a same-origin HTTPS check (HTTP is allowed only on loopback). Existing unsaved-change handling is not removed. Writes and navigation are separate explicit tool calls so an agent can choose what to show without unexpectedly discarding an open form.

## Lifecycle and Privacy

Tool registrations use an AbortController. Leaving a document releases them, and restoration from the browser back/forward cache registers them again. Registration failures are isolated from Moodle's page UI. Execution cancellation is forwarded to fetch; it is not a database rollback and must not trigger blind retries of writes.

Tool results may contain student or teacher data and authored HTML. They carry an untrusted-content hint. The plugin makes no AI-provider requests and stores no additional personal data. The browser agent's processing destination must comply with the institution's policy; use local processing where required. No cross-origin tool exposure is configured.

## Build and Checks

```bash
npm ci
npm run webmcp:build
npm run check
npx playwright install chromium
npm run test:webmcp:browser
```

The native browser check requires PHP on PATH or `MOODLE_SERVER_PHP`. It obtains the actual 251 descriptors from the PHP bridge, registers them with Chromium's native WebMCP API, executes an operation against a different course through a fictional server fixture, and verifies same-tab navigation. It uses a temporary browser with the testing feature enabled; it does not change the user's browser flags or contact a Moodle site.

The JavaScript suite covers unsupported browsers, session/CSRF transport, cross-course arguments, cancellation, navigation origin validation, registration cleanup, and build parity. `tests/webmcp_bridge_test.php` adds Moodle PHPUnit cases for the disabled setting, capabilities, catalogue completeness, argument encoding, cross-course reads, and native navigation. Run this suite in the project's Moodle PHPUnit environment before deployment; the browser fixture does not establish server permission enforcement on a deployed Moodle installation.

### Local Validation on 2026-10-15

- All 46 static/client and coverage-gate tests passed, including definitive Moodle errors, endpoint origins, duplicate initialization, recovery, browser API fallback, and back-forward cache lifecycle.
- PHP 8.2.34 syntax validation passed for the plugin sources, including the new bridge, output hook, endpoint, settings, and PHPUnit cases.
- Native Chromium WebMCP accepted all 251 descriptors. The three browser checks passed: full catalogue registration, cross-course execution through a fictional server fixture, and authorized same-tab navigation.
- CLI browser integration invokes all 250 operation adapters through native WebMCP using fictional contract-shaped responses, validates typed arguments/results, and checks session reuse, same-tab navigation, multipart upload bytes/draft propagation, download SHA-256/no-overwrite, IPC authentication, write non-retry, and close/reopen. This is adapter validation, not execution of 250 Moodle PHP operations.
- The installable 0.1.219 archive contains the AMD build, output hook, optional setting, multipart endpoint, bridge, and expanded PHP tests. Archive validation, byte-for-byte source comparison, and SHA-256 checksum verification passed.
- Native qualification passed on Moodle 4.5.15/PHP 8.3.15/PostgreSQL 16.15 and Moodle 5.2.4/PHP 8.3.15/PostgreSQL 17.11: 103 PHP tests and 11 real browser/CLI scenarios per profile. The bridge-specific suite passed 20 tests and 300 assertions on both.
- Bridge statement coverage measured on Moodle 5.2 is 97.48% (116/119). The 4.5 coverage-report generation did not complete; no 4.5 percentage is claimed. The CI workflow now enforces a fail-closed 95% bridge gate with an explicit report-generation memory budget.
- The full remote CI matrix has not been run for these working-tree changes. The two Docker profiles used only synthetic data on the authorized Oracle host; their containers, anonymous volumes, network, data, uploaded files and SSH tunnel were removed. Production Moodle sites and user browser settings were preserved. See [the qualification report](webmcp-validation-2026-10-15.md).

## References

- [WebMCP imperative API](https://developer.chrome.com/docs/ai/webmcp/imperative-api)
- [WebMCP tool security](https://developer.chrome.com/docs/ai/webmcp/secure-tools)
- [Moodle external API implementation](https://github.com/moodle/moodle/blob/MOODLE_405_STABLE/lib/external/classes/external_api.php)
- [Moodle output hook](https://github.com/moodle/moodle/blob/MOODLE_405_STABLE/lib/classes/hook/output/before_standard_top_of_body_html_generation.php)
