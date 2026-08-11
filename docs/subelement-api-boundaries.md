# Activity Subelement API Boundaries

MoodlIA exposes activity subelements when Moodle provides a public API path that can validate context, capabilities, ownership, and side effects without raw SQL or plugin-owned tables.

## Implemented Subelement Areas

- Assignment submissions and grades through Moodle Assignment APIs.
- Book chapter listing and view registration through Moodle Book APIs.
- Book chapter creation, update, movement, and deletion through one audited Moodle Book DML boundary because Moodle Book has no public chapter writer API in the supported target. The boundary mirrors Moodle Book's own `edit.php`, `delete.php`, and `move.php` behavior and is the only approved exception to the usual no-DML rule.
- Course activity-completion audit and repair through Moodle course-module APIs and MoodlIA's audited `update_module` path. This covers stale Book grade-completion rules and supports dry-run repair before changing Moodle state.
- Choice options, responses, and results through Moodle Choice APIs.
- Database fields and entries through Moodle Database APIs.
- Feedback item reads, page item reads, analysis reads, finished response reads, and the complete Moodle 5.0 core item set through Moodle Feedback APIs and item class APIs: create/update for `textfield`, `textarea`, `numeric`, `multichoice`, `multichoicerated`, `label`, and `info`, plus create-only `captcha` and `pagebreak` items according to their native semantics.
- Folder and Resource file reads, downloads, and deletes through Moodle File API rules.
- Forum discussions, posts, and discussion state through Moodle Forum APIs.
- Glossary entries, categories, authors, browse filters, search, pending approval reads, and entry CRUD through Moodle Glossary APIs.
- Lesson page, jump, access, grade, timer, and attempt report reads through Moodle Lesson APIs.
- Lesson content page creation, update, deletion, branch jump mutation, and all Moodle 5.0 core question-page types through Moodle Lesson page component APIs: truefalse, shortanswer, multichoice, numerical, essay, and matching.
- Quiz question slots, attempts, attempt data, attempt review, review options, grades, and view events through Moodle Quiz APIs.
- Wiki pages, subwikis, files, and view events through Moodle Wiki APIs.
- Workshop phases, user plans, grades, grade reports, reviewer/submission assessment reads, allocations, assessment form-definition reads, assessment updates, assessment evaluation, and submissions through Moodle Workshop APIs.
- All Moodle 5.0 core Workshop grading strategies—accumulative, comments, number-of-errors, and rubric—through Moodle Workshop grading strategy APIs, limited to setup phase and the active strategy.

## Intentionally Not Exposed Yet

These areas remain blocked until a stable Moodle API path is identified and tested:

- Third-party Feedback item types and direct response-value mutation.
- Lesson structural cluster/end-marker page creation and unsupported payloads outside the documented content and six core question-page schemas.
- Third-party Workshop grading strategies and standalone assessment creation outside Moodle's allocation flow.

## Required Standard Before Adding One

Every new subelement write must satisfy all of these conditions:

- It uses Moodle component APIs or external APIs, not raw SQL or plugin-owned tables. New `$DB` writes are blocked unless Moodle core has no public API for the target subelement and the implementation is isolated in a documented helper that mirrors the owning Moodle component's own behavior.
- It validates system context for `local/moodlia:useapi`.
- It validates course and module context before mutation.
- It verifies that the target subelement belongs to the selected activity.
- It checks the narrow Moodle capability required for the action.
- It returns a canonical shape shared by REST, MCP, and CLI.
- It has static parity coverage and at least one smoke test that confirms Moodle-visible state.
- It leaves generated courses behind on failure when that helps inspect broken state.

## Preferred Implementation Order

1. Lesson structural cluster and end-marker pages, only if portable course generation needs them and their navigation invariants can be smoke tested.
2. Third-party Feedback item types or Workshop grading strategies, only through explicit extension contracts for installed subplugins.
3. Direct Feedback response mutation or standalone Workshop assessment creation, only after validating participant ownership, attempt state, grading side effects, and Moodle event behavior.

See [remaining-api-validation.md](remaining-api-validation.md) for the current source-level validation notes and the evidence required before exposing these writes.
