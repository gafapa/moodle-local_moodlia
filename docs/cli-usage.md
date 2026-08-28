# CLI Usage

This guide explains how to use the public `moodlia` CLI after the MoodlIA Moodle plugin is installed and a REST token has been created.

The development repository also has `node cli/moodle-mcp.mjs ...` for local automation. Installed users should use `moodlia ...`.

## Install

Install globally:

```text
npm install -g moodlia
```

Or install in one Node project:

```text
npm install moodlia
```

## Configure

Use environment variables:

```text
MOODLE_BASE_URL=https://moodle.example.edu
MOODLE_REST_TOKEN=your-token
```

PowerShell example:

```powershell
$env:MOODLE_BASE_URL = "https://moodle.example.edu"
$env:MOODLE_REST_TOKEN = "your-token"
```

Bash example:

```bash
export MOODLE_BASE_URL="https://moodle.example.edu"
export MOODLE_REST_TOKEN="your-token"
```

The CLI also reads a local `.env` file from the current working directory:

```text
MOODLE_BASE_URL=https://moodle.example.edu
MOODLE_REST_TOKEN=your-token
```

Do not commit `.env` files or tokens.

`MOODLE_BASE_URL` must be the public HTTPS URL of the Moodle site, including any installation subdirectory such as `https://example.edu/learning`. The client preserves that path when resolving REST endpoints. Plain HTTP is rejected for remote hosts; loopback HTTP remains available for local development.

## Discover Commands

Show all commands:

```text
moodlia --help
```

Show one command:

```text
moodlia create-module --help
```

Every command uses the canonical operation name in kebab-case:

```text
get_courses -> moodlia get-courses
create_question_category -> moodlia create-question-category
get_quiz_results_report -> moodlia get-quiz-results-report
```

All outputs are JSON by default. Errors are JSON on stderr with:

```json
{
  "error": true,
  "code": "invalid_parameters",
  "message": "course-id must be an integer.",
  "details": {}
}
```

Use strict response validation by default. For advanced automation against a Moodle instance that returns a useful payload with fields outside the current contract, skip response validation:

```text
moodlia get-question-categories --course-id <course_id> --bank-scope quiz_private --quiz-module-id <quiz_module_id> --raw
moodlia create-question-category --course-id <course_id> --name "Quiz Questions" --bank-scope quiz_private --quiz-module-id <quiz_module_id> --no-validate-response
```

Both flags call the same Moodle REST function; they only skip client-side response-shape validation.

## JSON Parameters

Parameters declared as objects must be passed as JSON strings.

PowerShell:

```powershell
moodlia create-module --course-id 42 --section-number 1 --module-type page --name "Reading" --options '{ "content": "<p>Hello</p>" }'
```

When using a project-local install on Windows PowerShell, prefer the PowerShell shim because `moodlia.cmd` can mangle complex JSON quoting:

```powershell
$options = '{ "content": "<p>Hello from PowerShell.</p>" }'
.\node_modules\.bin\moodlia.ps1 create-module --course-id 42 --section-number 1 --module-type page --name "Reading" --options $options
```

Bash:

```bash
moodlia create-module --course-id 42 --section-number 1 --module-type page --name "Reading" --options '{"content":"<p>Hello</p>"}'
```

When JSON quoting becomes awkward, keep the JSON compact and validate it before running the command.

## Basic Smoke Check

Verify token and site connectivity:

```text
moodlia get-current-user
moodlia get-courses --limit 10
moodlia get-course-categories
```

If these fail, fix Moodle web service configuration before testing write operations.

## Course Workflow

Create a course category:

```text
moodlia create-course-category --name "Generated Courses" --visible true
```

Reuse a category with the same name and parent when it already exists:

```text
moodlia create-course-category --name "Generated Courses" --visible true --reuse-existing true
```

Create a course:

```text
moodlia create-course --fullname "MoodlIA Demo Course" --shortname "moodlia-demo-001" --category-id <category_id> --visible true --summary "<p>Generated with MoodlIA.</p>" --summary-format html --enable-completion true
```

Read course metadata and contents:

```text
moodlia get-course-details --course-id <course_id>
moodlia get-course-contents --course-id <course_id>
moodlia get-moodlia-status
```

`get-course-contents` includes course-module completion metadata (`completion`, `completion_view`, `completion_grade_item_number`, and `completion_expected`) so automation can audit whether an activity is configured for manual completion, view completion, or grade-based completion without calling each activity endpoint first.

`get-moodlia-status` is the MoodlIA-native diagnostic endpoint for tokens that cannot call Moodle's generic site-info web service. It returns the plugin version, Moodle version, current user, API capability state, REST service shortname, and the registered MoodlIA function list.

## Plugin Inventory And State

Plugin administration requires both `local/moodlia:useapi` and `local/moodlia:manageplugins`. The latter is not granted to any role archetype by default and should be assigned only to a dedicated administrative service account.

