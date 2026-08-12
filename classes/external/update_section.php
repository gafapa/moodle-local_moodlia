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
 * Update section external function.
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
use local_moodlia\operation\update_section as update_section_operation;

/**
 * External API adapter for update_section.
 */
class update_section extends external_api {
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
            'name' => new external_value(PARAM_TEXT, 'Section name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'summary' => new external_value(PARAM_TEXT, 'Section summary', VALUE_DEFAULT, null, NULL_ALLOWED),
            'visible' => new external_value(PARAM_BOOL, 'Whether the section is visible', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int|null $sectionid Sectionid.
     * @param int|null $sectionnumber Sectionnumber.
     * @param string|null $name Name.
     * @param string|null $summary Summary.
     * @param bool|null $visible Visible.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?int $sectionid = null,
        ?int $sectionnumber = null,
        ?string $name = null,
        ?string $summary = null,
        ?bool $visible = null
    ): array {
        [
            'course_id' => $courseid,
            'section_id' => $sectionid,
            'section_number' => $sectionnumber,
            'name' => $name,
            'summary' => $summary,
            'visible' => $sectionvisible,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'section_id' => $sectionid,
            'section_number' => $sectionnumber,
            'name' => $name,
            'summary' => $summary,
            'visible' => $visible,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        return update_section_operation::execute(
            (int) $courseid,
            $sectionid === null ? null : (int) $sectionid,
            $sectionnumber === null ? null : (int) $sectionnumber,
            $name,
            $summary,
            $sectionvisible === null ? null : (bool) $sectionvisible
        );
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
