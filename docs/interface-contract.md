# Interface Contract

The interface contract defines the canonical operation surface shared by REST, MCP, TypeScript-aware clients, and the Node CLI.

## Naming

Canonical operation names use `snake_case`:

```text
get_current_user
get_moodlia_status
get_courses
get_course_categories
create_course_category
update_course_category
delete_course_category
export_course_blueprint
create_course_from_blueprint
apply_course_blueprint
copy_course_structure
sync_course_enrolments
set_course_publish_state
audit_course
backup_course
restore_course_backup
upload_course_backup
get_course_backup_files
delete_course_backup_file
audit_course_completion
repair_course_completion
get_course_contents
get_course_details
move_course
get_calendar_events
create_calendar_event
update_calendar_event
delete_calendar_event
get_enrolled_users
get_user_details
create_user
update_user
delete_user
create_cohort
update_cohort
delete_cohort
add_cohort_member
remove_cohort_member
assign_course_role
unassign_course_role
enrol_user
unenrol_user
get_grade_categories
create_grade_category
update_grade_category
delete_grade_category
create_grade_item
update_grade_item
delete_grade_item
update_grade_value
get_groups
create_group
update_group
delete_group
get_groupings
create_grouping
update_grouping
delete_grouping
add_group_to_grouping
remove_group_from_grouping
get_group_members
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
export_question_bank_blueprint
import_question_bank_blueprint
create_question_category
update_question_category
delete_question_category
create_question
get_questions
update_question
move_question
delete_question
get_quiz_questions
add_question_to_quiz
```

Transport mappings:

| Surface | Naming Example |
| --- | --- |
| Canonical operation | `get_courses` |
| Moodle REST function | `local_moodlia_get_courses` |
| MCP tool | `get_courses` |
| TypeScript method | `get_courses()` |
| Development CLI command | `moodle-mcp get-courses` |
| Public npm CLI command | `moodlia get-courses` |

Operation names must not drift between transports.

## Course Workflow Operations

The course workflow operations compose existing course, section, module, group, enrolment, and visibility primitives. They are exposed through REST, MCP, CLI, and the TypeScript client like every other canonical operation.

- `export_course_blueprint`: returns a portable JSON blueprint for lightweight template, backup, and restore workflows. This is not a Moodle `.mbz` backup. When contents are included, Book modules include their chapter list and chapter HTML content in `chapters`, and Feedback modules include supported item definitions in `feedback_items`.
- `create_course_from_blueprint`: creates a course, applies sections, module shells, supported module subelements such as Book chapters and Feedback items, groups, enrolments, and initial publish state from a blueprint.
- `apply_course_blueprint`: applies blueprint sections, module shells, supported module subelements such as Book chapters and Feedback items, groups, and enrolments to an existing course.
- `copy_course_structure`: exports a source course blueprint and applies its structure to a target course.
- `sync_course_enrolments`: applies a desired manual-enrolment list, with optional removal of users missing from the desired list.
- `set_course_publish_state`: maps `draft`, `ready`, `published`, and `archived` to Moodle visibility and archive date behavior.
- `audit_course`: returns operational readiness issues such as hidden courses, empty summaries, no enrolled users, empty sections, and courses without activities.
- `backup_course`: creates a native Moodle `.mbz` course backup through Moodle's backup controller and returns stored-file metadata plus a Moodle pluginfile URL.
- `restore_course_backup`: restores a native Moodle `.mbz` backup into a new course, adds it to an existing course, or deletes existing course content before restore through Moodle's restore controller.
- `upload_course_backup`: stores an existing base64-encoded `.mbz` backup in the current user's Moodle private files so it can be restored later by file id.
- `get_course_backup_files`: lists `.mbz` backups from the selected course backup area and, optionally, the current user's backup area and private files.
- `delete_course_backup_file`: deletes a stored `.mbz` backup file when the caller owns the user-private file or can manage backups in the course context.
- `audit_course_completion`: returns completion-configuration issues such as old Book activities that still require a grade, mixed view-and-grade rules, automatic tracking without an exposed completion rule, or activities tracking completion while course completion is disabled.
- `repair_course_completion`: repairs completion settings with explicit dry-run support. `book_view_only` clears stale Book grade completion, `all_grade_to_view` clears all grade-completion rules by switching to view completion, and `disable_all` disables activity completion tracking for the course.

Complex workflow responses use JSON string fields for nested collections (`blueprint_json`, `sections_json`, `modules_json`, `groups_json`, `enrolments_json`, `warnings_json`, `issues_json`, `ok_json`, `changes_json`) to keep the wire shape stable across REST form parameters, MCP tool calls, CLI arguments, and generated TypeScript declarations.

## Diagnostics

`get_moodlia_status` is the MoodlIA-native status operation. It exists for deployments where automation tokens are limited to MoodlIA functions and cannot call Moodle's generic `core_webservice_get_site_info`. It returns the plugin component, Moodle site URL/name, Moodle and plugin versions, authenticated user, `local/moodlia:useapi` status, REST service shortname, declared function count, and `functions_json`.

## Contract Entry Shape

Each operation should have a contract entry with these fields:

```text
name: canonical snake_case name
summary: short operation description
type: read or write
parameters: typed input schema
returns: typed output schema
context: Moodle context resolution rule
capabilities: Moodle capabilities enforced for the caller
capability_mode: optional; all by default, or any when one listed caller capability is sufficient
target_capabilities: optional capabilities enforced on parameter-selected users or targets
transports: rest, mcp, cli
files: none, upload, download, or metadata
errors: supported normalized error codes
tests: parity, api, cli, browser, behat
cleanup: cleanup operation or fixture strategy
```

During implementation this can be represented as JSON, YAML, PHP metadata, or generated artifacts. The representation is less important than the rule that all adapters validate against it.

Unless an operation declares `capability_mode: "any"`, every capability listed in `capabilities` is required for the calling user in the resolved Moodle context. Operations that act on another selected user can also declare `target_capabilities` so the contract records additional ownership or role checks that are not caller capabilities.

The current transport manifests are generated from `contract/operations.json`:

```text
npm run manifests:generate
npm run manifests:check
```

## Parameter Rules

Use strict, explicit parameters:

- Prefer IDs as integers: `course_id`, `section_id`, `module_id`, `question_id`.
- Use text fields for names and descriptions: `name`, `summary`, `intro`, `content`.
- Use enums for bounded choices: `module_type`, `question_type`, `format`.
- Use arrays only where Moodle naturally accepts multiple items.
- Use optional fields only for true partial update behavior.
- Avoid transport-specific parameter names.

Every operation must define:

- Required parameters.
- Optional parameters and defaults.
- Moodle validation type where applicable.
- Maximum size for free text.
- Whether HTML is allowed.
- Whether file content is transferred separately.

## Return Rules

Return values should be stable typed objects. Avoid returning raw Moodle records unless the contract owns that shape.

Common response fields:

```text
id: Moodle entity id
name: display name
course_id: owning course id
section_id: owning section id
module_id: course module id
url: Moodle URL if useful
warnings: non-fatal Moodle warnings
```

Write operations should return enough information for follow-up verification:

- Created entity ID.
- Owning context.
- Display name.
- URL or lookup key.
- Operation status.

Delete operations should return:

```text
deleted: boolean
id: deleted entity id
warnings: array
```

## Error Shape

Adapters should normalize errors to a shared shape:

```text
{
  "code": "missing_capability",
  "message": "The current user cannot update this course.",
  "details": {
    "operation": "create_module",
    "course_id": 42
  }
}
```

The Node client and CLI use this canonical JSON-compatible shape for local validation, REST transport failures, Moodle REST payload errors, and response-shape validation:

```json
{
  "error": true,
  "code": "invalid_parameters",
  "message": "limit must be an integer.",
  "details": {
    "operation": "get_courses",
    "parameter": "limit"
  }
}
```

The MCP endpoint uses JSON-RPC envelopes. For automation, the canonical error code is always `error.data.code`; JSON-RPC `error.code` is protocol metadata only:

```json
{
  "jsonrpc": "2.0",
  "id": "request-id",
  "error": {
    "code": -32602,
    "message": "Tool arguments must be an object.",
    "data": {
      "code": "invalid_parameters",
      "details": {}
    }
  }
}
```

Recommended error codes:

- `invalid_parameters`
- `invalid_context`
- `missing_capability`
- `not_found`
- `conflict`
- `file_upload_failed`
- `file_download_failed`
- `moodle_error`
- `transport_error`
- `internal_error`

Do not expose secrets, local filesystem paths, stack traces, or raw token values in client-facing errors.

## Initial Operation Catalog

### User And Course Discovery

`get_current_user`

- Type: read.
- Context: system or user context.
- Returns: user id, username, full name, site URL, roles or capability summary where allowed.
- Transports: REST, MCP, CLI.

`get_courses`

- Type: read.
- Context: system, category, or user-visible course contexts.
- Returns: visible courses with id, short name, full name, category, and URL.
- Transports: REST, MCP, CLI.

`get_course_categories`

- Type: read.
- Context: system or course category.
- Parameters: optional `parent_id`; use `-1` for all categories and `0` for top-level categories.
- Returns: category id, name, parent id, visibility, direct course count, and category URL.
- Transports: REST, MCP, CLI.

`create_course_category`

- Type: write.
- Context: system or parent course category.
- Parameters: `name`, optional `parent_id`, optional `visible`, optional `reuse_existing`.
- Capabilities: `moodle/category:manage`.
- Returns: category summary plus `created`, where `created=false` means an existing sibling category with the same name was reused.
- This operation must use Moodle's course category API and must not create plugin-owned records.

`update_course_category`

- Type: write.
- Context: course category.
- Parameters: `category_id`, optional `name`, optional `visible`.
- Capabilities: `moodle/category:manage`.
- Returns: updated category summary.

`delete_course_category`

- Type: write.
- Context: course category.
- Parameters: `category_id`.
- Capabilities: `moodle/category:manage`.
- Returns: deletion status.
- Tests must delete or intentionally preserve generated courses before deleting their generated category.

`create_course`

- Type: write.
- Context: course category.
- Parameters: `fullname`, `shortname`, optional `category_id`, optional `visible`, optional `summary`, optional `summary_format` (`html` or `plain`), optional `course_format`, optional `start_date`, optional `end_date`.
- Capabilities: `moodle/course:create`.
- Returns: course id, names, category id, visibility, rendered summary, summary format, course format, start date, end date, and course URL.
- Uses Moodle's `create_course` API. `course_format` must reference an installed Moodle course format; dates are Unix timestamps and `end_date` must be greater than `start_date` when both are set.

`update_course`

- Type: write.
- Context: course.
- Parameters: `course_id`, optional `fullname`, optional `shortname`, optional `visible`, optional `summary`, optional `summary_format`, optional `course_format`, optional `enable_completion`, optional `category_id`, optional `start_date`, optional `end_date`.
- Capabilities: `moodle/course:update`; moving to another category also requires `moodle/course:create` in the target category context.
- Returns the same canonical course shape as `create_course`.
- Uses Moodle's `update_course` API and does not write directly to course tables.

`move_course`