List all additional plugins:

```text
moodlia list-plugins --source additional
```

Filter by Moodle plugin type or installation status:

```text
moodlia list-plugins --plugin-type mod --status uptodate
```

Inspect one plugin and its dependency graph:

```text
moodlia get-plugin-details --component mod_forum
moodlia get-plugin-dependencies --component mod_forum
```

Read cached compatible updates, or explicitly ask Moodle to refresh update information from Moodle.org:

```text
moodlia check-plugin-updates
moodlia check-plugin-updates --component mod_forum --refresh true
```

Change the enabled state only when the inventory reports `can_change_enabled: true`:

```text
moodlia set-plugin-enabled --component mod_forum --enabled false
```

The state operation is idempotent and verifies Moodle's state after a change. It rejects plugins with a pending install or upgrade, excludes multi-state filter and repository plugins from the boolean operation, refuses to enable plugins with unsatisfied requirements, refuses to disable plugins needed by an enabled dependent, and never changes MoodlIA's own state. These commands do not install, update, uninstall, upload, or remove plugin code; those actions require a controlled server deployment and Moodle upgrade workflow.

Update the course:

```text
moodlia update-course --course-id <course_id> --summary "<p>Updated summary.</p>" --summary-format html --visible true
```

Move the course to another category:

```text
moodlia move-course --course-id <course_id> --category-id <category_id>
```

Delete a controlled generated course:

```text
moodlia delete-course --course-id <course_id>
```

## Course Blueprints And Readiness

Export a lightweight course blueprint. This is a portable JSON description for templates, structure copy, and restore-like workflows; it is not a Moodle `.mbz` backup. With `--include-contents true`, Book modules include their chapter list and chapter HTML content in `chapters`, and Feedback modules include supported item definitions in `feedback_items`:

```text
moodlia export-course-blueprint --course-id <course_id> --include-contents true --include-groups true
```

Create a new course from a blueprint:

```text
moodlia create-course-from-blueprint --blueprint '{"course":{"fullname":"MoodlIA Blueprint Course","shortname":"moodlia-blueprint-001","category_id":12,"summary":"<p>Created from blueprint.</p>","summary_format":"html","enable_completion":true},"publish_state":"draft","sections":[{"name":"Unit 1","summary":"Introduction","modules":[{"module_type":"page","name":"Welcome","options":{"content":"<p>Start here.</p>"}},{"module_type":"book","name":"Reader","chapters":[{"title":"Chapter 1","content":"<p>Read this first.</p>"},{"title":"Details","content":"<p>More detail.</p>","subchapter":true}]},{"module_type":"feedback","name":"Pulse check","feedback_items":[{"source_item_id":1,"type":"textfield","name":"Goal","definition":{"size":40,"max_length":120}},{"type":"multichoice","name":"Difficulty","definition":{"subtype":"radio","choices":["Easy","Appropriate","Hard"]}}]}]}],"groups":[{"name":"Team A"}],"enrolments":[{"user_id":7,"role_archetype":"student"}]}'
```

Create a native Moodle `.mbz` backup for full Moodle restore workflows:

```text
moodlia backup-course --course-id <course_id> --filename "course-backup.mbz" --include-users false
```

The backup response returns `file_id`, `filename`, `url`, `filesize`, and `time_modified`. Keep the returned `file_id` when you want MoodlIA to restore the backup from Moodle's stored-file area.

List Moodle backup files that are available to the token. With `--include-private true`, MoodlIA includes the current user's Moodle backup area and private files, because Moodle stores newly generated course backups in the user backup area:

```text
moodlia get-course-backup-files --course-id <course_id> --include-private true
```

Upload an existing `.mbz` backup into the current user's Moodle private files for later restore. The CLI streams the file through Moodle's core multipart draft endpoint, avoiding Base64 expansion:

```text
moodlia upload-course-backup --upload-file "./course-backup.mbz"
```

The legacy `--upload-reference <base64_mbz_content>` input remains supported for
older integrations.

Restore a native Moodle `.mbz` backup into a new course:

```text
moodlia restore-course-backup --backup-file-id <file_id> --target new_course --category-id <category_id> --fullname "Restored Course" --shortname "restored-course-001"
```

Restore into an existing course by adding content or deleting the existing content first:

```text
moodlia restore-course-backup --backup-file-id <file_id> --target existing_add --target-course-id <course_id>
moodlia restore-course-backup --backup-file-id <file_id> --target existing_delete --target-course-id <course_id>
```

Delete a stored backup file after it is no longer needed:

```text
moodlia delete-course-backup-file --file-id <file_id>
```

Blueprints are best for controlled templates and automation-friendly JSON. They preserve course structure, module shells, groups, enrolments, and currently supported deep subelements such as Book chapters and Feedback items. Native `.mbz` backups use Moodle's backup/restore controllers and are the right tool when you need Moodle's full restore behavior.

