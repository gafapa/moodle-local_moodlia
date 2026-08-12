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
 * Move module external function.
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
use local_moodlia\operation\move_module as move_module_operation;

/**
 * External API adapter for move_module.
 */
class move_module extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Course module id'),
            'section_number' => new external_value(PARAM_INT, 'Target course section number'),
            'before_module_id' => new external_value(PARAM_INT, 'Target sibling course module id', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Course module id.
     * @param int $sectionnumber Target course section number.
     * @param int|null $beforemoduleid Target sibling course module id.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $sectionnumber,
        ?int $beforemoduleid = null
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'section_number' => $sectionnumber,
            'before_module_id' => $beforemoduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'section_number' => $sectionnumber,
            'before_module_id' => $beforemoduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:manageactivities', $coursecontext);

        return move_module_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $sectionnumber,
            $beforemoduleid === null ? null : (int) $beforemoduleid
        );
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
        ]);
    }
}