- Type: write.
- Context: source course and target course category.
- Parameters: `course_id`, `category_id`.
- Capabilities: `moodle/course:update` in the source course and `moodle/course:create` in the target category.
- Returns: course id, target category id, moved status, and course URL.
- This is a convenience operation over Moodle's course update API for automation that needs an explicit course-move command.

`get_course_contents`

- Type: read.
- Context: course.
- Parameters: `course_id`.
- Returns: course id, sections, and each section's modules with module id, instance id, type, name, visibility, course-page visibility, user visibility, and URL.
- Transports: REST, MCP, CLI.
- This operation reads Moodle's course module cache through Moodle APIs and must not query plugin-owned or Moodle tables directly.

`get_course_details`

- Type: read.
- Context: course.
- Parameters: `course_id`.
- Capabilities: `moodle/course:view`.
- Returns the same canonical course metadata shape as `create_course` and `update_course`, including rendered summary, course format, start date, and end date.
- This operation uses Moodle's course APIs and course context validation; it must not query course tables directly.

### Calendar Events

`get_calendar_events`

- Type: read.
- Context: course.
- Parameters: `course_id`, `time_from`, `time_to`.
- Capabilities: `moodle/course:view`.
- Returns: course id and course calendar events with event id, name, description, start timestamp, duration, event type, and calendar URL.
- This operation must use Moodle calendar APIs such as `calendar_get_events`.

`create_calendar_event`

- Type: write.
- Context: course.
- Parameters: `course_id`, `name`, `timestart`, optional `description`, optional `timeduration`.
- Capabilities: `moodle/calendar:manageentries`.
- Returns: created event summary.
- This operation creates only course calendar events; it must not create user, group, category, or site events.

`update_calendar_event`

- Type: write.
- Context: course.
- Parameters: `course_id`, `event_id`, optional event fields.
- Capabilities: `moodle/calendar:manageentries`.
- Returns: updated event summary.
- The event id must belong to the supplied course.

`delete_calendar_event`

- Type: write.
- Context: course.
- Parameters: `course_id`, `event_id`.
- Capabilities: `moodle/calendar:manageentries`.
- Returns: deletion status.

### Enrolments And Participants

`get_enrolled_users`

- Type: read.
- Context: course.
- Parameters: `course_id`.
- Capabilities: `moodle/course:viewparticipants`.
- Returns: course id and enrolled users with user id, username, full name, email, and role shortnames.
- This operation must use Moodle enrolment and role APIs such as `get_enrolled_users` and `get_user_roles`.

`enrol_user`

- Type: write.
- Context: course.
- Parameters: `course_id`, `user_id`, optional `role_archetype`.
- Supported `role_archetype` values: `student`, `teacher`, `editingteacher`.
- Capabilities: `enrol/manual:enrol`.
- Returns: course id, user id, resolved role id, role archetype, enrolment status, and the enrolled user summary.
- This operation uses Moodle's manual enrolment plugin and must not create custom enrolment records directly.

`unenrol_user`

- Type: write.
- Context: course.
- Parameters: `course_id`, `user_id`.
- Capabilities: `enrol/manual:unenrol`.
- Returns: course id, user id, and unenrolment status.
- This operation uses the course manual enrolment instance through Moodle's enrolment API.

### Groups

`get_groups`

- Type: read.
- Context: course.
- Parameters: `course_id`.
- Capabilities: `moodle/course:viewparticipants`.
- Returns: course id and groups with group id, name, description, and idnumber.
- This operation must use Moodle group APIs such as `groups_get_all_groups`.

`create_group`

- Type: write.
- Context: course.
- Parameters: `course_id`, `name`, optional `description`, optional `idnumber`.
- Capabilities: `moodle/course:managegroups`.
- Returns: the created group summary.
- This operation must use Moodle's group API and must not create custom group records.

`update_group`

- Type: write.
- Context: course.
- Parameters: `course_id`, `group_id`, optional `name`, optional `description`, optional `idnumber`.
- Capabilities: `moodle/course:managegroups`.
- Returns: the updated group summary.

`delete_group`

- Type: write.
- Context: course.
- Parameters: `course_id`, `group_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: deletion status.

`get_groupings`

- Type: read.
- Context: course.
- Parameters: `course_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: course id and groupings with grouping id, name, description, and idnumber.
- This operation must use Moodle grouping APIs such as `groups_get_all_groupings`.

`create_grouping`

- Type: write.
- Context: course.
- Parameters: `course_id`, `name`, optional `description`, optional `idnumber`.
- Capabilities: `moodle/course:managegroups`.
- Returns: the created grouping summary.
- This operation must use Moodle's grouping API and must not create custom grouping records.

`update_grouping`

- Type: write.
- Context: course.
- Parameters: `course_id`, `grouping_id`, optional `name`, optional `description`, optional `idnumber`.
- Capabilities: `moodle/course:managegroups`.
- Returns: the updated grouping summary.

`delete_grouping`

- Type: write.
- Context: course.
- Parameters: `course_id`, `grouping_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: deletion status.

`add_group_to_grouping`

- Type: write.
- Context: course.
- Parameters: `course_id`, `grouping_id`, `group_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: grouping membership status and the affected group/grouping summaries.

`remove_group_from_grouping`

- Type: write.
- Context: course.
- Parameters: `course_id`, `grouping_id`, `group_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: grouping membership removal status.

`get_group_members`

- Type: read.
- Context: course.
- Parameters: `course_id`, `group_id`.
- Capabilities: `moodle/course:viewparticipants`.
- Returns: group id and member summaries.

`add_group_member`

- Type: write.
- Context: course.
- Parameters: `course_id`, `group_id`, `user_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: membership status.

`remove_group_member`

- Type: write.
- Context: course.
- Parameters: `course_id`, `group_id`, `user_id`.
- Capabilities: `moodle/course:managegroups`.
- Returns: membership removal status.

### Sections

`create_section`

- Type: write.
- Parameters: `course_id`, `name`, optional `summary`, optional `position`, optional `visible`.
- Context: course.
- Returns: section id, course id, section number, name, rendered summary, and visibility.
- Uses Moodle's `course_create_section` and `course_update_section` APIs. `position=0` appends by using Moodle's native append mode. It must not write directly to course section tables.

`update_section`

- Type: write.
- Parameters: `course_id`, `section_id` or `section_number`, optional `name`, optional `summary`, optional `visible`.
- Context: course.
- Returns: updated section summary including rendered summary and visibility.
- Uses Moodle's `course_update_section` API. It must not write directly to course section tables.

`delete_section`

- Type: write.
- Parameters: `course_id`, `section_id` or `section_number`, delete mode.
- Context: course.
- Returns: deletion status.

### Activities And Resources

`create_module`