Apply a blueprint to an existing course:

```text
moodlia apply-course-blueprint --course-id <course_id> --blueprint '{"sections":[{"name":"Unit 2","modules":[{"module_type":"label","name":"Note","options":{"intro":"<p>Next steps.</p>"}}]}]}'
```

Copy section structure, module shells, and optionally groups between courses:

```text
moodlia copy-course-structure --source-course-id <source_course_id> --target-course-id <target_course_id> --include-contents true --include-groups false
```

Synchronise manual enrolments from a JSON list:

```text
moodlia sync-course-enrolments --course-id <course_id> --enrolments '[{"user_id":7,"role_archetype":"student"},{"user_id":8,"role_archetype":"teacher"}]' --unenrol-missing false
```

Move a course through the publishing workflow:

```text
moodlia set-course-publish-state --course-id <course_id> --publish-state draft
moodlia set-course-publish-state --course-id <course_id> --publish-state published
moodlia set-course-publish-state --course-id <course_id> --publish-state archived
```

Audit whether a course is ready for use:

```text
moodlia audit-course --course-id <course_id>
moodlia audit-course-completion --course-id <course_id> --include-ok true
moodlia repair-course-completion --course-id <course_id> --mode book_view_only --dry-run true
moodlia repair-course-completion --course-id <course_id> --mode book_view_only --dry-run false --reset-completion-states true
```

The readiness audit returns `ready`, `issue_count`, and `issues_json`. The completion audit returns `issue_count`, `repairable_count`, `issues_json`, and optional `ok_json`. Completion repair supports these modes:

- `book_view_only`: repairs old Book activities that still require a grade by switching them to automatic view completion and clearing Moodle-native grade flags.
- `all_grade_to_view`: applies the same grade-to-view repair to every grade-completion activity in the course.
- `disable_all`: disables activity completion tracking for all tracked activities in the course.

Use dry runs first in production. The repair response includes `changes_json` and `warnings_json` so REST, MCP, CLI, and typed clients share one transport-safe response shape.

## Sections And Modules

Create a section:

```text
moodlia create-section --course-id <course_id> --name "Unit 1" --summary "<p>Introduction.</p>" --summary-format html --visible true
```

Update a section with HTML while preserving native Moodle section files:

```text
moodlia update-section --course-id <course_id> --section-id <section_id> --summary "<details><summary>More information</summary><p>Open this section for details.</p></details>" --summary-format html --format json
```

If `--summary-format` is omitted, the update retains the section's existing summary format. Use `html` for real HTML; `plain` stores plain text.

Some Moodle course formats or plugin combinations may reject direct section creation. In that case, create `subsection` modules and use the generated section numbers as placement targets:

```text
moodlia create-module --course-id <course_id> --section-number 1 --module-type subsection --name "Generated unit"
moodlia get-module-details --course-id <course_id> --module-id <subsection_module_id>
moodlia move-module --course-id <course_id> --module-id <module_id> --section-number <generated_section_number>
```

Create common module types:

```text
moodlia create-module --course-id <course_id> --section-number 1 --module-type page --name "Reading" --options '{"content":"<p>Read this first.</p>"}'
moodlia create-module --course-id <course_id> --section-number 1 --module-type url --name "External reference" --options '{"external_url":"https://example.com"}'
moodlia create-module --course-id <course_id> --section-number 1 --module-type label --name "Inline note" --options '{"intro":"<p>Visible course note.</p>"}'
moodlia create-module --course-id <course_id> --section-number 1 --module-type folder --name "Downloads" --options '{"intro":"<p>Course files.</p>"}'
moodlia create-module --course-id <course_id> --section-number 1 --module-type quiz --name "Unit 1 quiz" --options '{"grade":10,"sumgrades":10,"preferredbehaviour":"deferredfeedback"}'
```

Read module details:

```text
moodlia get-module-details --course-id <course_id> --module-id <module_id>
```

Move, duplicate, update, and delete modules:

```text
moodlia duplicate-module --course-id <course_id> --module-id <module_id> --section-number 2 --name "Copy of Reading"
moodlia move-module --course-id <course_id> --module-id <module_id> --section-number 2
moodlia update-module --course-id <course_id> --module-id <module_id> --name "Updated name" --visible true
moodlia delete-module --course-id <course_id> --module-id <module_id>
```

Completion options use MoodlIA's stable names and accept Moodle-native grade flags for repair workflows:

```text
moodlia update-module --course-id <course_id> --module-id <book_module_id> --options '{"completion_tracking":"automatic","completion_view_required":true,"completion_use_grade":false,"completion_pass_grade":false,"completion_grade_item_number":-1,"reset_completion_states":true}'
moodlia update-module --course-id <course_id> --module-id <module_id> --options '{"completion_tracking":"none","reset_completion_states":true}'
```

