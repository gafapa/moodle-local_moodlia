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
 * Course completion status external function.
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
use local_moodlia\operation\get_course_completion_status as get_course_completion_status_operation;

/**
 * External API adapter for get_course_completion_status.
 */
class get_course_completion_status extends external_api {
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

        return get_course_completion_status_operation::execute((int) $courseid, (int) $userid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 if current user was requested'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the course is completed'),
            'aggregation' => new external_value(PARAM_INT, 'Moodle completion aggregation mode'),
            'criteria_count' => new external_value(PARAM_INT, 'Completion criteria count'),
            'completed_criteria_count' => new external_value(PARAM_INT, 'Completed criteria count'),
            'status_json' => new external_value(PARAM_RAW, 'Raw Moodle course completion status as JSON'),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Warnings structure.
     *
     * @return external_multiple_structure
     */
    public static function warnings_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'item' => new external_value(PARAM_TEXT, 'Warning item'),
            'item_id' => new external_value(PARAM_INT, 'Warning item id'),
            'warning_code' => new external_value(PARAM_ALPHANUMEXT, 'Warning code'),
            'message' => new external_value(PARAM_TEXT, 'Warning message'),
        ]));
    }
}