- Type: write.
- Parameters: `course_id`, `section_number`, `module_type`, `name`, module-specific options.
- Supported `module_type` values: `assign`, `book`, `choice`, `data`, `feedback`, `lesson`, `lti`, `page`, `folder`, `forum`, `glossary`, `label`, `qbank`, `quiz`, `resource`, `subsection`, `url`, `wiki`, `workshop`. `data` is Moodle's technical plugin name for the Database activity, `lti` is Moodle's technical plugin name for the External tool activity, `qbank` creates Moodle's question bank activity, and `subsection` creates Moodle's delegated subsection structure.
- Common module options include `visible`, `visible_on_course_page`, `download_content`, `completion_tracking`, `completion_view_required`, `completion_grade_item_number`, `completion_use_grade`, `completion_pass_grade`, and `completion_expected`. `visible_on_course_page` maps to Moodle's raw `visibleoncoursepage` setting. Use `visible=true` with `visible_on_course_page=false` to create Moodle's "available but not shown on course page" state without hiding the activity from direct access. `download_content` maps to Moodle's course module download-content flag where the site/course allows downloadable course content. `completion_use_grade`/`completionusegrade` and `completion_pass_grade`/`completionpassgrade` map to Moodle's native grade completion flags and are accepted so automation can clear stale grade-based completion rules on existing activities.
- Assignment module options: `intro`, `online_text`, `file_submissions`, `submission_drafts`, date fields, and grade fields configure a standard Moodle assignment. By default, MoodlIA creates a visible assignment with online text submissions and submission drafts enabled, and file submissions disabled.
- Book module options: `intro`, `numbering` (`none`, `numbers`, `bullets`, `indented`), and `custom_titles`. MoodlIA creates the book activity through Moodle's standard module creation API. Course Book listing, chapter listing, and view registration are exposed through Moodle's Book APIs. Chapter creation, update, movement, and deletion are exposed as explicit subelement operations; Moodle Book does not provide a public writer API for chapters, so MoodlIA keeps the unavoidable Book DML inside one audited helper that mirrors Moodle Book's own edit/delete/move behavior, validates module ownership and `mod/book:edit`, updates Book revision/page order, deletes chapter files/tags on deletion, and triggers Book chapter events. This helper must not use raw SQL or plugin-owned tables.
- Choice module options: `intro`, `choices`, `allow_update`, and `allow_multiple`. `choices` must contain at least two non-empty labels. By default, MoodlIA creates a visible Choice activity, allows the current user to update their choice, publishes anonymous aggregate results, and uses Moodle's `mod_choice` APIs for course listings, options, view events, submissions, response deletion, and results.
- Database module options (`module_type=data`): `intro`, `comments`, `approval_required`, `manage_approved`, `required_entries`, `required_entries_to_view`, `max_entries`, `rss_articles`, `available_from`, `available_to`, `view_from`, `view_to`, `default_sort_field_id`, `default_sort_direction`, `edit_any`, `notification`, and `completion_entries`. MoodlIA creates the activity through Moodle's standard module creation API and exposes settings, including entry-count completion rules, through Moodle database APIs and course module metadata. Database fields and entries are managed as separate subelements through Moodle's `mod_data` field APIs and external entry APIs, without direct table access.
- Database field operations: `get_data_fields` lists field metadata; `create_data_field` and `update_data_field` support the safe field types `text`, `textarea`, `number`, `menu`, `checkbox`, `radiobutton`, `multimenu`, and `url`; `delete_data_field` removes a field through Moodle's Database field API. Choice-like fields accept `options.choices`; `textarea` accepts `options.rows` and `options.columns`; URL fields accept `options.auto_link`, `options.forced_link_text`, and `options.open_in_new_window`, and entry values can address `<field>.url` and `<field>.text` subfields. Moodle field types that require uploaded files or specialised coordinates remain intentionally separate. MoodlIA refuses to delete a field while Moodle uses it as the activity default sort field because Moodle's UI handles that setting with additional module-internal logic.
- Database entry operations: `get_data_entries` lists entries with optional contents, `create_data_entry` creates an entry from a JSON object keyed by field name or field id, `update_data_entry` updates selected fields after validating the entry belongs to the selected activity, and `delete_data_entry` deletes an entry after the same ownership validation.
- Feedback module options: `intro`, `anonymous`, `multiple_submit`, `email_notification`, `autonumbering`, `publish_stats`, `page_after_submit`, `site_after_submit`, `completion_submit`, `time_open`, and `time_close`. MoodlIA creates the activity through Moodle's standard module creation API and exposes course listings, view events, access/status flags, settings, and items through Moodle feedback APIs, item class APIs, and course module metadata.
- Feedback item operations: `get_feedback_items` lists all Feedback activity items through Moodle's `mod_feedback_external::get_items`; `get_feedback_page_items` lists the items and page navigation flags for one Feedback page through `mod_feedback_external::get_page_items`; `create_feedback_item` and `update_feedback_item` support `textfield`, `textarea`, `numeric`, `multichoice`, `multichoicerated`, `label`, and `info` items through Moodle's Feedback item classes; `create_feedback_item` also supports create-only captcha items through Moodle's `feedback_item_captcha` class and pagebreak creation through Moodle's `feedback_create_pagebreak()` helper; `get_feedback_analysis` exposes aggregated analysis through `mod_feedback_external::get_analysis`; `get_feedback_finished_responses` exposes the current user's finished response values through `mod_feedback_external::get_finished_responses`; `delete_feedback_item` validates that the item belongs to the selected Feedback activity and deletes it through Moodle's Feedback API. MoodlIA does not write `feedback_item` tables directly.
- Lesson module options: `intro`, `practice`, `allow_review`, `ongoing_score`, `progress_bar`, `display_left_menu`, `display_left_if`, `slideshow`, `max_answers`, `default_feedback`, `available_from`, `deadline`, `time_limit_seconds`, `use_password`, `password`, `allow_question_retry`, `max_attempts`, `after_correct_answer`, `pages_to_show`, `grade`, `custom_scoring`, `retakes_allowed`, `use_max_grade`, `minimum_questions`, `activity_link`, `allow_offline_attempts`, `completion_end_reached`, `completion_time_spent_seconds`, media popup settings, and slideshow size/background settings. MoodlIA creates the activity through Moodle's standard module creation API and exposes course Lesson listing, settings/details, access information, page listing, possible jumps, view registration, user grade reads, timer reads, attempts overview reports, content-page create/update/delete, and all Moodle 5.0 core question-page types: truefalse, shortanswer, multichoice, numerical, essay, and matching.
- LTI module options (`module_type=lti`): `intro`, required `tool_url`, optional `secure_tool_url`, `type_id`, `launch_container`, privacy toggles (`send_name`, `send_email`, `allow_roster`, `allow_setting`), `accept_grades`, `grade`, `custom_parameters`, `resource_key`, `shared_secret`, `debug_launch`, `show_title_launch`, `show_description_launch`, `icon`, and `secure_icon`. MoodlIA creates the activity through Moodle's standard module creation API and exposes settings through `mod_lti_external::get_ltis_by_courses`; returned details intentionally omit shared secrets and passwords. Privacy-related options default to disabled.
- Glossary module options: `intro`, `main_glossary`, `default_approval`, `edit_always`, `allow_duplicated_entries`, `allow_comments`, `use_dynamic_linking`, `display_format`, `approval_display_format`, `entries_per_page`, `show_alphabet`, `show_all`, `show_special`, `allow_print_view`, and `completion_entries`. MoodlIA creates the activity through Moodle's standard module creation API and exposes settings, browse modes, entries, and entry-count completion rules through Moodle glossary APIs.
- Question bank module options (`module_type=qbank`): `intro` plus common module options. Moodle treats this as an explicit question bank module rather than a normal course-section activity, so it must be created with `section_number=0`. MoodlIA creates it through Moodle's standard module creation API and `get_module_details` exposes the module context, question bank URL, category count, question count, and category summaries through Moodle question APIs. Question category and question CRUD continue to use the canonical question bank operations.
- Subsection module options (`module_type=subsection`): no module-specific options are currently exposed beyond common module options. MoodlIA creates the subsection through Moodle's standard module creation API, and `get_module_details` exposes the delegated section id, number, name, visibility, and availability returned by Moodle's course format APIs. When a Moodle course format or site plugin rejects direct `create_section`, `subsection` is the documented Moodle-visible workaround: create a subsection activity, read its delegated section through `get_module_details`, then use that section number with `move_module`.
- Wiki module options: `intro`, `first_page_title`, `wiki_mode`, `default_format`, and `force_format`. MoodlIA creates the activity through Moodle's standard module creation API and exposes settings, subwikis, pages, files, and page view events through Moodle wiki APIs.
- Workshop module options: `intro`, `strategy`, `submission_grade`, `assessment_grade`, `grade_decimals`, `submission_instructions`, `assessment_instructions`, `text_submission`, `file_submission`, `max_submission_attachments`, `submission_file_types`, `max_file_size`, `late_submissions`, `self_assessment`, `example_submissions`, `examples_mode`, `submission_start`, `submission_end`, `assessment_start`, `assessment_end`, `switch_to_assessment_after_submission_deadline`, `conclusion`, and overall feedback settings. MoodlIA creates the activity through Moodle's standard module creation API and exposes settings through Moodle workshop APIs and course module metadata. Workshop phase switching, user-plan reads, grade reads, grade-report reads, reviewer/submission assessment reads, allocation, all four Moodle 5.0 core grading strategies, assessment form-definition reads, assessment updates, assessment evaluation, and submission CRUD are exposed through Moodle workshop APIs. Third-party grading strategies require an explicit extension contract before exposure.
- Forum module options: `forum_type` defaults to `general`; supported values are `general`, `eachuser`, `qanda`, `single`, and `blog`. `intro` stores the forum description. Forum creation also supports attachment limits, subscription/tracking settings, post blocking dates and thresholds, and completion rules for required discussions, replies, and total posts.

`get_module_details`

- Type: read.
- Parameters: `course_id`, `module_id`.
- Context: module.
- Returns common course-module metadata, including `visible`, the raw `visible_on_course_page` setting, `download_content`, and `user_visible`, section metadata, rendered description where Moodle exposes it, completion metadata, and an `extra_json` object.
- `extra_json.activity` contains module-specific data through Moodle public APIs where available:
  - `assign`: assignment settings, submission plugin summary, feedback plugin summary.
  - `book`: book settings and chapter summaries.
  - `choice`: choice settings, options, and aggregate results.
  - `data`: database timing, approval, comments, sorting settings, completion-entry rule, field count, field summaries, entry count, and entry summaries.
  - `feedback`: feedback timing, anonymity, and completion-submit settings.
  - `folder`: folder settings, file count, total file size, and stored file summaries.
  - `forum`: forum settings, discussion count, post count, and discussion summaries.
  - `glossary`: glossary settings, browse modes, entry count, and entry summaries.
  - `label`: rendered inline content.
  - `lesson`: lesson timing, navigation, attempt, grading, display, and completion settings.
  - `lti`: launch URL, launch container, privacy switches, custom parameters, grade acceptance, display flags, icons, and timestamps. Secrets are not returned.
  - `page`: page content and display metadata.
  - `quiz`: quiz settings, question count, grade, timing, attempt, review, and navigation metadata.
  - `resource`: resource settings, file count, total file size, primary file, and stored file summaries.
  - `url`: external URL and display metadata where Moodle exposes it.
  - `wiki`: wiki settings, page count, page summaries, subwikis, attached files, and view-event operations where Moodle exposes them.
  - `workshop`: workshop grading, submission, assessment, timing, example-submission, conclusion, and overall-feedback settings.
- MoodlIA does not use direct table reads for these details; it uses Moodle external APIs, course module metadata, File API, and existing module APIs.

`update_module`

- Type: write.
- Parameters: `course_id`, `module_id`, optional `name`, optional `visible`, optional safe common module options.
- Context: module.
- Supports completion repair options: `completion_tracking`, `completion_view_required`, `completion_grade_item_number`, `completion_use_grade`, `completionusegrade`, `completion_pass_grade`, `completionpassgrade`, `completion_expected`, and `reset_completion_states`.
- Use `completion_use_grade:false` or `completionusegrade:false` together with `completion_grade_item_number:-1` to clear Moodle's native grade-completion rule on activities whose UI still shows a "receive a grade" requirement. Setting `completion_tracking` to `none` or `manual` clears inherited view and grade criteria unless incompatible criteria are explicitly supplied in the same request.

`view_book`

- Parameters: `course_id`, `module_id`, optional `chapter_id`.
- Context: Book module.
- Capabilities: `mod/book:read`.
- Registers a Moodle Book or chapter view through `mod_book_external::view_book`, allowing Moodle to trigger view events and completion handling.
- Returns viewed status, resolved chapter id, and Moodle warnings. For an empty book, Moodle can return a successful view with warnings and `chapter_id` set to `0`.

`create_book_chapter`

- Type: write.
- Parameters: `course_id`, `module_id`, `title`, `content`, optional `content_format`, optional `subchapter`, optional `after_chapter_id`, optional `hidden`.
- Context: Book module.
- Capabilities: `mod/book:edit`.
- Creates a chapter in the selected Book activity. `after_chapter_id=0` inserts first, omitted or `null` appends, and a positive value inserts after that chapter. The first Book chapter cannot be a subchapter.
- Returns the canonical chapter shape used by `get_book_chapters`.

`update_book_chapter`

- Type: write.
- Parameters: `course_id`, `module_id`, `chapter_id`, and at least one mutable field: `title`, `content`, `content_format`, `subchapter`, or `hidden`.
- Context: Book module.
- Capabilities: `mod/book:edit`.
- Updates a chapter after verifying it belongs to the selected Book activity. It bumps the Book revision and triggers Moodle's Book chapter update event.
- Returns the canonical chapter shape used by `get_book_chapters`.

`move_book_chapter`

- Type: write.
- Parameters: `course_id`, `module_id`, `chapter_id`, optional `after_chapter_id`.
- Context: Book module.
- Capabilities: `mod/book:edit`.
- Moves a chapter in the Book page order. Moving a top-level chapter also moves its following subchapters as the same block, matching Moodle Book's UI behavior; moving a subchapter moves only that subchapter.
- Returns the canonical chapter shape used by `get_book_chapters`.

`delete_book_chapter`

- Type: write.
- Parameters: `course_id`, `module_id`, `chapter_id`.
- Context: Book module.
- Capabilities: `mod/book:edit`.
- Deletes a chapter after verifying Book ownership. Deleting a top-level chapter also deletes its following subchapters, matching Moodle Book's UI behavior.
- Returns deletion status and the deleted chapter ids.

`get_course_books`

- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`.
- Returns Book activities visible in the course through `mod_book_external::get_books_by_courses`, including book id, course-module id, numbering mode, custom-title flag, revision, modification time, URL, and Moodle warnings.

`get_lesson_access_information`

- Parameters: `course_id`, `module_id`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`.
- Returns current-user management, grading, and report permissions, attempt counters, first/last page metadata, prevent-access reasons, and Moodle warnings.

`get_lesson_details`

- Parameters: `course_id`, `module_id`, optional `password`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`.
- Returns a normalized Lesson summary from `mod_lesson_external::get_lesson`, including ids, rendered intro, grading/navigation/display/timing/completion settings, file counts, and Moodle warnings. Password values are never returned.

`get_course_lessons`

- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`.
- Returns normalized Lesson summaries for Lesson activities visible through `mod_lesson_external::get_lessons_by_courses`, plus Moodle warnings.

`get_lesson_pages`