Use `completion_use_grade:false` or `completionusegrade:false` when an existing Moodle activity, especially an older Book activity, still shows a grade-based completion rule after the visible grade item number appears cleared. `completion_pass_grade:false` or `completionpassgrade:false` clears the passing-grade rule. Setting `completion_tracking` to `none` or `manual` also clears inherited view and grade completion values unless explicitly incompatible completion criteria are provided in the same request.

## Files

Folder uploads stream a local file to a user-owned Moodle draft before the
operation moves it into the Folder activity:

```text
moodlia upload-folder-file --course-id <course_id> --module-id <folder_module_id> --upload-file "./notes.txt"
```

List and download folder files:

```text
moodlia get-folder-files --course-id <course_id> --module-id <folder_module_id>
moodlia download-folder-file --course-id <course_id> --module-id <folder_module_id> --file-id <file_id>
```

List and download resource files:

```text
moodlia get-resource-files --course-id <course_id> --module-id <resource_module_id>
moodlia download-resource-file --course-id <course_id> --module-id <resource_module_id> --file-id <file_id>
```

Delete a controlled folder file:

```text
moodlia delete-folder-file --course-id <course_id> --module-id <folder_module_id> --file-id <file_id>
```

## Enrolments, Groups, And Progress

List participants:

```text
moodlia get-enrolled-users --course-id <course_id>
```

Enrol and unenrol a known user:

```text
moodlia enrol-user --course-id <course_id> --user-id <user_id> --role-archetype student
moodlia unenrol-user --course-id <course_id> --user-id <user_id>
```

Manage Moodle user accounts:

```text
moodlia create-user --username "generated.student" --firstname "Generated" --lastname "Student" --email "generated.student@example.edu" --password "Use-A-Strong-Password-Here"
moodlia get-user-details --user-id <user_id>
moodlia update-user --user-id <user_id> --firstname "Updated" --suspended false
moodlia delete-user --user-id <user_id>
```

Manage site cohorts and cohort membership:

```text
moodlia create-cohort --name "Generated Cohort" --idnumber "generated-cohort-001" --visible true
moodlia update-cohort --cohort-id <cohort_id> --description "<p>Updated cohort.</p>"
moodlia add-cohort-member --cohort-id <cohort_id> --user-id <user_id>
moodlia remove-cohort-member --cohort-id <cohort_id> --user-id <user_id>
moodlia delete-cohort --cohort-id <cohort_id>
```

Assign and unassign course-level roles without changing enrolment records:

```text
moodlia assign-course-role --course-id <course_id> --user-id <user_id> --role-archetype student
moodlia unassign-course-role --course-id <course_id> --user-id <user_id> --role-archetype student
```

Create a group and add a member:

```text
moodlia create-group --course-id <course_id> --name "Team A"
moodlia add-group-member --course-id <course_id> --group-id <group_id> --user-id <user_id>
moodlia get-group-members --course-id <course_id> --group-id <group_id>
```

Read progress and completion:

```text
moodlia get-course-completion-status --course-id <course_id> --user-id <user_id>
moodlia get-activity-completion-statuses --course-id <course_id> --user-id <user_id>
moodlia get-course-progress-report --course-id <course_id> --limit 50
```

Manage Gradebook categories and grade items:

```text
moodlia get-grade-categories --course-id <course_id>
moodlia create-grade-category --course-id <course_id> --name "Portfolio"
moodlia update-grade-category --course-id <course_id> --category-id <category_id> --name "Portfolio Updated" --grade-pass 50 --grade-max 100 --exclude-empty-grades true
moodlia create-grade-item --course-id <course_id> --name "Participation" --grade-max 10 --grade-min 0 --grade-pass 5 --category-id <category_id>
moodlia update-grade-item --course-id <course_id> --item-id <item_id> --name "Participation Updated" --grade-max 20 --hidden false
moodlia update-grade-item --course-id <course_id> --item-id <quiz_grade_item_id> --grade-pass 8 --category-id <category_id> --weight 1
moodlia update-grade-value --course-id <course_id> --item-id <item_id> --user-id <user_id> --grade 8 --feedback "<p>Good participation.</p>"
moodlia delete-grade-item --course-id <course_id> --item-id <item_id>
moodlia delete-grade-category --course-id <course_id> --category-id <category_id>
```

## Assignment Workflow

Create an assignment with online text submissions:

```text
moodlia create-module --course-id <course_id> --section-number 1 --module-type assign --name "Essay" --options '{"intro":"<p>Write a short answer.</p>","online_text":true,"file_submissions":false,"grade":10}'
```

Save and submit the current user's online text:

