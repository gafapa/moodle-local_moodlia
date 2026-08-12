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
 * Course progress report external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_course_progress_report as get_course_progress_report_operation;

/**
 * External API adapter for get_course_progress_report.
 */
class get_course_progress_report extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'limit' => new external_value(PARAM_INT, 'Maximum users to include', VALUE_DEFAULT, 100),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $limit Maximum users to include.
     * @return array
     */
    public static function execute(int $course_id, int $limit = 100): array {
        [
            'course_id' => $courseid,
            'limit' => $userlimit,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'limit' => $limit,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance((int) $courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:viewparticipants', $coursecontext);
        require_capability('moodle/grade:viewall', $coursecontext);

        return get_course_progress_report_operation::execute((int) $courseid, (int) $userlimit);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'requested_limit' => new external_value(PARAM_INT, 'Requested maximum users'),
            'returned_user_count' => new external_value(PARAM_INT, 'Returned user count'),
            'total_enrolled_user_count' => new external_value(PARAM_INT, 'Total enrolled users visible to the caller'),
            'grade_item_count' => new external_value(PARAM_INT, 'Course grade item count'),
            'tracked_activity_count' => new external_value(PARAM_INT, 'Maximum tracked activity count across returned users'),
            'completed_user_count' => new external_value(PARAM_INT, 'Returned users with completed course status'),
            'average_grade_percentage' => new external_value(PARAM_FLOAT, 'Average grade percentage across returned users with gradable items'),
            'users' => new external_multiple_structure(new external_single_structure([
                'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
                'username' => new external_value(PARAM_USERNAME, 'Moodle username'),
                'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                'roles' => new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'Role shortname')),
                'course_completed' => new external_value(PARAM_BOOL, 'Whether the course is completed'),
                'course_criteria_count' => new external_value(PARAM_INT, 'Course completion criteria count'),
                'completed_course_criteria_count' => new external_value(PARAM_INT, 'Completed course criteria count'),
                'tracked_activity_count' => new external_value(PARAM_INT, 'Tracked activity count'),
                'completed_activity_count' => new external_value(PARAM_INT, 'Completed tracked activity count'),
                'grade_item_count' => new external_value(PARAM_INT, 'User grade item count'),
                'graded_item_count' => new external_value(PARAM_INT, 'Gradable item count used for percentage calculation'),
                'grade_points' => new external_value(PARAM_FLOAT, 'Summed raw grade points'),
                'grade_max' => new external_value(PARAM_FLOAT, 'Summed maximum grade points'),
                'grade_percentage' => new external_value(PARAM_FLOAT, 'Grade percentage'),
                'grade_percentage_formatted' => new external_value(PARAM_NOTAGS, 'Formatted grade percentage'),
                'warnings_count' => new external_value(PARAM_INT, 'Warnings attached to this user row'),
            ])),
            'warnings' => new external_multiple_structure(new external_single_structure([
                'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
                'warning_code' => new external_value(PARAM_ALPHANUMEXT, 'Warning code'),
                'message' => new external_value(PARAM_TEXT, 'Warning message'),
            ])),
        ]);
    }
}