- Parameters: `course_id`, `module_id`, optional `password`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`.
- Returns Lesson page ids, navigation ids, page type metadata, title/content where Moodle exposes them, answer ids, jump ids, file counts, and Moodle warnings.

`create_lesson_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `title`, `content`, optional `content_format`, optional `branches`, optional `after_page_id`, optional `display_in_menu`, optional `horizontal`, optional `page_type`, optional `answers`.
- Context: Lesson module.
- Capabilities: `mod/lesson:manage`.
- Creates a Moodle Lesson content, truefalse, shortanswer, multichoice, numerical, essay, or matching question page through `lesson_page::create(...)` after validating that the module belongs to the selected course.
- `branches` is a JSON object with a `branches` array. Each branch supports `title`, optional `response`, optional `score`, and `jump_to` as an integer page id or one of `this_page`, `next_page`, `previous_page`, or `end_of_lesson`.
- `page_type` defaults to `content`. For `truefalse`, `answers` must contain exactly two entries, either as `correct` and `wrong` objects or an `answers` array. For `shortanswer`, `answers` contains one or more accepted answers and may set `use_regular_expressions`. For `multichoice`, `answers` is a JSON object with optional `multi_answer` and an `answers` array containing at least two choices. For `numerical`, `answers` contains one or more numeric answers or inclusive `min:max` ranges. These answer rows support `answer`, optional formats and response, optional `score`, and a supported `jump_to`.
- For `essay`, `answers` contains optional `jump_to` and `score` settings for Moodle's manually graded response. For `matching`, `answers` contains at least two unique `pairs` with `prompt` and plain-text `match`, plus optional correct/wrong response text, formats, scores, and jumps.
- Returns the created page, answer ids, jumps, and normalized answer metadata.

`update_lesson_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `page_id`, optional `title`, optional `content`, optional `content_format`, optional `branches`, optional `display_in_menu`, optional `horizontal`, optional `answers`.
- Context: Lesson module.
- Capabilities: `mod/lesson:manage`.
- Updates a Moodle Lesson content, truefalse, shortanswer, multichoice, numerical, essay, or matching question page through the Lesson page component `update(...)` method after verifying that the page belongs to the selected Lesson module.
- Returns the updated page, answer ids, jumps, and normalized answer metadata.

`delete_lesson_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `page_id`.
- Context: Lesson module.
- Capabilities: `mod/lesson:manage`.
- Deletes a Moodle Lesson page through the Lesson page component `delete()` method after verifying that the page belongs to the selected Lesson module.
- Returns the deleted page id and deletion status.

`view_lesson`

- Parameters: `course_id`, `module_id`, optional `password`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`.
- Registers the Lesson view through `mod_lesson_external::view_lesson`, allowing Moodle to trigger view events and completion handling.

`get_lesson_user_grade`

- Parameters: `course_id`, `module_id`, optional `user_id`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`; Moodle enforces extra checks when requesting another user's data.
- Returns whether a grade exists, the raw grade, formatted grade, and Moodle warnings.

`get_lesson_user_timers`

- Parameters: `course_id`, `module_id`, optional `user_id`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`; Moodle enforces extra checks when requesting another user's data.
- Returns timer sessions, completion flags, timestamps, and Moodle warnings.

`get_lesson_possible_jumps`

- Parameters: `course_id`, `module_id`.
- Context: Lesson module.
- Capabilities: `mod/lesson:view`; Moodle returns jump data only when its own Lesson rules allow it, for example manager access or offline attempts.
- Returns possible page/answer jump targets through `mod_lesson_external::get_pages_possible_jumps`, plus Moodle warnings.

`get_lesson_attempts_overview`

- Parameters: `course_id`, `module_id`, optional `group_id`.
- Context: Lesson module.
- Capabilities: `mod/lesson:viewreports`.
- Returns attempt report totals, score/time aggregates, student attempt rows where available, and Moodle warnings.

`get_course_assignments`

- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`; Moodle controls which assignments the caller can see.
- Reads assignment modules through Moodle course module APIs and `assign` module APIs, then returns normalized assignment ids, module ids, names, intro and activity text, relevant date and grading settings, enabled submission and feedback plugin names, visibility, and view URL.

`get_assignment_submission_status`

- Parameters: `course_id`, `module_id`, optional `user_id`.
- Context: assignment module.
- Capabilities: `mod/assign:view`; Moodle assignment APIs enforce any user-specific visibility restrictions.
- Returns: course id, assignment module id, assignment instance id, user id, submission id, status, attempt number, editability, submitted flag, and online text.

`save_assignment_submission`

- Parameters: `course_id`, `module_id`, `online_text`.
- Context: assignment module.
- Capabilities: `mod/assign:submit`.
- Saves the current user's online-text submission through `mod_assign_external::save_submission`; it does not write directly to plugin tables or Moodle tables.

`submit_assignment_for_grading`

- Parameters: `course_id`, `module_id`, optional `accept_submission_statement`.
- Context: assignment module.
- Capabilities: `mod/assign:submit`.
- Submits the current user's assignment attempt through `mod_assign_external::submit_for_grading` and returns the same submission status shape used by `get_assignment_submission_status`.

`save_assignment_grade`

- Parameters: `course_id`, `module_id`, `user_id`, `grade`, optional `feedback_comment`, optional `attempt_number`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Saves a Moodle assignment grade through `mod_assign_external::save_grade`, including optional `assignfeedback_comments` feedback. The returned status includes grade, grader id, grading status, and feedback comment.

`get_assignment_grading_form`

- Parameters: `course_id`, `module_id`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Reads the active Moodle advanced grading definition for the assignment submission area. Supported Moodle methods are `rubric` and `guide`; unsupported or absent methods return `supported=false`.

`set_assignment_rubric`

- Parameters: `course_id`, `module_id`, `name`, optional `description`, `criteria`, optional `options`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`, `moodle/grade:managegradingforms`.
- Creates or updates the Moodle rubric definition through Moodle's grading manager and rubric controller. Criteria and levels are supplied as a JSON object. The plugin does not create grading tables or bypass Moodle's grading APIs.

`set_assignment_checklist`

- Parameters: `course_id`, `module_id`, `name`, optional `description`, `items`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`, `moodle/grade:managegradingforms`.
- Creates a checklist as a binary Moodle rubric with `Not met` and `Met` levels. This keeps checklist workflows compatible with Moodle core when a native checklist advanced grading plugin is not installed.

`set_assignment_marking_guide`

- Parameters: `course_id`, `module_id`, `name`, optional `description`, `criteria`, optional `comments`, optional `options`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`, `moodle/grade:managegradingforms`.
- Creates or updates a Moodle marking guide definition through Moodle's guide controller, including reusable guide comments.

`grade_assignment_with_rubric`

- Parameters: `course_id`, `module_id`, `user_id`, `criteria`, optional `feedback_comment`, optional `attempt_number`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Grades a submitted assignment with the active Moodle rubric by passing `advancedgradingdata` to `mod_assign_external::save_grade`.

`grade_assignment_with_checklist`

- Parameters: `course_id`, `module_id`, `user_id`, `items`, optional `feedback_comment`, optional `attempt_number`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Grades a checklist generated by `set_assignment_checklist` by selecting the binary rubric level for each criterion and then using Moodle's normal assignment grading API.

`grade_assignment_with_marking_guide`

- Parameters: `course_id`, `module_id`, `user_id`, `criteria`, optional `feedback_comment`, optional `attempt_number`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Grades an assignment with the active Moodle marking guide through `advancedgradingdata`, including criterion scores and remarks.

`get_assignment_submissions`

- Parameters: `course_id`, `module_id`, optional `status`, optional `since`, optional `before`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Reads assignment submissions through `mod_assign_external::get_submissions` and returns normalized submission ids, users, status, attempt numbers, timestamps, grading status, and online text.

`get_assignment_grades`

- Parameters: `course_id`, `module_id`, optional `since`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Reads assignment grades through `mod_assign_external::get_grades` and returns normalized grade ids, users, graders, attempts, raw grades, formatted grades, and timestamps.

`view_assignment`

- Parameters: `course_id`, `module_id`.
- Context: assignment module.
- Capabilities: `mod/assign:view`.
- Registers a normal assignment view through `mod_assign_external::view_assign` and returns the view status.

`view_assignment_submission_status`

- Parameters: `course_id`, `module_id`.
- Context: assignment module.
- Capabilities: `mod/assign:view`.
- Registers a submission-status view through `mod_assign_external::view_submission_status` and returns the view status.

`view_assignment_grading_table`

- Parameters: `course_id`, `module_id`.
- Context: assignment module.
- Capabilities: `mod/assign:grade`.
- Registers a grading-table view through `mod_assign_external::view_grading_table` and returns the view status.

`get_choice_options`

- Parameters: `course_id`, `choice_module_id`.
- Context: choice module.
- Capabilities: `mod/choice:choose`.
- Returns choice id, choice module id, option ids, option text, answer counts, and current-user selected/disabled flags through `mod_choice_external::get_choice_options`.

`get_course_choices`

- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`.
- Returns normalized Choice activity summaries from `mod_choice_external::get_choices_by_courses`, including ids, intro, display, timing, result visibility, update/multiple-answer settings, and Moodle warnings.

`view_choice`

- Parameters: `course_id`, `choice_module_id`.
- Context: choice module.
- Capabilities: `mod/choice:choose`.
- Registers the Choice view through `mod_choice_external::view_choice`, allowing Moodle to trigger view events and completion handling.

`submit_choice_response`

- Parameters: `course_id`, `choice_module_id`, `option_ids`.
- Context: choice module.
- Capabilities: `mod/choice:choose`.
- `option_ids` is a JSON array string of selected option ids, for example `[123]`, so CLI and REST transport encoding stays consistent.
- Submits the current user's response through `mod_choice_external::submit_choice_response`.

`delete_choice_responses`

- Parameters: `course_id`, `choice_module_id`, optional `response_ids`.
- Context: choice module.
- Capabilities: `mod/choice:choose`; Moodle enforces `mod/choice:deleteresponses` when deleting responses beyond the current user's allowed responses.
- `response_ids` is a JSON array string. An omitted or empty array delegates to Moodle's behavior for deleting the current user's responses.
- Deletes responses through `mod_choice_external::delete_choice_responses` and returns Moodle warnings for response ids the user is not allowed to delete.

`get_choice_results`

- Parameters: `course_id`, `choice_module_id`.
- Context: choice module.
- Capabilities: `mod/choice:readresponses`.
- Returns aggregate result rows with option id, option text, and answer count through `mod_choice_external::get_choice_results`.

`set_workshop_phase`

- Parameters: `course_id`, `module_id`, `phase`.
- Supported `phase` values: `setup`, `submission`, `assessment`, `evaluation`, `closed`.
- Context: workshop module.
- Capabilities: `mod/workshop:switchphase`.
- Switches the workshop phase through Moodle's Workshop API and returns the resolved phase name/code.

`get_workshop_submissions`

- Parameters: `course_id`, `module_id`, optional `user_id`, optional `group_id`, optional `page`, optional `per_page`.
- Context: workshop module.
- Capabilities: `mod/workshop:view`.
- Returns canonical Workshop submissions through Moodle's `mod_workshop_external` API.

`get_workshop_user_plan`

- Parameters: `course_id`, `module_id`, optional `user_id`.
- Context: workshop module.
- Capabilities: `mod/workshop:view`.
- Returns the user's Workshop phase plan, phase tasks, available actions, and example submissions through Moodle's `mod_workshop_external::get_user_plan` API. Reading another user's plan still follows Moodle's own capability and group-membership checks.