```text
moodlia save-assignment-submission --course-id <course_id> --module-id <assignment_module_id> --online-text "<p>My submission.</p>"
moodlia submit-assignment-for-grading --course-id <course_id> --module-id <assignment_module_id>
```

Read submissions and grades:

```text
moodlia get-assignment-submission-status --course-id <course_id> --module-id <assignment_module_id>
moodlia get-assignment-submissions --course-id <course_id> --module-id <assignment_module_id>
moodlia get-assignment-grades --course-id <course_id> --module-id <assignment_module_id>
```

Grade a user submission:

```text
moodlia save-assignment-grade --course-id <course_id> --module-id <assignment_module_id> --user-id <user_id> --grade 8 --feedback-comment "<p>Good work.</p>"
```

Configure and use advanced grading:

```text
moodlia set-assignment-rubric --course-id <course_id> --module-id <assignment_module_id> --name "Writing rubric" --description "<p>Generated rubric.</p>" --criteria '{"criteria":[{"description":"Content quality","levels":[{"definition":"Missing","score":0},{"definition":"Adequate","score":5},{"definition":"Strong","score":10}]},{"description":"Classroom applicability","levels":[{"definition":"Missing","score":0},{"definition":"Adequate","score":5},{"definition":"Strong","score":10}]}]}'
moodlia get-assignment-grading-form --course-id <course_id> --module-id <assignment_module_id>
moodlia grade-assignment-with-rubric --course-id <course_id> --module-id <assignment_module_id> --user-id <user_id> --criteria '{"criteria":[{"criterion_id":101,"level_id":1003,"remark":"Strong content."},{"criterion_id":102,"level_id":1005,"remark":"Adequate applicability."}]}' --feedback-comment "<p>Rubric feedback.</p>"
```

Configure a checklist. Moodle core does not include a native checklist advanced grading form in this installation, so MoodlIA stores checklists as binary Moodle rubrics with `Not met` and `Met` levels:

```text
moodlia set-assignment-checklist --course-id <course_id> --module-id <assignment_module_id> --name "Submission checklist" --description "<p>Binary checklist.</p>" --items '{"items":[{"description":"Includes a clear objective","score":5},{"description":"Includes assessment evidence","score":5}]}'
moodlia grade-assignment-with-checklist --course-id <course_id> --module-id <assignment_module_id> --user-id <user_id> --items '{"items":[{"criterion_id":101,"checked":true,"remark":"Met."},{"criterion_id":102,"checked":false,"remark":"Needs evidence."}]}' --feedback-comment "<p>Checklist feedback.</p>"
```

Configure a Moodle marking guide:

```text
moodlia set-assignment-marking-guide --course-id <course_id> --module-id <assignment_module_id> --name "Teacher guide" --description "<p>Generated guide.</p>" --criteria '{"criteria":[{"shortname":"Accuracy","description":"Accuracy of the response","description_markers":"Check factual correctness.","max_score":40},{"shortname":"Usefulness","description":"Usefulness for classroom practice","description_markers":"Check classroom reuse.","max_score":60}]}' --comments '{"comments":[{"description":"Clear and actionable feedback."}]}'
moodlia grade-assignment-with-marking-guide --course-id <course_id> --module-id <assignment_module_id> --user-id <user_id> --criteria '{"criteria":[{"criterion_id":101,"score":30,"remark":"Mostly accurate."},{"criterion_id":102,"score":60,"remark":"Very useful."}]}' --feedback-comment "<p>Guide feedback.</p>"
```

## Forum, Glossary, And Wiki

Create a forum discussion and reply:

```text
moodlia create-forum-discussion --course-id <course_id> --module-id <forum_module_id> --name "Week 1 discussion" --message "<p>What did you learn?</p>"
moodlia create-forum-discussion-post --course-id <course_id> --module-id <forum_module_id> --discussion-id <discussion_id> --subject "Re: Week 1 discussion" --message "<p>First reply.</p>"
moodlia get-forum-discussion-posts --course-id <course_id> --discussion-id <discussion_id>
```

Create and search glossary entries:

```text
moodlia create-glossary-entry --course-id <course_id> --module-id <glossary_module_id> --concept "MoodlIA" --definition "<p>Moodle automation interface.</p>"
moodlia search-glossary-entries --course-id <course_id> --module-id <glossary_module_id> --query "MoodlIA" --full-search true --include-not-approved true
```

Create and update wiki pages:

```text
moodlia create-wiki-page --course-id <course_id> --module-id <wiki_module_id> --title "Project notes" --content "Initial content"
moodlia get-wiki-pages --course-id <course_id> --module-id <wiki_module_id> --include-content true
moodlia update-wiki-page --course-id <course_id> --module-id <wiki_module_id> --page-id <page_id> --content "Updated content"
```

## Book Chapters

Create a Book activity, then add chapters:

