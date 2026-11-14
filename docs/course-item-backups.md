# Course item backups

Plugin `0.1.223` (build `2026111300`) exports a single activity or a single
section of a course as a native Moodle `.mbz` backup and imports it into another
existing course. All three operations use Moodle's own backup and restore
controllers, so the files are the same ones Moodle's interface produces and can
be restored there too.

| Operation | Moodle backup type | What it does |
| --- | --- | --- |
| `backup_course_module` | `TYPE_1ACTIVITY` | Backs up one course module, for example an assignment, quiz, or page. |
| `backup_course_section` | `TYPE_1SECTION` | Backs up one section and, unless `include_activities` is false, its activities. |
| `restore_course_item_backup` | `TARGET_EXISTING_ADDING` | Imports an activity or section backup into an existing course without deleting anything. |

Course backups keep using `backup_course` and `restore_course_backup`.
`restore_course_item_backup` rejects them.

## Exporting

`backup_course_module` takes `course_id` and `module_id`. `backup_course_section`
takes `course_id` and `section_number`. Both accept the same options as
`backup_course`: `filename` (`.mbz` is appended when missing), `include_users`,
`include_blocks`, `include_filters`, `include_comments`, `include_logs`, and
`include_grade_histories`. The section backup also takes `include_activities`.

Without `filename`, the file gets Moodle's default name, for example
`backup-moodle2-activity-201-assign201-20261114-1630-nu.mbz`. A new backup with
the same name in the same area replaces the earlier file, as in Moodle's backup
interface, so name repeated backups of the same item within one minute
explicitly. Before release `0.1.224`, every unnamed backup was called
`backup.mbz` and replaced the previous one.

The response contains the stored file metadata (`file_id`, `filename`, `url`,
`filepath`, `filesize`, `mimetype`, `time_modified`). The activity backup also
returns `module_id`, `module_name`, and `section_number`; the section backup
returns `section_id` and `section_number`.

Where Moodle stores the file depends on whether it contains user data:

- Without user data (the default), the file goes to the caller's private backup
  area (`user/backup`), so it survives if the activity or course is deleted.
- With `include_users`, Moodle stores it in the activity backup area
  (`backup/activity` in the module context) or the section backup area
  (`backup/section` in the course context). An activity-area file is deleted
  together with the activity.

Download the file through `url` with the web service file endpoint, as for course
backups. `get_course_backup_files` lists all of them; each entry carries
`backup_area` (`course`, `section`, `activity`, `user_backup`, or `user_private`),
and activity-area entries also carry `module_id`.

## Importing

`restore_course_item_backup` takes `backup_file_id`, `target_course_id`, and an
optional `target_section_number`. To import a file from another site, upload it
first with `upload_course_backup` and use the returned `file_id`.

- **Activity backups** are restored into the section with the same number they
  had in the source course, or section 0 when the target course has no such
  section. With `target_section_number`, the restored module is moved to the end
  of that section, which must already exist; a missing section is rejected before
  anything is restored.
- **Section backups** are merged by Moodle into the section with the same number.
  When the target course has no such section, Moodle creates it with the source
  name and summary. When it exists, the restored activities are appended and the
  section keeps its own name and summary, which Moodle only fills when empty.
  `target_section_number` is rejected for section backups.

The response returns `backup_type` (`activity` or `section`), `restored`,
`modules_json` (the restored modules with `module_id`, `module_name`, `name`, and
`section_number`), and `warnings_json` (non-fatal restore precheck warnings).

## Permissions

| Operation | Required in addition to `local/moodlia:useapi` |
| --- | --- |
| `backup_course_module` | `moodle/backup:backupactivity` in the module context; `moodle/backup:userinfo` with `include_users`. |
| `backup_course_section` | `moodle/backup:backupsection` in the course context; `moodle/backup:userinfo` with `include_users`. |
| `restore_course_item_backup` | Read access to the source file, `moodle/course:manageactivities` in the target course, and `moodle/restore:restoreactivity` or `moodle/restore:restoresection` for the backup type. Moodle's restore controller enforces the last one and fails with `restore_user_missing_capability`; it also requires `moodle/restore:userinfo` for backups with user data. |

Read access to a stored backup file is shared by `restore_course_item_backup`,
`restore_course_backup`, and `delete_course_backup_file`: callers always own their
user files; other files require the backup capability of the context that stores
them (`moodle/backup:backupcourse` for course backups,
`moodle/backup:backupsection` for section backups, and
`moodle/backup:backupactivity` for activity backups).

## Examples

REST:

```text
wsfunction=local_moodlia_backup_course_module&course_id=42&module_id=201&filename=essay
wsfunction=local_moodlia_restore_course_item_backup&backup_file_id=987&target_course_id=57&target_section_number=3
wsfunction=local_moodlia_backup_course_section&course_id=42&section_number=2
```

MCP:

```json
{ "name": "backup_course_module", "arguments": { "course_id": 42, "module_id": 201 } }
{ "name": "restore_course_item_backup", "arguments": { "backup_file_id": 987, "target_course_id": 57 } }
```

## Upgrade

The three operations are new web service functions. Upgrade the plugin to build
`2026111300` or later so Moodle registers them, and reconnect MCP clients to
reload the tool catalog.

## Validation

See the [validation report](course-item-backups-validation-2026-11-13.md) for
the live REST/MCP scenario and PHPUnit runs on Moodle 4.5.12, 5.2.4, and 5.3 beta.
