# Course item backups validation, 2026-11-13

Validation of `backup_course_module`, `backup_course_section`, and
`restore_course_item_backup` (plugin build `2026111300`, release `0.1.223`, not
yet published) on disposable real Moodle sites. Each site was a fresh
`erseco/alpine-moodle` installation on PostgreSQL 17.11 (Moodle backups do not run
on SQLite), on an ARM64 lab host, with the plugin working tree overlaid and
upgraded through `admin/cli/upgrade.php`. Every container and network was removed
after the run.

## Live REST and MCP scenario

A 12-step scenario called the plugin through `webservice/rest/server.php` and
`local/moodlia/mcp.php` with an administrator token:

1. Create an assignment in section 1 and a forum and glossary in section 2 of the source course.
2. `backup_course_module` on the assignment; download the file through `webservice/pluginfile.php` and compare its size.
3. `restore_course_item_backup` into a new target section (`target_section_number`); verify with `get_course_contents` and `get_module_details`.
4. Restore again without `target_section_number`: the module lands in section 1.
5. `backup_course_section` on section 2, download it, and restore it: only the forum and glossary are restored, in section 2.
6. A section backup with `target_section_number` is rejected.
7. A course backup is rejected and the error names `restore_course_backup`.
8. A missing `target_section_number` is rejected before anything is restored.
9. An activity backup with user data is stored in the activity area, listed by `get_course_backup_files` with `backup_area` and `module_id`, downloaded, and deleted with `delete_course_backup_file`.
10. Private item backups are listed with `backup_area: user_backup`.
11. MCP `tools/list` publishes 255 tools, including the three new ones, and `tools/call` runs `backup_course_module`.
12. Every backup created by the scenario is deleted.

| Moodle | PHP | `php -l` on changed files | Scenario |
| --- | --- | --- | --- |
| 4.5.12 (Build: 20260608) | 8.3.15 | 0 failures | 12/12 |
| 5.2.4 (Build: 20261005) | 8.3.15 | 0 failures | 12/12 |
| 5.3beta (Build: 20260916) | 8.4.21 | 0 failures | 12/12, build `2026111300` |

## PHPUnit

`tests/course_item_backup_test.php` (4 tests, 30 assertions) and the existing
backup regressions (`course_backup_tools_test` and
`test_section_summary_file_survives_native_backup_restore`, 3 tests):

| Moodle | PHPUnit | New tests | Existing backup tests |
| --- | --- | --- | --- |
| 4.5.12 | 9.6.34 | 4/4 pass | 3/3 pass |
| 5.3beta | 11.5.56 | 4/4 pass, no notices or deprecations | 3/3 pass (12 pre-existing doc-comment metadata deprecations) |

## Defects found and fixed during validation

- `db/services.php` registered the three functions with a malformed class name.
  A new static test now checks that every service class name matches its
  external class.
- The restore used `get_fast_modinfo($courseid, 0, true)`, which only resets the
  cache and returns `null`.
- Moving the restored activity used `moveto_module()`, deprecated in Moodle 5.2
  (MDL-86854). The operation now uses `cmactions::move_end_section()` when
  available and keeps `moveto_module()` for Moodle 4.5 to 5.1.
- An explicit `moodle/restore:restoreactivity` check was unreachable: Moodle's
  restore controller enforces the capability first and fails with
  `restore_user_missing_capability`. The check was removed and the test asserts
  Moodle's error.

## Not covered

- Section backups that contain Moodle 5.x subsections (delegated sections).
- Restores by non-administrator accounts through REST; PHPUnit covers the
  missing-capability case.
- The full GitHub Actions matrix; run `moodle-ci.yml` before publishing.
