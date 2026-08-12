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
 * Get Lesson attempts overview external function.
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
use local_moodlia\operation\get_lesson_attempts_overview as get_lesson_attempts_overview_operation;
use local_moodlia\operation\lesson_tools;

/**
 * External API adapter for get_lesson_attempts_overview.
 */
class get_lesson_attempts_overview extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id. Zero means Moodle default group resolution.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Lesson course module id.
     * @param int $group_id Moodle group id.
     * @return array
     */
    public static function execute(int $course_id, int $module_id, int $group_id = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'group_id' => $group_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/lesson:viewreports', $modulecontext);

        return get_lesson_attempts_overview_operation::execute((int) $courseid, (int) $moduleid, (int) $groupid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'lesson_id' => new external_value(PARAM_INT, 'Lesson instance id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id'),
            'lesson_scored' => new external_value(PARAM_BOOL, 'Whether the lesson is scored'),
            'attempts_count' => new external_value(PARAM_INT, 'Number of attempts'),
            'average_score' => new external_value(PARAM_FLOAT, 'Average score'),
            'high_score' => new external_value(PARAM_FLOAT, 'High score'),
            'low_score' => new external_value(PARAM_FLOAT, 'Low score'),
            'average_time' => new external_value(PARAM_INT, 'Average time spent'),
            'high_time' => new external_value(PARAM_INT, 'High time spent'),
            'low_time' => new external_value(PARAM_INT, 'Low time spent'),
            'students' => new external_multiple_structure(new external_single_structure([
                'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
                'full_name' => new external_value(PARAM_TEXT, 'User full name'),
                'best_grade' => new external_value(PARAM_FLOAT, 'Best grade'),
                'attempts' => new external_multiple_structure(new external_single_structure([
                    'attempt_number' => new external_value(PARAM_INT, 'Attempt number'),
                    'grade' => new external_value(PARAM_FLOAT, 'Attempt grade'),
                    'time_start' => new external_value(PARAM_INT, 'Attempt start timestamp'),
                    'time_end' => new external_value(PARAM_INT, 'Attempt last continued timestamp'),
                    'end_time' => new external_value(PARAM_INT, 'Attempt end timestamp'),
                ])),
            ])),
            'warnings' => get_lesson_access_information::warnings_structure(),
        ]);
    }
}