`get_workshop_grades`

- Parameters: `course_id`, `module_id`, optional `user_id`.
- Context: workshop module.
- Capabilities: `mod/workshop:view`.
- Returns the user's Workshop submission and assessment grade information through Moodle's `mod_workshop_external::get_grades` API. Reading another user's grades still follows Moodle's own `mod/workshop:viewallassessments` and group-membership checks.

`get_workshop_grades_report`

- Parameters: `course_id`, `module_id`, optional `group_id`, optional `sort_by`, optional `sort_direction`, optional `page`, optional `per_page`.
- Supported `sort_by` values: `lastname`, `firstname`, `submissiontitle`, `submissionmodified`, `submissiongrade`, `gradinggrade`.
- Supported `sort_direction` values: `ASC`, `DESC`.
- Context: workshop module.
- Capabilities: `mod/workshop:viewallassessments`.
- Returns the Workshop grades report rows, including submissions and reviewer/reviewed relationships, through Moodle's `mod_workshop_external::get_grades_report` API.
- Moodle may return an empty report before assessments exist, even when submissions already exist.

`get_workshop_reviewer_assessments`

- Parameters: `course_id`, `module_id`, optional `user_id`.
- Context: workshop module.
- Capabilities: `mod/workshop:view`; Moodle enforces additional reviewer and visibility checks through `mod_workshop_external::get_reviewer_assessments`.
- Returns canonical assessment rows assigned to the selected reviewer. `user_id=0` delegates to Moodle's current-user behavior.
- Moodle may return an empty list when no allocations exist or when the Workshop phase/state does not make assessments available to the caller.

`get_workshop_submission_assessments`

- Parameters: `course_id`, `module_id`, `submission_id`.
- Context: workshop module.
- Capabilities: `mod/workshop:view`; Moodle enforces additional submission assessment visibility checks through `mod_workshop_external::get_submission_assessments`.
- Validates that `submission_id` belongs to the selected Workshop module before reading its assessments.
- Moodle may return an empty list when no assessment allocation exists for the submission.

`set_workshop_grading_form`

- Type: write.
- Parameters: `course_id`, `module_id`, `strategy`, `definition`.
- Supported `strategy` values: `accumulative`, `comments`, `numerrors`, `rubric`.
- Context: workshop module.
- Capabilities: `mod/workshop:editdimensions`.
- Requires the Workshop to still be in setup phase and to use the selected active strategy.
- For `accumulative`, `definition` is a JSON object with a `dimensions` array. Each dimension supports `description`, `grade`, and optional `weight`.
- For `comments`, `definition` is a JSON object with a `dimensions` array. Each dimension supports a unique non-empty `description`.
- For `numerrors`, `definition` is a JSON object with a `dimensions` array and optional `mappings`. Each dimension supports a unique non-empty `description`, optional `grade0` and `grade1` labels, and optional positive integer `weight`. `mappings` may be an array of `{errors, grade}` objects or an object keyed by error count; grades must be between 0 and 100. When omitted, MoodlIA saves Moodle's linear default mapping for the total dimension weight.
- For `rubric`, `definition` is a JSON object with optional `layout` (`list` or `grid`) and a `dimensions` array. Each dimension supports a unique non-empty `description` and at least two `levels`; each level supports a unique non-empty `definition` and numeric `grade`.
- Creates or replaces the assessment dimensions through the Workshop grading strategy instance and its `save_edit_strategy_form(...)` method. The operation does not write to Workshop strategy tables directly.
- Returns the saved dimension count and Moodle dimension metadata as JSON.

`create_workshop_submission`

- Parameters: `course_id`, `module_id`, `title`, optional `content`, optional `content_format`.
- Context: workshop module.
- Capabilities: `mod/workshop:submit`.
- Creates the current user's Workshop submission through Moodle's Workshop external API.

`update_workshop_submission`

- Parameters: `course_id`, `module_id`, `submission_id`, optional `title`, optional `content`, optional `content_format`.
- Context: workshop module.
- Capabilities: `mod/workshop:submit`.
- Updates a submission after validating that it belongs to the selected Workshop activity.

`delete_workshop_submission`

- Parameters: `course_id`, `module_id`, `submission_id`.
- Context: workshop module.
- Capabilities: `mod/workshop:submit`.
- Deletes a submission after validating that it belongs to the selected Workshop activity.

## User, Cohort, And Role Operations

`get_user_details`

- Parameters: `user_id`.
- Context: system.
- Capabilities: `moodle/user:viewdetails`.
- Returns normalized user profile metadata including id, username, name fields, email, authentication method, suspension/deletion/confirmation flags, and last modification time.

`create_user`

- Parameters: `username`, `firstname`, `lastname`, `email`, `password`, optional `auth`, optional `suspended`.
- Context: system.
- Capabilities: `moodle/user:create`.
- Creates a Moodle user through Moodle's user API and returns the normalized user profile.

`update_user`

- Parameters: `user_id`, optional `firstname`, optional `lastname`, optional `email`, optional `password`, optional `auth`, optional `suspended`.
- Context: system.
- Capabilities: `moodle/user:update`.
- Updates Moodle user fields through Moodle's user API and returns the normalized user profile.

`delete_user`

- Parameters: `user_id`.
- Context: system.
- Capabilities: `moodle/user:delete`.
- Deletes a Moodle user through Moodle's user API and returns the deleted user id.

`create_cohort`

- Parameters: `name`, optional `idnumber`, optional `description`, optional `visible`.
- Context: system.
- Capabilities: `moodle/cohort:manage`.
- Creates a site cohort through Moodle's cohort API and returns normalized cohort metadata.

`update_cohort`

- Parameters: `cohort_id`, optional `name`, optional `idnumber`, optional `description`, optional `visible`.
- Context: system.
- Capabilities: `moodle/cohort:manage`.
- Updates a site cohort through Moodle's cohort API and returns normalized cohort metadata.

`delete_cohort`

- Parameters: `cohort_id`.
- Context: system.
- Capabilities: `moodle/cohort:manage`.
- Deletes a site cohort through Moodle's cohort API and returns the deleted cohort id.

`add_cohort_member`

- Parameters: `cohort_id`, `user_id`.
- Context: system.
- Capabilities: `moodle/cohort:manage`.
- Adds an existing Moodle user to a site cohort.

`remove_cohort_member`

- Parameters: `cohort_id`, `user_id`.
- Context: system.
- Capabilities: `moodle/cohort:manage`.
- Removes an existing Moodle user from a site cohort.

`assign_course_role`

- Parameters: `course_id`, `user_id`, optional `role_archetype` (`student`, `teacher`, or `editingteacher`).
- Context: course.
- Capabilities: `moodle/role:assign`.
- Assigns a role in the selected course context without creating or changing enrolment records, then returns the user's current course role shortnames.

`unassign_course_role`

- Parameters: `course_id`, `user_id`, optional `role_archetype` (`student`, `teacher`, or `editingteacher`).
- Context: course.
- Capabilities: `moodle/role:assign`.
- Removes the selected course role assignment without changing enrolment records, then returns the user's current course role shortnames.

## Gradebook Operations

`get_grade_items`

- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/grade:viewall`.
- Returns gradebook item ids, names, and category names using Moodle's gradebook APIs.

`get_user_grades`

- Parameters: `course_id`, optional `user_id`, optional `group_id`.
- Context: course.
- Capabilities: `moodle/grade:viewall`.
- Returns user grade rows with item metadata, course module id where available, raw and formatted grade values, range, percentage, feedback, hidden state, and locked state.

`get_grade_categories`

- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/grade:viewall`.
- Returns Gradebook categories for the selected course with category id, name, aggregation constant, hidden state, and modification timestamp.

`create_grade_category`

- Parameters: `course_id`, `name`, optional `aggregation`.
- Context: course.
- Capabilities: `moodle/grade:manage`.
- Creates a Moodle Gradebook category through Moodle's grade category API and returns normalized category metadata.

`update_grade_category`

- Parameters: `course_id`, `category_id`, optional `name`, optional `aggregation`, optional `hidden`.
- Context: course.
- Capabilities: `moodle/grade:manage`.
- Updates a Moodle Gradebook category through Moodle's grade category API and returns normalized category metadata.

`delete_grade_category`

- Parameters: `course_id`, `category_id`.
- Context: course.
- Capabilities: `moodle/grade:manage`.
- Deletes a Moodle Gradebook category through Moodle's grade category API. Moodle validates whether the category can be deleted.

`create_grade_item`

- Parameters: `course_id`, `name`, optional `grade_max`, optional `grade_min`, optional `grade_pass`, optional `category_id`, optional `hidden`.
- Context: course.
- Capabilities: `moodle/grade:manage`.
- Creates a manual Gradebook item. MoodlIA does not use this operation to create or mutate module-owned grade items.

`update_grade_item`

- Parameters: `course_id`, `item_id`, optional `name`, optional `grade_max`, optional `grade_min`, optional `grade_pass`, optional `category_id`, optional `hidden`, optional `locked`.
- Context: course.
- Capabilities: `moodle/grade:manage`.
- Updates only manual Gradebook items. Module-owned grade items must be changed through their owning activity.

`delete_grade_item`

- Parameters: `course_id`, `item_id`.
- Context: course.
- Capabilities: `moodle/grade:manage`.
- Deletes only manual Gradebook items.

`update_grade_value`

- Parameters: `course_id`, `item_id`, `user_id`, `grade`, optional `feedback`.
- Context: course.
- Capabilities: `moodle/grade:edit`.
- Updates a user's final grade for a manual Gradebook item through Moodle's grade item API after validating the grade is within the item range.

`get_course_progress_report`

- Parameters: `course_id`, optional `limit`.
- Context: course.
- Capabilities: `moodle/course:viewparticipants`, `moodle/grade:viewall`.
- Returns a compact per-user progress report for enrolled users, combining Moodle enrolment, gradebook, course completion, and activity completion APIs. The report includes aggregate course counts, per-user course-completion state, tracked/completed activity counts, grade totals, grade percentage, roles, and non-fatal warnings. It does not expose user email addresses and treats unavailable gradebook data as report warnings instead of using direct database access.

- Label module options: `content` is required. Moodle renders labels directly on the course page and does not expose a separate activity view link.
- URL module options: `external_url` is required. Optional `display` values are `auto`, `embed`, `new`, and `open`; `print_intro` controls whether Moodle stores and renders the intro when the selected display mode supports it.
- Context: course.
- Returns: module id, course module id, instance id, URL.

`get_course_feedbacks`

- Type: read.
- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`; Moodle's Feedback API filters by activity visibility and user permissions.
- Returns Feedback summaries through `mod_feedback_external::get_feedbacks_by_courses`, including instance id, course module id, intro, anonymity, notification, multiple-submit, numbering, post-submit, timing, completion, and URL fields.

`view_feedback`

- Type: write.
- Parameters: `course_id`, `module_id`, optional `module_viewed`.
- Context: feedback module.
- Capabilities: `mod/feedback:view`.
- Registers the Feedback view event through `mod_feedback_external::view_feedback`. When `module_viewed` is true, Moodle also applies its completion-view rules.

`get_feedback_access_information`

- Type: read.
- Parameters: `course_id`, `module_id`.
- Context: feedback module.
- Capabilities: `mod/feedback:view`.
- Returns Feedback access and state flags through `mod_feedback_external::get_feedback_access_information`.

`get_course_forums`

- Type: read.
- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`; Moodle's forum API filters out forums where the user cannot view discussions.
- Returns: course id, count, warnings, and forum summaries with instance id, course module id, type, intro, grading, subscription, tracking, locking, completion, discussion count, creation permission, unread count, and URL.
- Uses Moodle's `mod_forum_external::get_forums_by_courses` API.

