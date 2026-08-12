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
 * Course contents external function.
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
use local_moodlia\operation\get_course_contents as get_course_contents_operation;

/**
 * External API adapter for get_course_contents.
 */
class get_course_contents extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @return array
     */
    public static function execute(int $courseid): array {
        ['course_id' => $courseid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_course_contents_operation::execute((int) $courseid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'sections' => new external_multiple_structure(new external_single_structure([
                'section_id' => new external_value(PARAM_INT, 'Course section id'),
                'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
                'section_number' => new external_value(PARAM_INT, 'Course section number'),
                'name' => new external_value(PARAM_TEXT, 'Course section name'),
                'summary' => new external_value(PARAM_RAW, 'Rendered course section summary'),
                'visible' => new external_value(PARAM_BOOL, 'Whether the section is visible'),
                'modules' => new external_multiple_structure(new external_single_structure([
                    'module_id' => new external_value(PARAM_INT, 'MoodlIA module id alias'),
                    'course_module_id' => new external_value(PARAM_INT, 'Moodle course module id'),
                    'instance_id' => new external_value(PARAM_INT, 'Module instance id'),
                    'name' => new external_value(PARAM_TEXT, 'Module name'),
                    'module_type' => new external_value(PARAM_PLUGIN, 'Module type'),
                    'visible' => new external_value(PARAM_BOOL, 'Whether the module is visible'),
                    'visible_on_course_page' => new external_value(PARAM_BOOL, 'Whether the module is shown on the course page'),
                    'user_visible' => new external_value(PARAM_BOOL, 'Whether the module is visible to the current user'),
                    'completion' => new external_value(PARAM_INT, 'Completion tracking mode'),
                    'completion_view' => new external_value(PARAM_INT, 'Whether viewing is required for completion'),
                    'completion_grade_item_number' => new external_value(PARAM_INT, 'Required completion grade item number, or -1'),
                    'completion_expected' => new external_value(PARAM_INT, 'Expected completion Unix timestamp, or 0'),
                    'url' => new external_value(PARAM_RAW, 'Module URL'),
                ])),
            ])),
        ]);
    }
}
