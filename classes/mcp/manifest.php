<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * MCP tool manifest.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\mcp;

/**
 * Contract-backed MCP manifest.
 */
final class manifest {
    /**
     * Return MCP tools exposed by this plugin.
     *
     * @return array
     */
    public static function tools(): array {
        return [
            [
                'name' => 'get_current_user',
                'description' => 'Return the authenticated Moodle user visible to the current transport.',
                'inputSchema' => self::schema([]),
            ],
            [
                'name' => 'get_moodlia_status',
                'description' => 'Return MoodlIA plugin, Moodle site, token, and declared service diagnostics.',
                'inputSchema' => self::schema([]),
            ],
            [
                'name' => 'list_plugins',
                'description' => 'Return an administrative inventory of installed, pending, and missing Moodle plugins.',
                'inputSchema' => self::schema([
                    'plugin_type' => ['type' => 'string', 'required' => false],
                    'source' => ['type' => 'string', 'required' => false, 'enum' => ['all', 'standard', 'additional', 'missing']],
                    'status' => ['type' => 'string', 'required' => false, 'enum' => ['all', 'nodb', 'uptodate', 'new', 'upgrade', 'delete', 'downgrade', 'missing']],
                ]),
            ],
            [
                'name' => 'get_plugin_details',
                'description' => 'Return administrative metadata for one installed, pending, or missing Moodle plugin.',
                'inputSchema' => self::schema([
                    'component' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_plugin_dependencies',
                'description' => 'Return resolved dependencies and reverse dependencies for one Moodle plugin.',
                'inputSchema' => self::schema([
                    'component' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'check_plugin_updates',
                'description' => 'Return cached Moodle plugin updates and optionally refresh update information from Moodle.org.',
                'inputSchema' => self::schema([
                    'component' => ['type' => 'string', 'required' => false],
                    'refresh' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'set_plugin_enabled',
                'description' => 'Enable or disable one compatible Moodle plugin without installing or removing code.',
                'inputSchema' => self::schema([
                    'component' => ['type' => 'string', 'required' => true],
                    'enabled' => ['type' => 'boolean', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_courses',
                'description' => 'Return Moodle courses visible to the authenticated user.',
                'inputSchema' => self::schema([
                    'limit' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_course_categories',
                'description' => 'Return Moodle course categories visible to the authenticated user.',
                'inputSchema' => self::schema([
                    'parent_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_course_category',
                'description' => 'Create a Moodle course category.',
                'inputSchema' => self::schema([
                    'name' => ['type' => 'string', 'required' => true],
                    'parent_id' => ['type' => 'integer', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                    'reuse_existing' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_course_category',
                'description' => 'Update a Moodle course category.',
                'inputSchema' => self::schema([
                    'category_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_course_category',
                'description' => 'Delete a Moodle course category.',
                'inputSchema' => self::schema([
                    'category_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_contents',
                'description' => 'Return sections and modules visible in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_details',
                'description' => 'Return Moodle course metadata and settings.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_module_details',
                'description' => 'Return common details for a Moodle course module.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_calendar_events',
                'description' => 'Return Moodle course calendar events in a time range.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'time_from' => ['type' => 'integer', 'required' => true],
                    'time_to' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_calendar_event',
                'description' => 'Create a Moodle course calendar event.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'timestart' => ['type' => 'integer', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'timeduration' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_calendar_event',
                'description' => 'Update a Moodle course calendar event.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'event_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'description' => ['type' => 'string', 'required' => false],
                    'timestart' => ['type' => 'integer', 'required' => false],
                    'timeduration' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_calendar_event',
                'description' => 'Delete a Moodle course calendar event.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'event_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_enrolled_users',
                'description' => 'Return users enrolled in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_grade_items',
                'description' => 'Return Moodle gradebook items for a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_user_grades',
                'description' => 'Return Moodle gradebook grades for a user in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                    'group_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_grade_categories',
                'description' => 'Return Moodle gradebook categories for a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_grade_category',
                'description' => 'Create a Moodle gradebook category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'aggregation' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_grade_category',
                'description' => 'Update a Moodle gradebook category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'aggregation' => ['type' => 'integer', 'required' => false],
                    'hidden' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_grade_category',
                'description' => 'Delete a Moodle gradebook category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_grade_item',
                'description' => 'Create a manual Moodle gradebook item.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'grade_max' => ['type' => 'number', 'required' => false],
                    'grade_min' => ['type' => 'number', 'required' => false],
                    'grade_pass' => ['type' => 'number', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'hidden' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_grade_item',
                'description' => 'Update a manual Moodle gradebook item.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'item_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'grade_max' => ['type' => 'number', 'required' => false],
                    'grade_min' => ['type' => 'number', 'required' => false],
                    'grade_pass' => ['type' => 'number', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'hidden' => ['type' => 'boolean', 'required' => false],
                    'locked' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_grade_item',
                'description' => 'Delete a manual Moodle gradebook item.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'item_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'update_grade_value',
                'description' => 'Update a manual Moodle gradebook value for a user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'item_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'grade' => ['type' => 'number', 'required' => true],
                    'feedback' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_course_completion_status',
                'description' => 'Return Moodle course completion status for a user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_activity_completion_statuses',
                'description' => 'Return Moodle activity completion statuses for a user in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_course_progress_report',
                'description' => 'Return a compact progress and grade report for enrolled users in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'limit' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'set_activity_completion_status',
                'description' => 'Mark a manually completable Moodle activity complete or incomplete for the current user.',
                'inputSchema' => self::schema([
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'completed' => ['type' => 'boolean', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_user_details',
                'description' => 'Return Moodle user profile details.',
                'inputSchema' => self::schema([
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_user',
                'description' => 'Create a Moodle user account.',
                'inputSchema' => self::schema([
                    'username' => ['type' => 'string', 'required' => true],
                    'firstname' => ['type' => 'string', 'required' => true],
                    'lastname' => ['type' => 'string', 'required' => true],
                    'email' => ['type' => 'string', 'required' => true],
                    'password' => ['type' => 'string', 'required' => true],
                    'auth' => ['type' => 'string', 'required' => false],
                    'suspended' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_user',
                'description' => 'Update Moodle user account fields.',
                'inputSchema' => self::schema([
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'firstname' => ['type' => 'string', 'required' => false],
                    'lastname' => ['type' => 'string', 'required' => false],
                    'email' => ['type' => 'string', 'required' => false],
                    'password' => ['type' => 'string', 'required' => false],
                    'auth' => ['type' => 'string', 'required' => false],
                    'suspended' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_user',
                'description' => 'Delete a Moodle user account.',
                'inputSchema' => self::schema([
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_cohort',
                'description' => 'Create a Moodle site cohort.',
                'inputSchema' => self::schema([
                    'name' => ['type' => 'string', 'required' => true],
                    'idnumber' => ['type' => 'string', 'required' => false],
                    'description' => ['type' => 'string', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_cohort',
                'description' => 'Update a Moodle site cohort.',
                'inputSchema' => self::schema([
                    'cohort_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'idnumber' => ['type' => 'string', 'required' => false],
                    'description' => ['type' => 'string', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_cohort',
                'description' => 'Delete a Moodle site cohort.',
                'inputSchema' => self::schema([
                    'cohort_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'add_cohort_member',
                'description' => 'Add a Moodle user to a site cohort.',
                'inputSchema' => self::schema([
                    'cohort_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'remove_cohort_member',
                'description' => 'Remove a Moodle user from a site cohort.',
                'inputSchema' => self::schema([
                    'cohort_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'assign_course_role',
                'description' => 'Assign a course role to a Moodle user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'role_archetype' => ['type' => 'string', 'required' => false, 'enum' => ['student', 'teacher', 'editingteacher']],
                ]),
            ],
            [
                'name' => 'unassign_course_role',
                'description' => 'Unassign a course role from a Moodle user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'role_archetype' => ['type' => 'string', 'required' => false, 'enum' => ['student', 'teacher', 'editingteacher']],
                ]),
            ],
            [
                'name' => 'enrol_user',
                'description' => 'Enrol a Moodle user in a course through the manual enrolment plugin.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'role_archetype' => ['type' => 'string', 'required' => false, 'enum' => ['student', 'teacher', 'editingteacher']],
                ]),
            ],
            [
                'name' => 'unenrol_user',
                'description' => 'Unenrol a Moodle user from a course manual enrolment instance.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_groups',
                'description' => 'Return Moodle groups in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_group',
                'description' => 'Create a Moodle group in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'idnumber' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_group',
                'description' => 'Update a Moodle course group.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'description' => ['type' => 'string', 'required' => false],
                    'idnumber' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_group',
                'description' => 'Delete a Moodle course group.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_groupings',
                'description' => 'Return Moodle groupings in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_grouping',
                'description' => 'Create a Moodle grouping in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'idnumber' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_grouping',
                'description' => 'Update a Moodle course grouping.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'grouping_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'description' => ['type' => 'string', 'required' => false],
                    'idnumber' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_grouping',
                'description' => 'Delete a Moodle course grouping.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'grouping_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'add_group_to_grouping',
                'description' => 'Add a Moodle group to a grouping.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'grouping_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'remove_group_from_grouping',
                'description' => 'Remove a Moodle group from a grouping.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'grouping_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_group_members',
                'description' => 'Return members of a Moodle course group.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'add_group_member',
                'description' => 'Add a Moodle user to a course group.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'remove_group_member',
                'description' => 'Remove a Moodle user from a course group.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_course',
                'description' => 'Create a Moodle course in an existing Moodle category.',
                'inputSchema' => self::schema([
                    'fullname' => ['type' => 'string', 'required' => true],
                    'shortname' => ['type' => 'string', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                    'summary' => ['type' => 'string', 'required' => false],
                    'summary_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                    'course_format' => ['type' => 'string', 'required' => false],
                    'start_date' => ['type' => 'integer', 'required' => false],
                    'end_date' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'export_course_blueprint',
                'description' => 'Export a Moodle course as a portable MoodlIA course blueprint for lightweight backup, templates, and restore workflows.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'include_contents' => ['type' => 'boolean', 'required' => false],
                    'include_groups' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_course_from_blueprint',
                'description' => 'Create a Moodle course from a portable MoodlIA course blueprint.',
                'inputSchema' => self::schema([
                    'blueprint' => ['type' => 'object', 'required' => true],
                ]),
            ],
            [
                'name' => 'apply_course_blueprint',
                'description' => 'Apply sections, module shells, groups, and enrolments from a MoodlIA blueprint to an existing Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'blueprint' => ['type' => 'object', 'required' => true],
                ]),
            ],
            [
                'name' => 'copy_course_structure',
                'description' => 'Copy section structure, module shells, and optionally groups from one Moodle course into another.',
                'inputSchema' => self::schema([
                    'source_course_id' => ['type' => 'integer', 'required' => true],
                    'target_course_id' => ['type' => 'integer', 'required' => true],
                    'include_contents' => ['type' => 'boolean', 'required' => false],
                    'include_groups' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'sync_course_enrolments',
                'description' => 'Synchronise manual course enrolments from a desired user and role list.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'enrolments' => ['type' => 'string', 'required' => true],
                    'unenrol_missing' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'set_course_publish_state',
                'description' => 'Apply a course publishing workflow state mapped to Moodle course visibility and archive metadata.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'publish_state' => ['type' => 'string', 'required' => true, 'enum' => ['draft', 'ready', 'published', 'archived']],
                ]),
            ],
            [
                'name' => 'audit_course',
                'description' => 'Audit a Moodle course for operational readiness issues.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'backup_course',
                'description' => 'Create a native Moodle .mbz course backup.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'filename' => ['type' => 'string', 'required' => false],
                    'include_users' => ['type' => 'boolean', 'required' => false],
                    'include_activities' => ['type' => 'boolean', 'required' => false],
                    'include_blocks' => ['type' => 'boolean', 'required' => false],
                    'include_filters' => ['type' => 'boolean', 'required' => false],
                    'include_comments' => ['type' => 'boolean', 'required' => false],
                    'include_logs' => ['type' => 'boolean', 'required' => false],
                    'include_grade_histories' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'restore_course_backup',
                'description' => 'Restore a native Moodle .mbz course backup into a new or existing course.',
                'inputSchema' => self::schema([
                    'backup_file_id' => ['type' => 'integer', 'required' => true],
                    'target' => ['type' => 'string', 'required' => false, 'enum' => ['new_course', 'existing_add', 'existing_delete']],
                    'target_course_id' => ['type' => 'integer', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'fullname' => ['type' => 'string', 'required' => false],
                    'shortname' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'upload_course_backup',
                'description' => 'Upload a native Moodle .mbz backup file for later restore.',
                'inputSchema' => self::schema([
                    'filename' => ['type' => 'string', 'required' => true],
                    'upload_reference' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_backup_files',
                'description' => 'List native Moodle .mbz backup files available to the caller.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => false],
                    'include_private' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_course_backup_file',
                'description' => 'Delete a native Moodle .mbz backup file.',
                'inputSchema' => self::schema([
                    'file_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'audit_course_completion',
                'description' => 'Audit activity completion settings in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'include_ok' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'repair_course_completion',
                'description' => 'Repair activity completion settings in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'mode' => ['type' => 'string', 'required' => false, 'enum' => ['book_view_only', 'all_grade_to_view', 'disable_all']],
                    'dry_run' => ['type' => 'boolean', 'required' => false],
                    'reset_completion_states' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_course',
                'description' => 'Update Moodle course identity, category, visibility, summary, format, and date fields.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'fullname' => ['type' => 'string', 'required' => false],
                    'shortname' => ['type' => 'string', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                    'summary' => ['type' => 'string', 'required' => false],
                    'summary_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                    'course_format' => ['type' => 'string', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'start_date' => ['type' => 'integer', 'required' => false],
                    'end_date' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'move_course',
                'description' => 'Move a Moodle course to another category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'delete_course',
                'description' => 'Delete a controlled Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_section',
                'description' => 'Create a course section in a controlled Moodle course context.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'summary' => ['type' => 'string', 'required' => false],
                    'position' => ['type' => 'integer', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_section',
                'description' => 'Update a course section name, summary, or visibility.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'section_id' => ['type' => 'integer', 'required' => false],
                    'section_number' => ['type' => 'integer', 'required' => false],
                    'name' => ['type' => 'string', 'required' => false],
                    'summary' => ['type' => 'string', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_section',
                'description' => 'Delete or clear a controlled Moodle course section.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'section_id' => ['type' => 'integer', 'required' => false],
                    'section_number' => ['type' => 'integer', 'required' => false],
                    'delete_mode' => ['type' => 'string', 'required' => false, 'enum' => ['delete', 'clear']],
                ]),
            ],
            [
                'name' => 'create_module',
                'description' => 'Create a Moodle course module in a section.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'section_number' => ['type' => 'integer', 'required' => true],
                    'module_type' => [
                        'type' => 'string',
                        'required' => true,
                        'enum' => [
                            'assign',
                            'book',
                            'choice',
                            'data',
                            'feedback',
                            'lesson',
                            'lti',
                            'page',
                            'folder',
                            'forum',
                            'glossary',
                            'label',
                            'qbank',
                            'quiz',
                            'resource',
                            'subsection',
                            'url',
                            'wiki',
                            'workshop',
                        ],
                    ],
                    'name' => ['type' => 'string', 'required' => true],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_module',
                'description' => 'Update a Moodle course module.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'visible' => ['type' => 'boolean', 'required' => false],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'duplicate_module',
                'description' => 'Duplicate a Moodle course module.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'section_number' => ['type' => 'integer', 'required' => false],
                    'name' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'move_module',
                'description' => 'Move a Moodle course module to a target section.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'section_number' => ['type' => 'integer', 'required' => true],
                    'before_module_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_course_books',
                'description' => 'Return Book activities in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_book_chapters',
                'description' => 'List chapters in a Moodle book activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'include_content' => ['type' => 'boolean', 'required' => false],
                    'include_hidden' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_book',
                'description' => 'Register a Moodle book or chapter view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'chapter_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_book_chapter',
                'description' => 'Create a chapter in a Moodle book activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => true],
                    'content' => ['type' => 'string', 'required' => true],
                    'content_format' => ['type' => 'integer', 'required' => false],
                    'subchapter' => ['type' => 'boolean', 'required' => false],
                    'after_chapter_id' => ['type' => 'integer', 'required' => false],
                    'hidden' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_book_chapter',
                'description' => 'Update a chapter in a Moodle book activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'chapter_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => false],
                    'content' => ['type' => 'string', 'required' => false],
                    'content_format' => ['type' => 'integer', 'required' => false],
                    'subchapter' => ['type' => 'boolean', 'required' => false],
                    'hidden' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'move_book_chapter',
                'description' => 'Move a chapter in a Moodle book activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'chapter_id' => ['type' => 'integer', 'required' => true],
                    'after_chapter_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_book_chapter',
                'description' => 'Delete a chapter from a Moodle book activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'chapter_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_lesson_access_information',
                'description' => 'Return current-user access information for a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_lesson_details',
                'description' => 'Return settings and metadata for a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'password' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_course_lessons',
                'description' => 'List Moodle Lesson activities in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_lesson_pages',
                'description' => 'List pages in a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'password' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_lesson_page',
                'description' => 'Create a content or supported question page in a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => true],
                    'content' => ['type' => 'string', 'required' => true],
                    'content_format' => ['type' => 'integer', 'required' => false],
                    'branches' => ['type' => 'object', 'required' => false],
                    'after_page_id' => ['type' => 'integer', 'required' => false],
                    'display_in_menu' => ['type' => 'boolean', 'required' => false],
                    'horizontal' => ['type' => 'boolean', 'required' => false],
                    'page_type' => [
                        'type' => 'string',
                        'required' => false,
                        'enum' => ['content', 'essay', 'matching', 'multichoice', 'numerical', 'shortanswer', 'truefalse'],
                    ],
                    'answers' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_lesson_page',
                'description' => 'Update a content or supported question page in a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'page_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => false],
                    'content' => ['type' => 'string', 'required' => false],
                    'content_format' => ['type' => 'integer', 'required' => false],
                    'branches' => ['type' => 'object', 'required' => false],
                    'display_in_menu' => ['type' => 'boolean', 'required' => false],
                    'horizontal' => ['type' => 'boolean', 'required' => false],
                    'answers' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_lesson_page',
                'description' => 'Delete a page from a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'page_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_lesson',
                'description' => 'Register a Moodle Lesson view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'password' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_lesson_user_grade',
                'description' => 'Return the final grade for a user in a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_lesson_user_timers',
                'description' => 'Return timer sessions for a user in a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_lesson_possible_jumps',
                'description' => 'Return possible page jumps for a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_lesson_attempts_overview',
                'description' => 'Return report overview data for attempts in a Moodle Lesson activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_data_fields',
                'description' => 'List fields in a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_data_field',
                'description' => 'Create a field in a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'field_type' => ['type' => 'string', 'required' => true, 'enum' => ['text', 'textarea', 'number', 'menu', 'checkbox', 'radiobutton', 'multimenu', 'url']],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'required' => ['type' => 'boolean', 'required' => false],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_data_field',
                'description' => 'Update a field in a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'field_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'required' => ['type' => 'boolean', 'required' => false],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_data_field',
                'description' => 'Delete a field from a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'field_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_data_entries',
                'description' => 'List entries in a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'search' => ['type' => 'string', 'required' => false],
                    'include_contents' => ['type' => 'boolean', 'required' => false],
                    'page' => ['type' => 'integer', 'required' => false],
                    'per_page' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_data_entry',
                'description' => 'Create an entry in a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'values' => ['type' => 'object', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_data_entry',
                'description' => 'Update an entry in a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'entry_id' => ['type' => 'integer', 'required' => true],
                    'values' => ['type' => 'object', 'required' => true],
                ]),
            ],
            [
                'name' => 'delete_data_entry',
                'description' => 'Delete an entry from a Moodle Database activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'entry_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'set_workshop_phase',
                'description' => 'Change the phase of a Moodle Workshop activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'phase' => ['type' => 'string', 'required' => true, 'enum' => ['setup', 'submission', 'assessment', 'evaluation', 'closed']],
                ]),
            ],
            [
                'name' => 'get_workshop_submissions',
                'description' => 'List submissions in a Moodle Workshop activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                    'group_id' => ['type' => 'integer', 'required' => false],
                    'page' => ['type' => 'integer', 'required' => false],
                    'per_page' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_workshop_user_plan',
                'description' => 'Read the phase plan and task state for a Moodle Workshop activity user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_workshop_grades',
                'description' => 'Read grade information for a Moodle Workshop activity user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_workshop_grades_report',
                'description' => 'Read the grades report for a Moodle Workshop activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => false],
                    'sort_by' => [
                        'type' => 'string',
                        'required' => false,
                        'enum' => [
                            'lastname',
                            'firstname',
                            'submissiontitle',
                            'submissionmodified',
                            'submissiongrade',
                            'gradinggrade',
                        ],
                    ],
                    'sort_direction' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'page' => ['type' => 'integer', 'required' => false],
                    'per_page' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_workshop_reviewer_assessments',
                'description' => 'List assessments assigned to a Moodle Workshop reviewer.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_workshop_submission_assessments',
                'description' => 'List assessments for a Moodle Workshop submission.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'submission_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'allocate_workshop_submission',
                'description' => 'Allocate a Moodle Workshop submission to a reviewer.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'submission_id' => ['type' => 'integer', 'required' => true],
                    'reviewer_id' => ['type' => 'integer', 'required' => false],
                    'weight' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_workshop_assessment_form_definition',
                'description' => 'Return form data for a Moodle Workshop assessment.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'assessment_id' => ['type' => 'integer', 'required' => true],
                    'mode' => ['type' => 'string', 'required' => false, 'enum' => ['assessment', 'preview']],
                ]),
            ],
            [
                'name' => 'set_workshop_grading_form',
                'description' => 'Create or replace a supported Moodle Workshop grading form.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'strategy' => ['type' => 'string', 'required' => true, 'enum' => ['accumulative', 'comments', 'numerrors', 'rubric']],
                    'definition' => ['type' => 'object', 'required' => true],
                ]),
            ],
            [
                'name' => 'update_workshop_assessment',
                'description' => 'Update a Moodle Workshop assessment using Moodle assessment form data.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'assessment_id' => ['type' => 'integer', 'required' => true],
                    'data_json' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'evaluate_workshop_assessment',
                'description' => 'Evaluate a Moodle Workshop assessment as a teacher or allocator.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'assessment_id' => ['type' => 'integer', 'required' => true],
                    'feedback_text' => ['type' => 'string', 'required' => false],
                    'feedback_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                    'weight' => ['type' => 'integer', 'required' => false],
                    'grading_grade_over' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_workshop_submission',
                'description' => 'Create a submission in a Moodle Workshop activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => true],
                    'content' => ['type' => 'string', 'required' => false],
                    'content_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                ]),
            ],
            [
                'name' => 'update_workshop_submission',
                'description' => 'Update a submission in a Moodle Workshop activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'submission_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => true],
                    'content' => ['type' => 'string', 'required' => false],
                    'content_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                ]),
            ],
            [
                'name' => 'delete_workshop_submission',
                'description' => 'Delete a submission from a Moodle Workshop activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'submission_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_glossary_entry',
                'description' => 'Create an entry in a Moodle glossary activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'concept' => ['type' => 'string', 'required' => true],
                    'definition' => ['type' => 'string', 'required' => true],
                    'definition_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_course_glossaries',
                'description' => 'List Moodle Glossary activities in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_glossary',
                'description' => 'Register a Moodle Glossary view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'mode' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_glossary_entry',
                'description' => 'Register a Moodle Glossary entry view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'entry_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_glossary_entry',
                'description' => 'Read one Moodle Glossary entry by id.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'entry_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_by_letter',
                'description' => 'List Moodle Glossary entries by letter.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'letter' => ['type' => 'string', 'required' => false],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_by_category',
                'description' => 'List Moodle Glossary entries by category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_by_author',
                'description' => 'List Moodle Glossary entries by author name.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'letter' => ['type' => 'string', 'required' => false],
                    'field' => ['type' => 'string', 'required' => false, 'enum' => ['FIRSTNAME', 'LASTNAME']],
                    'sort' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_by_author_id',
                'description' => 'List Moodle Glossary entries by author id.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'author_id' => ['type' => 'integer', 'required' => true],
                    'order' => ['type' => 'string', 'required' => false, 'enum' => ['CONCEPT', 'CREATION', 'UPDATE']],
                    'sort' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_by_date',
                'description' => 'List Moodle Glossary entries by creation or update date.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'order' => ['type' => 'string', 'required' => false, 'enum' => ['CREATION', 'UPDATE']],
                    'sort' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_by_term',
                'description' => 'List Moodle Glossary entries by concept or alias term.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'term' => ['type' => 'string', 'required' => true],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_categories',
                'description' => 'List categories in a Moodle Glossary activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_authors',
                'description' => 'List authors in a Moodle Glossary activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'search_glossary_entries',
                'description' => 'Search entries in a Moodle glossary activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'query' => ['type' => 'string', 'required' => true],
                    'full_search' => ['type' => 'boolean', 'required' => false],
                    'order' => ['type' => 'string', 'required' => false, 'enum' => ['CONCEPT', 'CREATION', 'UPDATE']],
                    'sort' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_not_approved' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_glossary_entries_to_approve',
                'description' => 'List Moodle Glossary entries pending approval.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'letter' => ['type' => 'string', 'required' => false],
                    'order' => ['type' => 'string', 'required' => false, 'enum' => ['CONCEPT', 'CREATION', 'UPDATE']],
                    'sort' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'from' => ['type' => 'integer', 'required' => false],
                    'limit' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_glossary_entry',
                'description' => 'Update an entry in a Moodle glossary activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'entry_id' => ['type' => 'integer', 'required' => true],
                    'concept' => ['type' => 'string', 'required' => false],
                    'definition' => ['type' => 'string', 'required' => false],
                    'definition_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'plain']],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_glossary_entry',
                'description' => 'Delete an entry from a Moodle glossary activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'entry_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_wiki_page',
                'description' => 'Create a page in a Moodle wiki activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'title' => ['type' => 'string', 'required' => true],
                    'content' => ['type' => 'string', 'required' => true],
                    'content_format' => ['type' => 'string', 'required' => false, 'enum' => ['html', 'creole', 'nwiki']],
                    'group_id' => ['type' => 'integer', 'required' => false],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_wiki_pages',
                'description' => 'List pages in a Moodle wiki activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => false],
                    'user_id' => ['type' => 'integer', 'required' => false],
                    'sort_by' => ['type' => 'string', 'required' => false],
                    'sort_direction' => ['type' => 'string', 'required' => false, 'enum' => ['ASC', 'DESC']],
                    'include_content' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_wiki_subwikis',
                'description' => 'List visible subwikis in a Moodle wiki activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_wiki_files',
                'description' => 'List files attached to a Moodle wiki subwiki.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => false],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_wiki',
                'description' => 'Register a Moodle Wiki activity view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_wiki_page',
                'description' => 'Register a Moodle Wiki page view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'page_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'update_wiki_page',
                'description' => 'Update a page in a Moodle wiki activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'page_id' => ['type' => 'integer', 'required' => true],
                    'content' => ['type' => 'string', 'required' => true],
                    'section' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_wiki_page',
                'description' => 'Delete a page from a Moodle wiki activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'page_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_choice_options',
                'description' => 'Return options for a Moodle choice activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'choice_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_choices',
                'description' => 'List Moodle Choice activities in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_choice',
                'description' => 'Register a Moodle Choice view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'choice_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'submit_choice_response',
                'description' => 'Submit the current user\'s response to a Moodle choice activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'choice_module_id' => ['type' => 'integer', 'required' => true],
                    'option_ids' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'delete_choice_responses',
                'description' => 'Delete current-user or permitted responses from a Moodle Choice activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'choice_module_id' => ['type' => 'integer', 'required' => true],
                    'response_ids' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_choice_results',
                'description' => 'Return aggregated results for a Moodle choice activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'choice_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_feedbacks',
                'description' => 'Return Feedback activities in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_feedback',
                'description' => 'Register a Moodle Feedback activity view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'module_viewed' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_feedback_access_information',
                'description' => 'Return Moodle Feedback access and status information.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_feedback_items',
                'description' => 'List items in a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_feedback_item',
                'description' => 'Create an item in a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'type' => ['type' => 'string', 'required' => true, 'enum' => [
                        'textfield',
                        'textarea',
                        'numeric',
                        'multichoice',
                        'multichoicerated',
                        'label',
                        'info',
                        'captcha',
                        'pagebreak',
                    ]],
                    'name' => ['type' => 'string', 'required' => false],
                    'definition' => ['type' => 'object', 'required' => true],
                    'position' => ['type' => 'integer', 'required' => false],
                    'label' => ['type' => 'string', 'required' => false],
                    'required' => ['type' => 'boolean', 'required' => false],
                    'depend_item_id' => ['type' => 'integer', 'required' => false],
                    'depend_value' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_feedback_item',
                'description' => 'Update an item in a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'item_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'definition' => ['type' => 'object', 'required' => false],
                    'position' => ['type' => 'integer', 'required' => false],
                    'label' => ['type' => 'string', 'required' => false],
                    'required' => ['type' => 'boolean', 'required' => false],
                    'depend_item_id' => ['type' => 'integer', 'required' => false],
                    'depend_value' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_feedback_page_items',
                'description' => 'List items on one page in a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'page' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_feedback_analysis',
                'description' => 'Return aggregated analysis for a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'group_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_feedback_finished_responses',
                'description' => 'Return current-user finished responses for a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'delete_feedback_item',
                'description' => 'Delete an item from a Moodle Feedback activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'item_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_forums',
                'description' => 'Return Forum activities in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_forum',
                'description' => 'Register a Moodle forum activity view.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_forum_discussions',
                'description' => 'Return discussions from a Moodle forum activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_forum_discussion',
                'description' => 'Create a discussion in a Moodle forum activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'message' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_forum_discussion_posts',
                'description' => 'Return posts from a Moodle forum discussion.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'create_forum_discussion_post',
                'description' => 'Create a reply post in a Moodle forum discussion.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'parent_post_id' => ['type' => 'integer', 'required' => false],
                    'subject' => ['type' => 'string', 'required' => true],
                    'message' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'update_forum_discussion_post',
                'description' => 'Update a Moodle forum discussion post.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'post_id' => ['type' => 'integer', 'required' => true],
                    'subject' => ['type' => 'string', 'required' => false],
                    'message' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'set_forum_discussion_pin',
                'description' => 'Pin or unpin a Moodle forum discussion.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'pinned' => ['type' => 'boolean', 'required' => true],
                ]),
            ],
            [
                'name' => 'set_forum_discussion_favourite',
                'description' => 'Favourite or unfavourite a Moodle forum discussion for the current user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'favourite' => ['type' => 'boolean', 'required' => true],
                ]),
            ],
            [
                'name' => 'set_forum_discussion_subscription',
                'description' => 'Subscribe or unsubscribe the current user from a Moodle forum discussion.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'subscribed' => ['type' => 'boolean', 'required' => true],
                ]),
            ],
            [
                'name' => 'set_forum_discussion_lock',
                'description' => 'Lock or unlock a Moodle forum discussion.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'locked' => ['type' => 'boolean', 'required' => true],
                ]),
            ],
            [
                'name' => 'delete_forum_discussion_post',
                'description' => 'Delete a Moodle forum discussion post. Deleting the first post deletes the discussion through Moodle\'s forum API.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'discussion_id' => ['type' => 'integer', 'required' => true],
                    'post_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_assignments',
                'description' => 'Return Moodle assignments in a course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_assignment_submission_status',
                'description' => 'Return the current assignment submission status.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'save_assignment_submission',
                'description' => 'Save an online text assignment submission.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'online_text' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'submit_assignment_for_grading',
                'description' => 'Submit the current assignment attempt for grading.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'accept_submission_statement' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'save_assignment_grade',
                'description' => 'Save a Moodle assignment grade and feedback comment.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'grade' => ['type' => 'number', 'required' => true],
                    'feedback_comment' => ['type' => 'string', 'required' => false],
                    'attempt_number' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_assignment_grading_form',
                'description' => 'Return the active advanced grading form for a Moodle assignment.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'set_assignment_rubric',
                'description' => 'Create or update a Moodle assignment rubric.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'criteria' => ['type' => 'object', 'required' => true],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'set_assignment_checklist',
                'description' => 'Create a Moodle assignment checklist as a binary rubric.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'items' => ['type' => 'object', 'required' => true],
                ]),
            ],
            [
                'name' => 'set_assignment_marking_guide',
                'description' => 'Create or update a Moodle assignment marking guide.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'criteria' => ['type' => 'object', 'required' => true],
                    'comments' => ['type' => 'object', 'required' => false],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'grade_assignment_with_rubric',
                'description' => 'Grade a Moodle assignment using its active rubric.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'criteria' => ['type' => 'object', 'required' => true],
                    'feedback_comment' => ['type' => 'string', 'required' => false],
                    'attempt_number' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'grade_assignment_with_checklist',
                'description' => 'Grade a Moodle assignment using a binary rubric checklist.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'items' => ['type' => 'object', 'required' => true],
                    'feedback_comment' => ['type' => 'string', 'required' => false],
                    'attempt_number' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'grade_assignment_with_marking_guide',
                'description' => 'Grade a Moodle assignment using its active marking guide.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'criteria' => ['type' => 'object', 'required' => true],
                    'feedback_comment' => ['type' => 'string', 'required' => false],
                    'attempt_number' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_assignment_submissions',
                'description' => 'Return submissions for a Moodle assignment.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'status' => ['type' => 'string', 'required' => false, 'enum' => ['new', 'draft', 'submitted', 'reopened']],
                    'since' => ['type' => 'integer', 'required' => false],
                    'before' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_assignment_grades',
                'description' => 'Return grades for a Moodle assignment.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'since' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_assignment',
                'description' => 'Register a Moodle assignment view for the current user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_assignment_submission_status',
                'description' => 'Register a Moodle assignment submission-status view for the current user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_assignment_grading_table',
                'description' => 'Register a Moodle assignment grading-table view for the current user.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'delete_module',
                'description' => 'Delete a Moodle course module.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'upload_folder_file',
                'description' => 'Upload a file to a Moodle folder activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'filename' => ['type' => 'string', 'required' => true],
                    'upload_reference' => ['type' => 'string', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_folder_files',
                'description' => 'Return files stored in a Moodle folder activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'download_folder_file',
                'description' => 'Download or retrieve metadata for a file in a Moodle folder activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'file_id' => ['type' => 'integer', 'required' => false],
                    'path' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_resource_files',
                'description' => 'Return files stored in a Moodle file resource.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'download_resource_file',
                'description' => 'Download or retrieve metadata for a file in a Moodle file resource.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'file_id' => ['type' => 'integer', 'required' => false],
                    'path' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_folder_file',
                'description' => 'Delete a file from a Moodle folder activity.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'module_id' => ['type' => 'integer', 'required' => true],
                    'file_id' => ['type' => 'integer', 'required' => false],
                    'path' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_question_banks',
                'description' => 'Return question banks visible in a Moodle course.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'include_quiz_private' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_question_categories',
                'description' => 'Return question categories from a selected Moodle question bank.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'question_bank_module_id' => ['type' => 'integer', 'required' => false],
                    'quiz_module_id' => ['type' => 'integer', 'required' => false],
                    'include_top' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'export_question_bank_blueprint',
                'description' => 'Export a portable MoodlIA JSON blueprint from a Moodle question bank.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'question_bank_module_id' => ['type' => 'integer', 'required' => false],
                    'quiz_module_id' => ['type' => 'integer', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'include_unsupported' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'import_question_bank_blueprint',
                'description' => 'Import a portable MoodlIA JSON blueprint into a Moodle question bank.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'blueprint_json' => ['type' => 'string', 'required' => true],
                    'bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'question_bank_module_id' => ['type' => 'integer', 'required' => false],
                    'quiz_module_id' => ['type' => 'integer', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => false],
                    'create_categories' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'create_question_category',
                'description' => 'Create a Moodle question category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'parent_id' => ['type' => 'integer', 'required' => false],
                    'description' => ['type' => 'string', 'required' => false],
                    'bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'question_bank_module_id' => ['type' => 'integer', 'required' => false],
                    'quiz_module_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_question_category',
                  'description' => 'Update a Moodle question category.',
                  'inputSchema' => self::schema([
                      'category_id' => ['type' => 'integer', 'required' => true],
                      'context_id' => ['type' => 'integer', 'required' => true],
                      'name' => ['type' => 'string', 'required' => false],
                      'description' => ['type' => 'string', 'required' => false],
                  ]),
            ],
            [
                'name' => 'delete_question_category',
                  'description' => 'Delete a controlled Moodle question category.',
                  'inputSchema' => self::schema([
                      'category_id' => ['type' => 'integer', 'required' => true],
                      'context_id' => ['type' => 'integer', 'required' => true],
                      'delete_mode' => ['type' => 'string', 'required' => false, 'enum' => ['delete', 'merge']],
                  ]),
            ],
            [
                'name' => 'create_question',
                  'description' => 'Create a Moodle question in a question category.',
                  'inputSchema' => self::schema([
                      'category_id' => ['type' => 'integer', 'required' => true],
                      'context_id' => ['type' => 'integer', 'required' => true],
                      'question_type' => [
                        'type' => 'string',
                        'required' => true,
                        'enum' => [
                            'truefalse',
                            'shortanswer',
                            'multichoice',
                            'numerical',
                            'essay',
                            'matching',
                            'description',
                            'randomsamatch',
                            'gapselect',
                            'ddwtos',
                            'ordering',
                            'multianswer',
                            'ddmarker',
                            'ddimageortext',
                            'calculatedsimple',
                            'calculated',
                            'calculatedmulti',
                        ],
                      ],
                      'name' => ['type' => 'string', 'required' => true],
                      'question_text' => ['type' => 'string', 'required' => true],
                      'options' => ['type' => 'object', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_questions',
                'description' => 'Return ready Moodle questions in a question category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => true],
                    'bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'question_bank_module_id' => ['type' => 'integer', 'required' => false],
                    'quiz_module_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_question',
                'description' => 'Update a Moodle question.',
                'inputSchema' => self::schema([
                    'question_id' => ['type' => 'integer', 'required' => true],
                    'name' => ['type' => 'string', 'required' => false],
                    'question_text' => ['type' => 'string', 'required' => false],
                    'options' => ['type' => 'object', 'required' => false],
                ]),
            ],
            [
                'name' => 'move_question',
                'description' => 'Move a Moodle question to another question bank category.',
                'inputSchema' => self::schema([
                    'course_id' => ['type' => 'integer', 'required' => true],
                    'question_id' => ['type' => 'integer', 'required' => true],
                    'target_category_id' => ['type' => 'integer', 'required' => true],
                    'target_bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'target_question_bank_module_id' => ['type' => 'integer', 'required' => false],
                    'target_quiz_module_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'delete_question',
                'description' => 'Delete or hide a Moodle question.',
                'inputSchema' => self::schema([
                    'question_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_quiz_questions',
                'description' => 'Return questions currently used by a Moodle quiz.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_course_quizzes',
                'description' => 'Return Moodle quizzes in selected courses.',
                'inputSchema' => self::schema([
                    'course_ids' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'start_quiz_attempt',
                'description' => 'Start a Moodle quiz attempt or preview for the current user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'force_new' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_attempts',
                'description' => 'Return Moodle quiz attempts for a user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                    'status' => ['type' => 'string', 'required' => false, 'enum' => ['all', 'finished', 'unfinished']],
                    'include_previews' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_results_report',
                'description' => 'Return a compact attempt and grade report for enrolled users in a Moodle quiz.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'limit' => ['type' => 'integer', 'required' => false],
                    'include_previews' => ['type' => 'boolean', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_attempt_access_information',
                'description' => 'Return Moodle access information for a quiz attempt.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_attempt_data',
                'description' => 'Return rendered Moodle question data for a quiz attempt page.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'page' => ['type' => 'integer', 'required' => false],
                    'preflight_data' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_attempt_summary',
                'description' => 'Return pre-submit summary data for a Moodle quiz attempt.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'preflight_data' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'save_quiz_attempt',
                'description' => 'Save current Moodle quiz attempt responses without finishing the attempt.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'data' => ['type' => 'string', 'required' => false],
                    'preflight_data' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'process_quiz_attempt',
                'description' => 'Process Moodle quiz attempt responses and optionally finish the attempt.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'data' => ['type' => 'string', 'required' => false],
                    'finish_attempt' => ['type' => 'boolean', 'required' => false],
                    'time_up' => ['type' => 'boolean', 'required' => false],
                    'preflight_data' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_attempt_review',
                'description' => 'Return review data for a finished Moodle quiz attempt.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'page' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_access_information',
                'description' => 'Return Moodle access information for a quiz.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_quiz_combined_review_options',
                'description' => 'Return combined Moodle quiz review option visibility.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_quiz',
                'description' => 'Register a Moodle quiz view for the current user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'view_quiz_attempt',
                'description' => 'Register a Moodle quiz attempt page view for the current user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'page' => ['type' => 'integer', 'required' => false],
                    'preflight_data' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_quiz_attempt_summary',
                'description' => 'Register a Moodle quiz attempt summary view for the current user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                    'preflight_data' => ['type' => 'string', 'required' => false],
                ]),
            ],
            [
                'name' => 'view_quiz_attempt_review',
                'description' => 'Register a Moodle quiz attempt review view for the current user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'attempt_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_quiz_user_best_grade',
                'description' => 'Return the best current grade for a Moodle quiz user.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'user_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'get_quiz_feedback_for_grade',
                'description' => 'Return the Moodle quiz feedback text for a grade value.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'grade' => ['type' => 'number', 'required' => true],
                ]),
            ],
            [
                'name' => 'get_quiz_required_question_types',
                'description' => 'Return Moodle question types used by a quiz.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                ]),
            ],
            [
                'name' => 'add_question_to_quiz',
                'description' => 'Add a Moodle question to a quiz module.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'question_id' => ['type' => 'integer', 'required' => true],
                    'slot' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'add_random_questions_to_quiz',
                'description' => 'Add random Moodle question slots from a question category to a quiz module.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'category_id' => ['type' => 'integer', 'required' => true],
                    'number' => ['type' => 'integer', 'required' => true],
                    'slot' => ['type' => 'integer', 'required' => false],
                    'include_subcategories' => ['type' => 'boolean', 'required' => false],
                    'bank_scope' => ['type' => 'string', 'required' => false, 'enum' => ['course_shared', 'quiz_private']],
                    'question_bank_module_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'remove_question_from_quiz',
                'description' => 'Remove a Moodle question slot from a quiz module.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'slot' => ['type' => 'integer', 'required' => false],
                    'question_id' => ['type' => 'integer', 'required' => false],
                ]),
            ],
            [
                'name' => 'update_quiz_question_slot',
                'description' => 'Update the maximum mark for a Moodle quiz question slot.',
                'inputSchema' => self::schema([
                    'quiz_module_id' => ['type' => 'integer', 'required' => true],
                    'slot' => ['type' => 'integer', 'required' => true],
                    'max_mark' => ['type' => 'number', 'required' => true],
                ]),
            ],
        ];
    }

    /**
     * Return the known tool names.
     *
     * @return array
     */
    public static function tool_names(): array {
        return array_map(static fn(array $tool): string => $tool['name'], self::tools());
    }

    /**
     * Build a JSON schema object for MCP tool arguments.
     *
     * @param array $parameters Parameters.
     * @return array
     */
    private static function schema(array $parameters): array {
        $properties = [];
        $required = [];

        foreach ($parameters as $name => $definition) {
            $properties[$name] = [
                'type' => $definition['type'],
            ];

            if (!empty($definition['enum'])) {
                $properties[$name]['enum'] = $definition['enum'];
            }

            if (!empty($definition['required'])) {
                $required[] = $name;
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];
    }
}