`view_forum`

- Type: write.
- Parameters: `course_id`, `module_id`.
- Context: forum module.
- Capabilities: `mod/forum:viewdiscussion`.
- Registers the forum view event and completion handling through Moodle's `mod_forum_external::view_forum` API.
- Returns: forum id, module id, view status, and warnings.

`get_forum_discussions`

- Type: read.
- Parameters: `course_id`, `module_id`.
- Context: forum module.
- Capabilities: `mod/forum:viewdiscussion`.
- Returns: course id, module id, forum id, and discussion records with discussion id, first post id, name, message, author id, reply count, timestamps, reply permission, and discussion URL.
- Uses Moodle forum APIs such as `mod_forum_external::get_forum_discussions`.

`create_forum_discussion`

- Type: write.
- Parameters: `course_id`, `module_id`, `name`, `message`.
- Context: forum module.
- Capabilities: `mod/forum:startdiscussion`.
- Returns: the canonical discussion record.
- Creates a normal Moodle forum discussion in the target forum activity and does not create plugin-owned records.

`get_forum_discussion_posts`

- Type: read.
- Parameters: `course_id`, `module_id`, `discussion_id`.
- Context: forum module.
- Capabilities: `mod/forum:viewdiscussion`.
- Returns: course id, module id, forum id, discussion id, and post records with post id, parent post id, subject, message, author id, timestamps, and post URL.

`create_forum_discussion_post`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, optional `parent_post_id`, `subject`, `message`.
- Context: forum module.
- Capabilities: `mod/forum:replypost`.
- Returns: the canonical post record.

`update_forum_discussion_post`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, `post_id`, optional `subject`, optional `message`.
- Context: forum module.
- Capabilities: `mod/forum:editownpost` or `mod/forum:editanypost`.
- Returns: the updated canonical post record.
- Moodle's forum edit rules still apply, including author, role, and time-window constraints.

`set_forum_discussion_pin`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, `pinned`.
- Context: forum module.
- Capabilities: `mod/forum:pindiscussions`.
- Pins or unpins the selected discussion through Moodle's `mod_forum_external::set_pin_state` API after validating that it belongs to the selected forum activity.
- Returns: course id, module id, forum id, discussion id, and final pin state.

`set_forum_discussion_lock`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, `locked`.
- Context: forum module.
- Capabilities: `moodle/course:manageactivities`.
- Locks or unlocks the selected discussion through Moodle's `mod_forum_external::set_lock_state` API after validating that it belongs to the selected forum activity.
- Returns: course id, module id, forum id, discussion id, final lock state, and lock timestamp.

`set_forum_discussion_favourite`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, `favourite`.
- Context: forum module.
- Capabilities: `mod/forum:viewdiscussion`; Moodle's forum API applies its own favourite permission checks.
- Favourites or unfavourites the selected discussion for the current user through Moodle's `mod_forum_external::toggle_favourite_state` API after validating that it belongs to the selected forum activity.
- Returns: course id, module id, forum id, discussion id, and final favourite state for the current user.

`set_forum_discussion_subscription`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, `subscribed`.
- Context: forum module.
- Capabilities: `mod/forum:viewdiscussion`; Moodle's forum subscription rules still apply.
- Subscribes or unsubscribes the current user from the selected discussion through Moodle's `mod_forum_external::set_subscription_state` API after validating that it belongs to the selected forum activity.
- Returns: course id, module id, forum id, discussion id, and final subscription state for the current user.

`delete_forum_discussion_post`

- Type: write.
- Parameters: `course_id`, `module_id`, `discussion_id`, `post_id`.
- Context: forum module.
- Capabilities: `mod/forum:deleteownpost` or `mod/forum:deleteanypost`.
- Deletes the selected post through Moodle's `mod_forum_external::delete_post` API after validating that it belongs to the selected forum and discussion.
- If the deleted post is the first post in the discussion, Moodle deletes the discussion according to its normal forum rules.
- Returns: deletion status, deleted post id, and owning course/module/discussion ids.

`create_glossary_entry`

- Type: write.
- Parameters: `course_id`, `module_id`, `concept`, `definition`, optional `definition_format`, optional `options`.
- Context: glossary module.
- Capabilities: `mod/glossary:write`.
- Returns: entry id, glossary id, module id, concept, definition, format, approval flag, and direct entry URL.
- Creates the entry through Moodle's glossary external API and does not create plugin-owned records.

`search_glossary_entries`

- Type: read.
- Parameters: `course_id`, `module_id`, `query`, optional `full_search`, optional `order`, optional `sort`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns: matching canonical glossary entry records.

`get_course_glossaries`

- Type: read.
- Parameters: `course_id`.
- Context: course.
- Capabilities: `moodle/course:view`.
- Returns normalized Glossary activity summaries from `mod_glossary_external::get_glossaries_by_courses`, including ids, intro, display format, browse modes, approval/comment/linking settings, and whether the current user can add entries.

`view_glossary`

- Type: write.
- Parameters: `course_id`, `module_id`, optional `mode`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Registers the Glossary view through `mod_glossary_external::view_glossary`, allowing Moodle to trigger view events and completion handling.

`view_glossary_entry`

- Type: write.
- Parameters: `course_id`, `module_id`, `entry_id`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Validates that the entry belongs to the selected Glossary and registers the entry view through `mod_glossary_external::view_entry`.

`get_glossary_entry`

- Type: read.
- Parameters: `course_id`, `module_id`, `entry_id`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Validates that the entry belongs to the selected Glossary and returns the canonical entry record, update/delete permission flags, and warnings from `mod_glossary_external::get_entry_by_id`.

`get_glossary_entries_by_letter`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `letter`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns canonical glossary entry records from `mod_glossary_external::get_entries_by_letter`.

`get_glossary_entries_by_category`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `category_id`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns canonical glossary entry records from `mod_glossary_external::get_entries_by_category`. Use `category_id=0` for all categories and `category_id=-1` for uncategorised entries.
- Requires a Glossary display format whose visible tabs include Moodle's `cat` browse mode.

`get_glossary_entries_by_author`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `letter`, optional `field` (`FIRSTNAME` or `LASTNAME`), optional `sort`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns canonical glossary entry records from `mod_glossary_external::get_entries_by_author`.
- Requires a Glossary display format whose visible tabs include Moodle's `author` browse mode.

`get_glossary_entries_by_author_id`

- Type: read.
- Parameters: `course_id`, `module_id`, `author_id`, optional `order`, optional `sort`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns canonical glossary entry records from `mod_glossary_external::get_entries_by_author_id`.
- Requires a Glossary display format whose visible tabs include Moodle's `author` browse mode.

`get_glossary_entries_by_date`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `order`, optional `sort`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns canonical glossary entry records from `mod_glossary_external::get_entries_by_date`.
- Requires a Glossary display format whose visible tabs include Moodle's `date` browse mode, such as `continuous` or `fullwithoutauthor`.

`get_glossary_entries_by_term`

- Type: read.
- Parameters: `course_id`, `module_id`, `term`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns canonical glossary entry records from `mod_glossary_external::get_entries_by_term`, matching concept or alias.

`get_glossary_categories`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `from`, optional `limit`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns Glossary category ids, names, dynamic-linking state, and warnings through `mod_glossary_external::get_categories`.

`get_glossary_authors`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `from`, optional `limit`, optional `include_not_approved`.
- Context: glossary module.
- Capabilities: `mod/glossary:view`.
- Returns Glossary author ids, full names, profile picture URLs, and warnings through `mod_glossary_external::get_authors`.
- Requires a Glossary display format whose visible tabs include Moodle's `author` browse mode.

`get_glossary_entries_to_approve`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `letter`, optional `order`, optional `sort`, optional `from`, optional `limit`.
- Context: glossary module.
- Capabilities: `mod/glossary:approve`.
- Returns canonical pending-approval entry records from `mod_glossary_external::get_entries_to_approve`.

`update_glossary_entry`

- Type: write.
- Parameters: `course_id`, `module_id`, `entry_id`, optional `concept`, optional `definition`, optional `definition_format`, optional `options`.
- Context: glossary module.
- Capabilities: Moodle glossary update rules apply through the core external API.
- Returns: the updated canonical glossary entry record.

`delete_glossary_entry`

- Type: write.
- Parameters: `course_id`, `module_id`, `entry_id`.
- Context: glossary module.
- Capabilities: Moodle glossary delete rules apply through the core external API.
- Returns: deletion status.

`create_wiki_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `title`, `content`, optional `content_format`, optional `group_id`, optional `user_id`.
- Context: wiki module.
- Capabilities: `mod/wiki:editpage`.
- Returns: the canonical wiki page record with page id, wiki id, subwiki id, title, rendered content, editability, timestamps, and URL.
- Creates the page through Moodle's wiki external API and does not create plugin-owned records.

`get_wiki_pages`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `group_id`, optional `user_id`, optional `sort_by`, optional `sort_direction`, optional `include_content`.
- Context: wiki module.
- Capabilities: `mod/wiki:viewpage`.
- Returns: a canonical list of wiki page records.

`get_wiki_subwikis`

- Type: read.
- Parameters: `course_id`, `module_id`.
- Context: wiki module.
- Capabilities: `mod/wiki:viewpage`.
- Uses Moodle's `mod_wiki_external::get_subwikis` API.
- Returns: visible subwiki ids, wiki id, group/user ownership ids, editability, and Moodle warnings.

`get_wiki_files`

- Type: read.
- Parameters: `course_id`, `module_id`, optional `group_id`, optional `user_id`.
- Context: wiki module.
- Capabilities: `mod/wiki:viewpage`.
- Uses Moodle's `mod_wiki_external::get_subwiki_files` API.
- Returns: file metadata and download URLs for files attached to the resolved subwiki. Empty wikis return an empty file list.

`view_wiki`

- Type: write.
- Parameters: `course_id`, `module_id`.
- Context: wiki module.
- Capabilities: `mod/wiki:viewpage`.
- Uses Moodle's `mod_wiki_external::view_wiki` API to register the activity view and completion progress.
- Returns: wiki id, module id, view status, and Moodle warnings.

`view_wiki_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `page_id`.
- Context: wiki module.
- Capabilities: `mod/wiki:viewpage`.
- Validates that the page belongs to the selected Wiki activity, then uses Moodle's `mod_wiki_external::view_page` API to register the page view and completion progress.
- Returns: wiki id, module id, page id, view status, and Moodle warnings.

