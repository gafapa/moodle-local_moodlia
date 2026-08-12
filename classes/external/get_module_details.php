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
 * Get module details external function.
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
use local_moodlia\operation\get_module_details as get_module_details_operation;
use local_moodlia\operation\module_tools;

/**
 * External API adapter for get_module_details.
 */
class get_module_details extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Moodle course module id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Moodle course module id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $course = get_course($courseid);
        $cm = module_tools::get_course_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);

        return get_module_details_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'MoodlIA module id alias'),
            'course_module_id' => new external_value(PARAM_INT, 'Moodle course module id'),
            'instance_id' => new external_value(PARAM_INT, 'Module instance id'),
            'name' => new external_value(PARAM_TEXT, 'Module name'),
            'module_type' => new external_value(PARAM_PLUGIN, 'Module type'),
            'visible' => new external_value(PARAM_BOOL, 'Module visibility'),
            'visible_on_course_page' => new external_value(PARAM_BOOL, 'Module course-page visibility'),
            'user_visible' => new external_value(PARAM_BOOL, 'Current user visibility'),
            'id_number' => new external_value(PARAM_TEXT, 'Module ID number'),
            'language' => new external_value(PARAM_LANG, 'Forced module language'),
            'group_mode' => new external_value(PARAM_INT, 'Module group mode'),
            'grouping_id' => new external_value(PARAM_INT, 'Module grouping id'),
            'availability' => new external_value(PARAM_RAW, 'Module availability restrictions JSON'),
            'download_content' => new external_value(PARAM_BOOL, 'Whether Moodle can include this module in course downloads'),
            'url' => new external_value(PARAM_URL, 'Module URL'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'context_id' => new external_value(PARAM_INT, 'Module context id'),
            'section_id' => new external_value(PARAM_INT, 'Course section id'),
            'section_number' => new external_value(PARAM_INT, 'Course section number'),
            'section_name' => new external_value(PARAM_TEXT, 'Course section name'),
            'description' => new external_value(PARAM_RAW, 'Rendered module description'),
            'show_description' => new external_value(PARAM_BOOL, 'Whether the module description is shown on the course page'),
            'completion' => new external_value(PARAM_INT, 'Completion mode'),
            'completion_view' => new external_value(PARAM_INT, 'Completion requires view flag'),
            'completion_grade_item_number' => new external_value(PARAM_INT, 'Completion grade item number or -1'),
            'completion_expected' => new external_value(PARAM_INT, 'Expected completion Unix timestamp, or 0'),
            'added' => new external_value(PARAM_INT, 'Module creation timestamp where available'),
            'deletion_in_progress' => new external_value(PARAM_BOOL, 'Whether Moodle is deleting this module'),
            'extra_json' => new external_value(PARAM_RAW, 'JSON encoded module metadata exposed by Moodle cm_info'),
        ]);
    }
}
