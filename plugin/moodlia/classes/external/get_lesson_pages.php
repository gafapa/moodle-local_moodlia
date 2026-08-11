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
 * Get Lesson pages external function.
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
use local_moodlia\operation\get_lesson_pages as get_lesson_pages_operation;
use local_moodlia\operation\lesson_tools;

/**
 * External API adapter for get_lesson_pages.
 */
class get_lesson_pages extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'password' => new external_value(PARAM_RAW, 'Optional lesson password', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Lesson course module id.
     * @param string $password Optional lesson password.
     * @return array
     */
    public static function execute(int $course_id, int $module_id, string $password = ''): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'password' => $password,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'password' => $password,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/lesson:view', $modulecontext);

        return get_lesson_pages_operation::execute((int) $courseid, (int) $moduleid, (string) $password);
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
            'count' => new external_value(PARAM_INT, 'Returned page count'),
            'pages' => new external_multiple_structure(new external_single_structure([
                'page_id' => new external_value(PARAM_INT, 'Lesson page id'),
                'lesson_id' => new external_value(PARAM_INT, 'Lesson instance id'),
                'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
                'previous_page_id' => new external_value(PARAM_INT, 'Previous page id or 0'),
                'next_page_id' => new external_value(PARAM_INT, 'Next page id or 0'),
                'question_type' => new external_value(PARAM_INT, 'Lesson question type'),
                'question_option' => new external_value(PARAM_INT, 'Lesson question option'),
                'layout' => new external_value(PARAM_INT, 'Lesson page layout'),
                'display' => new external_value(PARAM_INT, 'Lesson page display setting'),
                'display_in_menu_block' => new external_value(PARAM_BOOL, 'Whether the page is displayed in the menu block'),
                'type' => new external_value(PARAM_INT, 'Lesson page type'),
                'type_id' => new external_value(PARAM_INT, 'Lesson page type id'),
                'type_string' => new external_value(PARAM_RAW, 'Lesson page type label'),
                'title' => new external_value(PARAM_RAW, 'Lesson page title'),
                'content' => new external_value(PARAM_RAW, 'Lesson page content'),
                'content_format' => new external_value(PARAM_INT, 'Moodle content format'),
                'time_created' => new external_value(PARAM_INT, 'Creation timestamp'),
                'time_modified' => new external_value(PARAM_INT, 'Modification timestamp'),
                'answer_ids' => new external_multiple_structure(new external_value(PARAM_INT, 'Answer id')),
                'jumps' => new external_multiple_structure(new external_value(PARAM_INT, 'Jump page id')),
                'files_count' => new external_value(PARAM_INT, 'Attached file count'),
                'files_size_total' => new external_value(PARAM_INT, 'Attached file size total'),
            ])),
            'warnings' => get_lesson_access_information::warnings_structure(),
        ]);
    }
}
