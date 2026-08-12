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
 * Get Lesson access information external function.
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
use local_moodlia\operation\get_lesson_access_information as get_lesson_access_information_operation;
use local_moodlia\operation\lesson_tools;

/**
 * External API adapter for get_lesson_access_information.
 */
class get_lesson_access_information extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Lesson course module id.
     * @return array
     */
    public static function execute(int $course_id, int $module_id): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/lesson:view', $modulecontext);

        return get_lesson_access_information_operation::execute((int) $courseid, (int) $moduleid);
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
            'can_manage' => new external_value(PARAM_BOOL, 'Whether the current user can manage the lesson'),
            'can_grade' => new external_value(PARAM_BOOL, 'Whether the current user can grade the lesson'),
            'can_view_reports' => new external_value(PARAM_BOOL, 'Whether the current user can view lesson reports'),
            'review_mode' => new external_value(PARAM_BOOL, 'Whether the lesson is in review mode for the current user'),
            'attempts_count' => new external_value(PARAM_INT, 'Current user attempt count'),
            'last_page_seen' => new external_value(PARAM_INT, 'Last page seen id or 0'),
            'left_during_timed_session' => new external_value(PARAM_BOOL, 'Whether the user left during a timed session'),
            'first_page_id' => new external_value(PARAM_INT, 'First lesson page id or 0'),
            'prevent_access_reasons' => new external_multiple_structure(new external_single_structure([
                'reason' => new external_value(PARAM_TEXT, 'Reason code'),
                'data' => new external_value(PARAM_RAW, 'Reason data'),
                'message' => new external_value(PARAM_RAW, 'Rendered reason message'),
            ])),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Shared warning structure.
     *
     * @return external_multiple_structure
     */
    public static function warnings_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'item' => new external_value(PARAM_TEXT, 'Warning item'),
            'item_id' => new external_value(PARAM_INT, 'Warning item id'),
            'warning_code' => new external_value(PARAM_TEXT, 'Warning code'),
            'message' => new external_value(PARAM_TEXT, 'Warning message'),
        ]));
    }
}