```text
moodlia create-module --course-id <course_id> --section-number 1 --module-type book --name "Course guide" --options '{"intro":"<p>Guide intro.</p>","numbering":"numbers"}'
moodlia create-book-chapter --course-id <course_id> --module-id <book_module_id> --title "Chapter 1" --content "<p>Opening content.</p>"
moodlia create-book-chapter --course-id <course_id> --module-id <book_module_id> --title "Chapter 1.1" --content "<p>Nested content.</p>" --after-chapter-id <chapter_id> --subchapter true
```

For Books that should not participate in Moodle completion, create them with completion disabled:

```text
moodlia create-module --course-id <course_id> --section-number 1 --module-type book --name "Reference book" --options '{"intro":"<p>Reference only.</p>","completion_tracking":"none"}'
```

Update, reorder, list, and delete chapters:

```text
moodlia update-book-chapter --course-id <course_id> --module-id <book_module_id> --chapter-id <chapter_id> --title "Updated chapter" --content "<p>Updated content.</p>"
moodlia move-book-chapter --course-id <course_id> --module-id <book_module_id> --chapter-id <chapter_id> --after-chapter-id 0
moodlia get-book-chapters --course-id <course_id> --module-id <book_module_id> --include-content true
moodlia delete-book-chapter --course-id <course_id> --module-id <book_module_id> --chapter-id <chapter_id>
```

Deleting a top-level chapter also deletes the following subchapters, matching Moodle Book's own UI behavior.

## Database, Choice, Feedback, Lesson, And Workshop

Create a Database activity field and entry:

```text
moodlia create-data-field --course-id <course_id> --module-id <database_module_id> --field-type text --name "Title" --required true
moodlia create-data-field --course-id <course_id> --module-id <database_module_id> --field-type url --name "Reference" --options '{"auto_link":true,"open_in_new_window":true}'
moodlia create-data-entry --course-id <course_id> --module-id <database_module_id> --values '{"Title":"First entry","Reference.url":"https://example.com","Reference.text":"Reference link"}'
moodlia get-data-entries --course-id <course_id> --module-id <database_module_id> --include-contents true
```

Read Choice options and submit a response:

```text
moodlia get-choice-options --course-id <course_id> --choice-module-id <choice_module_id>
moodlia submit-choice-response --course-id <course_id> --choice-module-id <choice_module_id> --option-ids "[<option_id>]"
moodlia get-choice-results --course-id <course_id> --choice-module-id <choice_module_id>
```

Read Feedback structure and analysis:

```text
moodlia get-feedback-items --course-id <course_id> --module-id <feedback_module_id>
moodlia get-feedback-analysis --course-id <course_id> --module-id <feedback_module_id>
```

Create and update supported Feedback items:

```text
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type textfield --name "Student goal" --required true --definition '{"size":40,"max_length":120}'
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type multichoice --name "Difficulty" --definition '{"subtype":"radio","choices":["Easy","Appropriate","Hard"],"horizontal":false}'
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type numeric --name "Expected study hours" --definition '{"range_from":0,"range_to":40}'
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type multichoicerated --name "Satisfaction" --definition '{"subtype":"radio","choices":[{"value":1,"text":"Low"},{"value":3,"text":"Medium"},{"value":5,"text":"High"}]}'
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type info --name "Course metadata" --definition '{"mode":"course"}'
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type captcha --definition '{}'
moodlia create-feedback-item --course-id <course_id> --module-id <feedback_module_id> --type pagebreak --definition '{}'
moodlia update-feedback-item --course-id <course_id> --module-id <feedback_module_id> --item-id <item_id> --name "Updated difficulty" --definition '{"subtype":"dropdown","choices":["Easy","Appropriate","Hard","Too hard"]}'
```

Feedback item writes currently support `textfield`, `textarea`, `numeric`, `multichoice`, `multichoicerated`, `label`, `info`, captcha creation, and pagebreak creation. Updating a captcha or pagebreak is intentionally not exposed; delete and recreate it instead. Moodle allows only one captcha item per Feedback activity, and captcha items do not accept definition settings.

Read Lesson and Workshop state:

```text
moodlia get-lesson-details --course-id <course_id> --module-id <lesson_module_id>
moodlia get-lesson-pages --course-id <course_id> --module-id <lesson_module_id>
moodlia get-workshop-user-plan --course-id <course_id> --module-id <workshop_module_id>
moodlia get-workshop-submissions --course-id <course_id> --module-id <workshop_module_id>
```

Configure a Workshop grading form while the Workshop is still in setup phase:

