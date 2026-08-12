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
 * Activity completion statuses external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_activity_completion_statuses as get_activity_completion_statuses_operation;

/**
 * External API adapter for get_activity_completion_statuses.
 */
class get_activity_completion_statuses extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 for current user', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @return array
     */
    public static function execute(int $courseid, int $userid = 0): array {
        [
            'course_id' => $courseid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'user_id' => $userid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course((int) $courseid);
        $coursecontext = \context_course::instance((int) $course->id);
        self::validate_context($coursecontext);
        require_login($course);

        return get_activity_completion_statuses_operation::execute((int) $courseid, (int) $userid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Requested Moodle user id, or 0 for current user'),
            'statuses' => new external_multiple_structure(new external_single_structure([
                'module_id' => new external_value(PARAM_INT, 'Course module id'),
                'module_type' => new external_value(PARAM_PLUGIN, 'Moodle module type'),
                'instance_id' => new external_value(PARAM_INT, 'Activity instance id'),
                'state' => new external_value(PARAM_INT, 'Completion state'),
                'time_completed' => new external_value(PARAM_INT, 'Completion timestamp'),
                'tracking' => new external_value(PARAM_INT, 'Completion tracking mode'),
                'override_by' => new external_value(PARAM_INT, 'User id that overrode completion'),
                'value_used' => new external_value(PARAM_BOOL, 'Whether this value is used'),
                'has_completion' => new external_value(PARAM_BOOL, 'Whether completion is enabled for the activity'),
                'is_automatic' => new external_value(PARAM_BOOL, 'Whether completion is automatic'),
                'is_tracked_user' => new external_value(PARAM_BOOL, 'Whether the requested user is tracked'),
                'user_visible' => new external_value(PARAM_BOOL, 'Whether the activity is visible to the user'),
                'details_json' => new external_value(PARAM_RAW, 'Raw Moodle completion details as JSON'),
            ])),
            'warnings' => get_course_completion_status::warnings_structure(),
        ]);
    }
}