`update_wiki_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `page_id`, `content`, optional `section`.
- Context: wiki module.
- Capabilities: `mod/wiki:editpage`.
- Returns: the updated canonical wiki page record.

`delete_wiki_page`

- Type: write.
- Parameters: `course_id`, `module_id`, `page_id`.
- Context: wiki module.
- Capabilities: `mod/wiki:managewiki`.
- Returns: deletion status and the deleted page ids/title.
- Deletes the page through Moodle's `wiki_delete_pages` module API after validating that the page belongs to the selected wiki module and subwiki.

`update_module`

- Type: write.
- Parameters: `course_id`, `module_id`, optional `name`, optional `visible`, and reserved module-specific `options`.
- Context: module or course.
- Returns: updated module summary including id, name, module type, visibility, course-page visibility, download-content flag, user visibility, and URL.
- Common update options include `visible_on_course_page`, `id_number`, `group_mode`, `tags`, and `download_content`.
- `visible` and `visible_on_course_page` use Moodle's course module visibility API and must be verified through `get_course_contents`, `get_module_details`, or Moodle's course page state.
- `download_content` uses Moodle core's `set_downloadcontent` API and must be verified through `get_module_details` or the returned module summary.

`duplicate_module`

- Type: write.
- Parameters: `course_id`, source `module_id`, optional target `section_number`, optional new `name`.
- Context: module or course.
- Returns: duplicated module summary including id, name, module type, visibility, course-page visibility, download-content flag, user visibility, and URL.
- Uses Moodle core's module duplication API. It does not copy through direct table access or custom plugin storage.

`move_module`

- Type: write.
- Parameters: `course_id`, `module_id`, target `section_number`, optional `before_module_id`.
- Context: module or course.
- Returns: moved module summary including id, name, module type, visibility, course-page visibility, download-content flag, user visibility, and URL.
- Uses Moodle core's module move API. When `before_module_id` is provided, it must reference a module already in the target section.

`delete_module`

- Type: write.
- Parameters: `course_id`, `module_id`.
- Context: module or course.
- Returns: deletion status.

### Files

`upload_folder_file`

- Type: write.
- Parameters: `course_id`, `module_id`, `filename`, upload reference or file token.
- Context: module.
- Files: upload.
- Returns: file id or file metadata, filename, file URL where allowed.

`get_folder_files`

- Type: read.
- Parameters: `course_id`, `module_id`.
- Context: folder module.
- Files: none.
- Returns: stored file id, filename, file path, size, MIME type, modified timestamp, and file URL for each non-directory file in the folder.
- This operation must use Moodle's File API and must not query file tables directly.

`download_folder_file`

- Type: read.
- Parameters: `course_id`, `module_id`, `file_id` or path.
- Context: module.
- Files: download.
- Returns: metadata plus download URL or binary stream depending on transport.

`get_resource_files`

- Type: read.
- Parameters: `course_id`, `module_id`.
- Context: resource module.
- Files: none.
- Returns: stored file id, filename, file path, size, MIME type, modified timestamp, and file URL for the file resource.
- This operation must use Moodle's File API and must not query file tables directly.

`download_resource_file`

- Type: read.
- Parameters: `course_id`, `module_id`, `file_id` or path.
- Context: resource module.
- Files: download.
- Returns: metadata plus download URL or binary stream depending on transport.

`delete_folder_file`

- Type: write.
- Parameters: `course_id`, `module_id`, `file_id` or path.
- Context: module.
- Returns: deletion status.

### Question Bank And Quiz

`get_question_banks`

- Type: read.
- Parameters: `course_id`, `include_quiz_private`.
- Context: course context.
- Returns: visible course question bank modules and, when requested, quiz-owned private banks with module id, context id, scope, visibility, and bank URL.
- This operation must not create a question bank. If a course has no question bank activity, the `course_shared` result can be empty.

`get_question_categories`

- Type: read.
- Parameters: `course_id`, `bank_scope`, `question_bank_module_id`, `quiz_module_id`, `include_top`.
- Default `bank_scope`: `course_shared`.
- Context: resolved existing question bank context.
- Returns: category id, name, context id, parent id, question count, scope metadata, and a direct category URL.
- This operation must not create a question bank or a default category. It only reads existing Moodle question bank state.

`export_question_bank_blueprint`

- Type: read.
- Parameters: `course_id`, optional `bank_scope`, optional `question_bank_module_id`, optional `quiz_module_id`, optional `category_id`, optional `include_unsupported`.
- Default `bank_scope`: `course_shared`.
- Context: resolved existing question bank context.
- Capabilities: `moodle/question:viewall`.
- Returns a portable MoodlIA JSON blueprint string with schema `moodlia.question_bank_blueprint.v1`, source category metadata, directly reconstructable questions, and skipped unsupported questions.
- The blueprint is for MoodlIA automation. It is not a Moodle XML export, not a native `.mbz` backup, and does not read question bank tables directly.

`import_question_bank_blueprint`

- Type: write.
- Parameters: `course_id`, `blueprint_json`, optional `bank_scope`, optional `question_bank_module_id`, optional `quiz_module_id`, optional `category_id`, optional `create_categories`.
- Default `bank_scope`: `course_shared`.
- Context: resolved target question bank context.
- Capabilities: `moodle/question:managecategory`, `moodle/question:add`.
- Recreates category structure and questions from a MoodlIA blueprint through the existing question category and question creation operations. If `create_categories=false`, all blueprint questions are imported into the selected target category or the target bank's default category.

`create_question_category`

- Type: write.
- Parameters: `course_id`, `name`, `parent_id`, `description`, `bank_scope`, `question_bank_module_id`, `quiz_module_id`.
- Default `bank_scope`: `course_shared`.
- Supported `bank_scope` values:
  - `course_shared`: create the category in a reusable course question bank module. If `question_bank_module_id` is omitted, MoodlIA uses or creates a course question bank named `MoodlIA Question Bank`.
  - `quiz_private`: create the category in the private question bank for a specific quiz. This requires `quiz_module_id`.
- Context: resolved question bank context.
- Returns: category id, name, context id, bank scope, course question bank module id, and quiz module id.

The Moodle quiz questions page is not a storage-ownership view. It shows questions used by the quiz and may include questions stored in a course shared bank. To verify where a question lives, open the specific question bank category URL returned by `create_question_category`.

`update_question_category`

- Type: write.
- Parameters: `category_id`, patch fields.
- Context: question bank context.
- Returns: updated category summary.

`delete_question_category`

- Type: write.
- Parameters: `category_id`, delete mode.
- Context: question bank context.
- Returns: deletion status.

`create_question`

- Type: write.
- Parameters: `category_id`, `context_id`, `question_type`, `name`, `question_text`, answers/options.
- Supported `question_type` values: `truefalse`, `shortanswer`, `multichoice`, `numerical`, `essay`, `matching`, `description`, `randomsamatch`, `gapselect`, `ddwtos`, `ordering`, `multianswer`, `ddmarker`, `ddimageortext`, `calculatedsimple`, `calculated`, `calculatedmulti`.
- Matching questions use `options.subquestions` (or alias `options.pairs`) with objects containing `question` and `answer`. They require at least two subquestions and three available answers; use `options.extra_answers` for distractor answers without a stem.
- Calculated questions use `question_type=calculatedsimple`, `question_type=calculated`, or `question_type=calculatedmulti`, formula answers in `options.answers[].text` or `options.answers[].formula`, and private per-question datasets. `calculatedmulti` answer text can include calculated expressions such as `{={a}+{b}}` and also accepts the multichoice-style options `single`, `shuffle_answers`, `answer_numbering`, `correct_feedback`, `partially_correct_feedback`, and `incorrect_feedback`. Variables can be declared through `options.variables[]` with `name`, `min`, `max`, `decimals`, and `distribution`, or detected from placeholders such as `{a}`. Use `distribution` values `uniform` or `loguniform`. Dataset rows can be supplied through `options.dataset_values[]`; otherwise the operation generates values from the declared ranges.
- Context: question bank context. The supplied `context_id` must own `category_id`; MoodlIA validates this through Moodle question bank category APIs before saving the question.
- Returns: question id, category id, type, name.

`update_question`

- Type: write.
- Parameters: `question_id`, patch fields.
- Context: question bank context.
- Returns: updated question summary.

`move_question`

- Type: write.
- Parameters: `course_id`, `question_id`, `target_category_id`, optional target bank selector fields.
- Context: source and destination question bank contexts.
- Returns: source category, target category, target context, target bank scope, and moved status.
- The target category must belong to the selected target course question bank or quiz-private question bank.

`get_questions`

- Type: read.
- Parameters: `course_id`, `category_id`, optional bank selector fields.
- Context: question bank context.
- Returns: ready questions in the selected category.
- The category must belong to the selected course question bank or quiz-private question bank.

`delete_question`

- Type: write.
- Parameters: `question_id`.
- Context: question bank context.
- Returns: deletion status. Moodle may hide a question version instead of physically deleting it when the question is already in use.

`get_quiz_questions`

- Type: read.
- Parameters: `quiz_module_id`.
- Context: quiz module.
- Returns: quiz id, quiz module id, and each quiz slot with slot number, slot id, question id, question name, question type, page, and maximum mark.
- This operation reports questions used by the quiz. It does not indicate where the question is stored; use `get_question_categories` and the returned category URLs to verify question bank ownership.

`get_course_quizzes`

- Type: read.
- Parameters: optional `course_id` for one course and optional `course_ids` for batch reads. `course_ids` accepts a JSON array (`[2206,2207]`), comma-separated ids (`2206,2207`), or one scalar id (`2206`). An empty list delegates to Moodle's visible-quiz lookup.
- Context: course.
- Capabilities: Moodle validates course visibility and module access through `mod_quiz_external::get_quizzes_by_courses`; MoodlIA also requires `local/moodlia:useapi`.
- Returns selected course ids, quiz count, normalized quiz setting rows, Moodle view URLs, and warnings.

`start_quiz_attempt`

- Type: write.
- Parameters: `quiz_module_id`, optional `force_new`.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`.
- Starts a Moodle quiz attempt or preview for the authenticated user through Moodle's quiz external API.
- Returns quiz id, quiz module id, and the canonical attempt fields: attempt id, user id, attempt number, question usage id, state, preview flag, timestamps, and raw sum grades.

`get_quiz_attempts`

- Type: read.
- Parameters: `quiz_module_id`, optional `user_id`, optional `status` (`all`, `finished`, `unfinished`), optional `include_previews`.
- Context: quiz module.
- Capabilities: own attempts require `mod/quiz:attempt` or `mod/quiz:preview`; viewing another user's attempts requires `mod/quiz:viewreports`.
- Returns the canonical attempt list for the selected quiz/user using Moodle's non-deprecated quiz attempts external API.

`get_quiz_results_report`

- Type: read.
- Parameters: `quiz_module_id`, optional `limit`, optional `include_previews`.
- Context: quiz module.
- Capabilities: `mod/quiz:viewreports` and `moodle/course:viewparticipants`.
- Combines Moodle enrolment/user visibility APIs with Moodle quiz attempt and best-grade APIs to return a compact report for the selected quiz.
- Returns quiz identity, requested/returned user counts, aggregate attempt and grade counts, average best grade fields, per-user role names, attempt counters, last-attempt timestamps/state, best-grade/feedback fields, and per-user warnings.
- The report intentionally does not expose user email addresses. Consumers that need more profile data should request it through a separate capability-scoped operation rather than expanding this report.