```text
moodlia set-workshop-grading-form --course-id <course_id> --module-id <workshop_module_id> --strategy accumulative --definition '{"dimensions":[{"description":"<p>Content quality</p>","grade":10,"weight":1},{"description":"<p>Practical applicability</p>","grade":10,"weight":1}]}'
moodlia set-workshop-grading-form --course-id <course_id> --module-id <workshop_module_id> --strategy comments --definition '{"dimensions":[{"description":"<p>Content feedback</p>"},{"description":"<p>Practical feedback</p>"}]}'
moodlia set-workshop-grading-form --course-id <course_id> --module-id <workshop_module_id> --strategy numerrors --definition '{"dimensions":[{"description":"<p>Required sources are cited</p>","grade0":"No","grade1":"Yes","weight":1},{"description":"<p>Conclusion follows evidence</p>","grade0":"No","grade1":"Yes","weight":1}],"mappings":[{"errors":1,"grade":50},{"errors":2,"grade":0}]}'
moodlia set-workshop-grading-form --course-id <course_id> --module-id <workshop_module_id> --strategy rubric --definition '{"layout":"list","dimensions":[{"description":"<p>Content quality</p>","levels":[{"definition":"Missing","grade":0},{"definition":"Adequate","grade":5},{"definition":"Strong","grade":10}]},{"description":"<p>Practical applicability</p>","levels":[{"definition":"Missing","grade":0},{"definition":"Adequate","grade":5},{"definition":"Strong","grade":10}]}]}'
```

Workshop grading-form writes support all Moodle 5.0 core strategies: `accumulative`, `comments`, `numerrors`, and `rubric`. The Workshop must still be in setup phase and the requested strategy must match the module strategy. Third-party strategy definitions require an explicit extension contract.

Create and manage Lesson content pages plus all Moodle 5.0 core question page types:

```text
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --title "Start" --content "<p>Read this page first.</p>" --branches '{"branches":[{"title":"Continue","jump_to":"next_page"}]}'
moodlia update-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-id <page_id> --title "Updated start" --branches '{"branches":[{"title":"Finish","jump_to":"end_of_lesson"}]}'
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-type truefalse --title "Check" --content "<p>Select the correct answer.</p>" --answers '{"correct":{"answer":"True","response":"Correct","jump_to":"next_page","score":1},"wrong":{"answer":"False","response":"Review the content","jump_to":"this_page","score":0}}'
moodlia update-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-id <page_id> --answers '{"answers":[{"answer":"True","response":"Correct","jump_to":"next_page","score":1},{"answer":"False","response":"Try again","jump_to":"this_page","score":0}]}'
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-type shortanswer --title "Type it" --content "<p>Type MoodlIA.</p>" --answers '{"use_regular_expressions":false,"answers":[{"answer":"MoodlIA","response":"Correct","jump_to":"next_page","score":1}]}'
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-type multichoice --title "Choose" --content "<p>Select the best answer.</p>" --answers '{"multi_answer":false,"answers":[{"answer":"Best option","response":"Correct","jump_to":"next_page","score":1},{"answer":"Distractor","response":"Review the content","jump_to":"this_page","score":0}]}'
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-type numerical --title "Range" --content "<p>Enter a value from 8 to 10.</p>" --answers '{"answers":[{"answer":"8:10","response":"Correct range","jump_to":"end_of_lesson","score":1}]}'
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-type essay --title "Reflect" --content "<p>Explain how you would apply this concept.</p>" --answers '{"jump_to":"next_page","score":1}'
moodlia create-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-type matching --title "Match concepts" --content "<p>Match each concept to its definition.</p>" --answers '{"correct_response":"All pairs are correct.","wrong_response":"Review the pairs.","pairs":[{"prompt":"Formative","match":"Improves learning during the process"},{"prompt":"Summative","match":"Evaluates learning at the end"}]}'
moodlia delete-lesson-page --course-id <course_id> --module-id <lesson_module_id> --page-id <page_id>
```

Lesson page writes support content pages with branch buttons and Moodle's six core question types: truefalse, shortanswer, multichoice, numerical, essay, and matching. Structural cluster and end-marker page types remain outside this operation because they are navigation structures rather than question pages.

## Question Bank And Quiz

Create a shared course question bank module:

```text
moodlia create-module --course-id <course_id> --section-number 0 --module-type qbank --name "MoodlIA Question Bank"
```

Moodle question bank activities are not displayed as normal course-section activities, so MoodlIA requires `module-type qbank` to be created in `section-number 0`.

Export and import a portable MoodlIA question bank blueprint:

```text
moodlia export-question-bank-blueprint --course-id <course_id> --bank-scope course_shared --question-bank-module-id <qbank_module_id>
moodlia import-question-bank-blueprint --course-id <target_course_id> --blueprint-json '<blueprint_json>' --bank-scope course_shared --question-bank-module-id <target_qbank_module_id> --create-categories true
```

The blueprint is MoodlIA JSON for automation workflows. It is not a Moodle XML export and it is not a native `.mbz` backup. Export includes directly reconstructable question types and reports unsupported questions inside the blueprint `skipped_questions` list unless `--include-unsupported false` is used.

Create a shared question category:

