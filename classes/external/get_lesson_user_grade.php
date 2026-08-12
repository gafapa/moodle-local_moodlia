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
 * Get Lesson user grade external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_lesson_user_grade as get_lesson_user_grade_operation;
use local_moodlia\operation\lesson_tools;

/**
 * External API adapter for get_lesson_user_grade.
 */
class get_lesson_user_grade extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id. Zero means current user.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Lesson course module id.
     * @param int $userid Moodle user id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $userid = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/lesson:view', $modulecontext);

        return get_lesson_user_grade_operation::execute((int) $courseid, (int) $moduleid, (int) $userid);
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
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 when current user was used'),
            'has_grade' => new external_value(PARAM_BOOL, 'Whether Moodle returned a final grade'),
            'grade' => new external_value(PARAM_FLOAT, 'Lesson final raw grade, or 0 when no grade exists'),
            'formatted_grade' => new external_value(PARAM_RAW, 'Formatted lesson grade'),
            'warnings' => get_lesson_access_information::warnings_structure(),
        ]);
    }
}
