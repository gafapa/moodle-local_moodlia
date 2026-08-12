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
 * Create section external function.
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
use local_moodlia\operation\create_section as create_section_operation;

/**
 * External API adapter for create_section.
 */
class create_section extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Section name'),
            'summary' => new external_value(PARAM_TEXT, 'Section summary', VALUE_DEFAULT, ''),
            'position' => new external_value(PARAM_INT, 'Placement position, or 0 to append', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_BOOL, 'Whether the section is visible', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param string $summary Summary.
     * @param int $position Position.
     * @param bool $visible Visible.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        string $summary = '',
        int $position = 0,
        bool $visible = true
    ): array {
        [
            'course_id' => $courseid,
            'name' => $name,
            'summary' => $summary,
            'position' => $position,
            'visible' => $sectionvisible,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'name' => $name,
            'summary' => $summary,
            'position' => $position,
            'visible' => $visible,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        return create_section_operation::execute((int) $courseid, $name, $summary, (int) $position, (bool) $sectionvisible);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'section_id' => new external_value(PARAM_INT, 'Moodle course section id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'section_number' => new external_value(PARAM_INT, 'Course section number'),
            'name' => new external_value(PARAM_TEXT, 'Resolved section name'),
            'summary' => new external_value(PARAM_RAW, 'Rendered section summary'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the section is visible'),
        ]);
    }
}