`get_quiz_attempt_access_information`

- Type: read.
- Parameters: `quiz_module_id`, optional `attempt_id`.
- Context: quiz module.
- Capabilities: `mod/quiz:view`; Moodle applies its own current-user attempt ownership and access checks.
- Uses Moodle's `mod_quiz_external::get_attempt_access_information` API.
- Returns selected quiz ids, requested attempt id, end time, preflight requirement state, final-attempt state, prevent-new-attempt reasons, and Moodle warnings.

`get_quiz_attempt_data`

- Type: read.
- Parameters: `quiz_module_id`, `attempt_id`, optional `page`, optional `preflight_data` JSON array of name/value pairs.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`; Moodle validates attempt ownership and access rules.
- Uses Moodle's `mod_quiz_external::get_attempt_data` API after validating that the attempt belongs to the selected quiz module.
- Returns canonical attempt fields, current/next page, access messages, Moodle-rendered question HTML, question state/status/mark metadata where Moodle exposes it, response-file-area counts, and Moodle warnings.

`get_quiz_attempt_summary`

- Type: read.
- Parameters: `quiz_module_id`, `attempt_id`, optional `preflight_data` JSON array of name/value pairs.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`.
- Uses Moodle's `mod_quiz_external::get_attempt_summary` API after validating that the attempt belongs to the selected quiz module.
- Returns unanswered count where Moodle provides it, summary question rows, and Moodle warnings.

`save_quiz_attempt`

- Type: write.
- Parameters: `quiz_module_id`, `attempt_id`, optional `data` JSON array of response name/value pairs, optional `preflight_data` JSON array of name/value pairs.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`.
- Uses Moodle's `mod_quiz_external::save_attempt` API after validating that the attempt belongs to the selected quiz module.
- Returns quiz id, quiz module id, attempt id, save status, and Moodle warnings.

`process_quiz_attempt`

- Type: write.
- Parameters: `quiz_module_id`, `attempt_id`, optional `data` JSON array of response name/value pairs, optional `finish_attempt`, optional `time_up`, optional `preflight_data` JSON array of name/value pairs.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`.
- Uses Moodle's `mod_quiz_external::process_attempt` API after validating that the attempt belongs to the selected quiz module.
- Returns quiz id, quiz module id, attempt id, resulting attempt state, finished flag, and Moodle warnings.

`get_quiz_attempt_review`

- Type: read.
- Parameters: `quiz_module_id`, `attempt_id`, optional `page` where `-1` means all review pages.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt`, `mod/quiz:preview`, or `mod/quiz:viewreports`; Moodle applies its own review visibility rules.
- Uses Moodle's `mod_quiz_external::get_attempt_review` API after validating that the attempt belongs to the selected quiz module.
- Returns canonical attempt fields, formatted grade where visible, review page, additional review data, rendered reviewed question rows, and Moodle warnings.

`get_quiz_access_information`

- Type: read.
- Parameters: `quiz_module_id`.
- Context: quiz module.
- Capabilities: `mod/quiz:view`.
- Uses Moodle's `mod_quiz_external::get_quiz_access_information` API.
- Returns quiz id, quiz module id, access capability flags, active access rule names, rule descriptions, and current access prevention reasons.

`get_quiz_combined_review_options`

- Type: read.
- Parameters: `quiz_module_id`, optional `user_id`.
- Context: quiz module.
- Capabilities: `mod/quiz:view`; Moodle applies its own review and reporting visibility rules for the selected user.
- Uses Moodle's `mod_quiz_external::get_combined_review_options` API.
- Returns quiz id, quiz module id, requested user id, `some_options` and `all_options` name/value rows, and Moodle warnings. Option rows are intentionally generic because Moodle can add review flags between versions.

`view_quiz`

- Type: write.
- Parameters: `quiz_module_id`.
- Context: quiz module.
- Capabilities: `mod/quiz:view`.
- Registers the quiz view event and completion progress through Moodle's `mod_quiz_external::view_quiz` API.
- Returns quiz id, quiz module id, and view status.

`view_quiz_attempt`

- Type: write.
- Parameters: `quiz_module_id`, `attempt_id`, optional `page`, optional `preflight_data` JSON array of name/value pairs.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`.
- Registers the quiz attempt page view event through Moodle's `mod_quiz_external::view_attempt` API after validating selected quiz ownership.
- Returns quiz id, quiz module id, attempt id, page, view status, and Moodle warnings.

`view_quiz_attempt_summary`

- Type: write.
- Parameters: `quiz_module_id`, `attempt_id`, optional `preflight_data` JSON array of name/value pairs.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt` or `mod/quiz:preview`.
- Registers the quiz attempt summary view event through Moodle's `mod_quiz_external::view_attempt_summary` API after validating selected quiz ownership.
- Returns quiz id, quiz module id, attempt id, view status, and Moodle warnings.

`view_quiz_attempt_review`

- Type: write.
- Parameters: `quiz_module_id`, `attempt_id`.
- Context: quiz module.
- Capabilities: `mod/quiz:attempt`, `mod/quiz:preview`, or `mod/quiz:viewreports`.
- Registers the quiz attempt review view event through Moodle's `mod_quiz_external::view_attempt_review` API after validating selected quiz ownership.
- Returns quiz id, quiz module id, attempt id, view status, and Moodle warnings.

`get_quiz_user_best_grade`

- Type: read.
- Parameters: `quiz_module_id`, optional `user_id`.
- Context: quiz module.
- Capabilities: own grade requires quiz view access; another user's grade requires `mod/quiz:viewreports`.
- Uses Moodle's `mod_quiz_external::get_user_best_grade` API.
- Returns quiz id, quiz module id, requested user id, grade availability, best grade, grade-to-pass, and overall feedback fields when Moodle exposes them.

`get_quiz_feedback_for_grade`

- Type: read.
- Parameters: `quiz_module_id`, `grade`.
- Context: quiz module.
- Capabilities: `mod/quiz:view`.
- Uses Moodle's `mod_quiz_external::get_quiz_feedback_for_grade` API.
- Returns quiz id, quiz module id, requested grade, and overall feedback text/format for that grade.

`get_quiz_required_question_types`

- Type: read.
- Parameters: `quiz_module_id`.
- Context: quiz module.
- Capabilities: `mod/quiz:view`.
- Uses Moodle's `mod_quiz_external::get_quiz_required_qtypes` API.
- Returns quiz id, quiz module id, and question type plugin names used or potentially required by the quiz.

`add_question_to_quiz`

- Type: write.
- Parameters: `quiz_module_id`, `question_id`, slot or ordering options.
- Context: quiz module.
- Returns: quiz id, question id, slot.

`add_random_questions_to_quiz`

- Type: write.
- Parameters: `quiz_module_id`, `category_id`, `number`, optional slot, optional subcategory inclusion, and optional source bank selector fields (`course_shared` by default, or `quiz_private` for the same quiz).
- Context: quiz module and selected question bank category.
- Returns: quiz id, quiz module id, source category id, added count, and added random slots.
- Random slots are added through Moodle quiz structure APIs and use the selected category as the random question source.

`update_quiz_question_slot`

- Type: write.
- Parameters: `quiz_module_id`, `slot`, `max_mark`.
- Context: quiz module.
- Returns: quiz id, quiz module id, slot id, question id, question type, updated max mark, and update status.
- The operation updates the Moodle quiz slot mark and recomputes quiz grades through Moodle quiz APIs.

`remove_question_from_quiz`

- Type: write.
- Parameters: `quiz_module_id` and either `slot` or `question_id`.
- Context: quiz module.
- Returns: quiz id, quiz module id, removed question id, removed slot, and removal status.
- Moodle may reject removal when the quiz structure can no longer be edited, for example after real attempts exist.

## REST Requirements

Each REST operation must have:

- A `db/services.php` function declaration.
- A class under `classes/external`.
- `execute_parameters`.
- `execute`.
- `execute_returns`.
- A PHPUnit test for parameter and return typing where practical.

REST functions should call the canonical operation dispatcher.

## MCP Requirements

Each MCP operation must have:

- A tool schema in `tools/list`.
- A handler path in `tools/call`.
- Argument validation using the canonical contract.
- Result normalization matching REST where the operation is transport-compatible.

MCP tools should use canonical names exactly.

## CLI Requirements

Each CLI command must map to one canonical operation. Commands should be generated from or checked against the contract.

The current Node CLI calls Moodle REST directly, not MCP. It maps each command to the matching `local_moodlia_*` web service function and uses `MOODLE_BASE_URL` plus `MOODLE_REST_TOKEN` for authentication.

The reusable client layer lives in `client/moodle-rest-client.mjs`, with TypeScript declarations in `client/moodle-rest-client.d.ts`. CLI commands, tests, and automation scripts should call Moodle REST through this layer instead of duplicating request construction.

The CLI must also reuse the shared contract parameter builder. Required options, unknown options, enums, booleans, integer and number coercion, `minimum`/`maximum` ranges, and JSON object parameters must be validated before creating the REST transport. Invalid CLI input should return a JSON error payload and must not make a Moodle request.

The shared client validates response shapes against the operation `returns` contract after successful REST calls. The validator checks declared fields and types while allowing extra Moodle fields that are outside the canonical contract.

Advanced automation may disable only response-shape validation with `--no-validate-response` or `--raw`. Parameter validation and the REST call are unchanged. This exists for Moodle responses that are semantically usable but differ in nullable fields between Moodle versions or bank scopes.

The same `MOODLE_REST_TOKEN` is used for REST, MCP, and CLI automation. There is no separate MCP token in the current design.

Expected command pattern:

```text
moodle-mcp <operation-name-in-kebab-case> [options]
```

The public npm package exposes the same commands through the `moodlia` binary:

```text
moodlia <operation-name-in-kebab-case> [options]
```

Examples:

```text
moodle-mcp get-courses --format json
moodle-mcp create-module --course-id 42 --section-number 3 --module-type page --name "Reading"
moodle-mcp update-question --question-id 99 --name "Updated title"
moodlia get-courses --format json
```

Object parameters are passed as JSON strings:

```text
moodle-mcp create-module --course-id 42 --section-number 3 --module-type page --name "Reading" --options "{\"content\":\"<p>Hello</p>\"}"
moodle-mcp update-question --question-id 99 --patch "{\"name\":\"Updated title\"}"
```

CLI success output is pretty-printed JSON matching the canonical operation return contract. CLI failure output is a single JSON error object on stderr with `error`, `code`, `message`, and `details`.

## Parity Requirements

A contract parity test should fail when:

- A REST function exists without a canonical operation.
- A canonical operation marked for REST has no REST function.
- An MCP tool exists without a canonical operation.
- A canonical operation marked for MCP has no MCP tool handler.
- A CLI command exists without a canonical operation.
- A canonical operation marked for CLI has no command.
- A shared client method or wrapper has no REST and MCP variant where applicable.
- Return schemas differ between REST, MCP, and CLI for a compatible operation.

## Versioning

The contract should include a version. Increment it when:

- An operation is added or removed.
- A parameter is added, removed, renamed, or changes type.
- A return field is added, removed, renamed, or changes type.
- An error code is added or removed.
- Required capabilities change.

Breaking changes should be explicit and tested across all supported transports.
