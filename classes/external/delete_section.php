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
 * Delete section external function.
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
use local_moodlia\operation\delete_section as delete_section_operation;

/**
 * External API adapter for delete_section.
 */
class delete_section extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'section_id' => new external_value(PARAM_INT, 'Course section id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'section_number' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, null, NULL_ALLOWED),
            'delete_mode' => new external_value(PARAM_ALPHA, 'Delete mode', VALUE_DEFAULT, 'delete'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int|null $sectionid Sectionid.
     * @param int|null $sectionnumber Sectionnumber.
     * @param string $deletemode Deletemode.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?int $sectionid = null,
        ?int $sectionnumber = null,
        string $deletemode = 'delete'
    ): array {
        [
            'course_id' => $courseid,
            'section_id' => $sectionid,
            'section_number' => $sectionnumber,
            'delete_mode' => $deletemode,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'section_id' => $sectionid,
            'section_number' => $sectionnumber,
            'delete_mode' => $deletemode,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        return delete_section_operation::execute(
            (int) $courseid,
            $sectionid === null ? null : (int) $sectionid,
            $sectionnumber === null ? null : (int) $sectionnumber,
            $deletemode
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Whether the section was deleted'),
            'id' => new external_value(PARAM_INT, 'Deleted course section id'),
        ]);
    }
}
