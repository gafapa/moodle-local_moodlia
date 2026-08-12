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
 * User grades external function.
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
use local_moodlia\operation\get_user_grades as get_user_grades_operation;

/**
 * External API adapter for get_user_grades.
 */
class get_user_grades extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 for current user', VALUE_DEFAULT, 0),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id, or 0 for all allowed groups', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $user_id Moodle user id, or 0 for current user.
     * @param int $group_id Moodle group id, or 0 for all allowed groups.
     * @return array
     */
    public static function execute(int $course_id, int $user_id = 0, int $group_id = 0): array {
        [
            'course_id' => $courseid,
            'user_id' => $userid,
            'group_id' => $groupid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'user_id' => $user_id,
            'group_id' => $group_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:viewall', $coursecontext);

        return get_user_grades_operation::execute((int) $courseid, (int) $userid, (int) $groupid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'user_fullname' => new external_value(PARAM_TEXT, 'User full name'),
            'items' => new external_multiple_structure(new external_single_structure([
                'item_id' => new external_value(PARAM_INT, 'Grade item id'),
                'name' => new external_value(PARAM_RAW, 'Grade item name'),
                'item_type' => new external_value(PARAM_ALPHA, 'Grade item type'),
                'item_module' => new external_value(PARAM_PLUGIN, 'Grade item module'),
                'item_instance' => new external_value(PARAM_INT, 'Grade item instance'),
                'course_module_id' => new external_value(PARAM_INT, 'Course module id'),
                'grade_raw' => new external_value(PARAM_FLOAT, 'Raw grade value'),
                'grade_formatted' => new external_value(PARAM_RAW, 'Formatted grade value'),
                'grade_min' => new external_value(PARAM_FLOAT, 'Minimum grade'),
                'grade_max' => new external_value(PARAM_FLOAT, 'Maximum grade'),
                'range_formatted' => new external_value(PARAM_NOTAGS, 'Formatted grade range'),
                'percentage_formatted' => new external_value(PARAM_NOTAGS, 'Formatted grade percentage'),
                'feedback' => new external_value(PARAM_RAW, 'Grade feedback'),
                'hidden' => new external_value(PARAM_BOOL, 'Whether the grade is hidden'),
                'locked' => new external_value(PARAM_BOOL, 'Whether the grade is locked'),
            ])),
        ]);
    }
}