```text
moodlia create-question-category --course-id <course_id> --name "Generated Questions" --bank-scope course_shared --question-bank-module-id <qbank_module_id>
```

Create a quiz-private question category instead:

```text
moodlia create-question-category --course-id <course_id> --name "Quiz Private Questions" --bank-scope quiz_private --quiz-module-id <quiz_module_id>
```

Create questions:

```text
moodlia create-question --category-id <category_id> --context-id <context_id> --question-type truefalse --name "True or false" --question-text "<p>Moodle is a learning platform.</p>" --options '{"correct_answer":true}'
moodlia create-question --category-id <category_id> --context-id <context_id> --question-type shortanswer --name "Short answer" --question-text "<p>Type MoodlIA.</p>" --options '{"answers":[{"text":"MoodlIA","fraction":1}]}'
moodlia create-question --category-id <category_id> --context-id <context_id> --question-type multichoice --name "One answer" --question-text "<p>Pick one.</p>" --options '{"single":true,"answers":[{"text":"Correct","fraction":1},{"text":"Wrong","fraction":0}]}'
```

Add a question to a quiz and set its slot mark:

```text
moodlia add-question-to-quiz --quiz-module-id <quiz_module_id> --question-id <question_id>
moodlia update-quiz-question-slot --quiz-module-id <quiz_module_id> --slot <slot_number> --max-mark 1
```

Read quiz structure and start an attempt:

```text
moodlia get-quiz-questions --quiz-module-id <quiz_module_id>
moodlia start-quiz-attempt --quiz-module-id <quiz_module_id> --force-new false
moodlia get-quiz-attempts --quiz-module-id <quiz_module_id> --status all --include-previews true
```

Read quiz attempt data and reports:

```text
moodlia get-quiz-attempt-data --quiz-module-id <quiz_module_id> --attempt-id <attempt_id>
moodlia get-quiz-attempt-summary --quiz-module-id <quiz_module_id> --attempt-id <attempt_id>
moodlia get-quiz-results-report --quiz-module-id <quiz_module_id> --limit 50 --include-previews false
```

## Grades

Read course gradebook items:

```text
moodlia get-grade-items --course-id <course_id>
```

Configure the course total and its global completion criteria:

```text
moodlia set-course-grade-pass --course-id <course_id> --grade-pass-percent 80
moodlia set-course-completion-criteria --course-id <course_id> --required-module-ids '[12591,12592,12593,12594,12595,12596]' --require-all-activities true --required-course-grade-percent 80 --criteria-aggregation all
moodlia get-course-completion-criteria --course-id <course_id>
```

`required_module_ids` are Moodle course-module ids, not activity instance ids. Each selected activity must already have completion tracking enabled. Replacing global criteria is refused once Moodle has completion records for the course, so existing learner completion data is never discarded by this command. `get-grade-items` returns the grade range, pass grade, category, weight, visibility, lock state, owning activity, and course-total contribution required to verify the configuration.

Read one user's grades, or all visible grades allowed by the token:

```text
moodlia get-user-grades --course-id <course_id> --user-id <user_id>
moodlia get-user-grades --course-id <course_id>
```

Read quiz-specific grade helpers:

```text
moodlia get-quiz-user-best-grade --quiz-module-id <quiz_module_id> --user-id <user_id>
moodlia get-quiz-feedback-for-grade --quiz-module-id <quiz_module_id> --grade 8
```

## Deletion And Cleanup

Prefer deleting the smallest generated entity first:

```text
moodlia delete-folder-file --course-id <course_id> --module-id <folder_module_id> --file-id <file_id>
moodlia delete-question --question-id <question_id>
moodlia remove-question-from-quiz --quiz-module-id <quiz_module_id> --slot <slot_number>
moodlia delete-module --course-id <course_id> --module-id <module_id>
moodlia delete-section --course-id <course_id> --section-id <section_id>
moodlia delete-course --course-id <course_id>
```

For failed test or demo runs, keep the generated course until you inspect the Moodle UI and the JSON output that caused the failure.

## Troubleshooting

Authentication failed:

- Confirm `MOODLE_BASE_URL` has no trailing path like `/webservice/rest/server.php`.
- Confirm `MOODLE_REST_TOKEN` belongs to a user authorised for the MoodlIA service.
- Confirm Moodle web services and REST protocol are enabled.

Permission denied:

- The token is valid, but the user lacks the Moodle capability required by the operation.
- Test with `moodlia get-current-user`, then inspect the user's roles in the target course/category/module context.

Invalid parameter:

- Run `moodlia <command> --help`.
- Check integer, boolean, enum, and JSON object options.
- Use canonical command names in kebab-case.

Moodle entity not found:

- Verify the ID belongs to the selected course or module.
- Use `get-course-contents`, `get-module-details`, `get-question-categories`, or `get-quiz-questions` to confirm ownership.
