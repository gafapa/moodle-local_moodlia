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
 * Update grouping external function.
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
use local_moodlia\operation\update_grouping as update_grouping_operation;

/**
 * External API adapter for update_grouping.
 */
class update_grouping extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'grouping_id' => new external_value(PARAM_INT, 'Moodle grouping id'),
            'name' => new external_value(PARAM_TEXT, 'Grouping name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'description' => new external_value(PARAM_RAW, 'Grouping description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'idnumber' => new external_value(PARAM_RAW, 'Grouping idnumber', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $groupingid Grouping id.
     * @param string|null $name Name.
     * @param string|null $description Description.
     * @param string|null $idnumber Idnumber.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $groupingid,
        ?string $name = null,
        ?string $description = null,
        ?string $idnumber = null
    ): array {
        [
            'course_id' => $courseid,
            'grouping_id' => $groupingid,
            'name' => $name,
            'description' => $description,
            'idnumber' => $idnumber,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'grouping_id' => $groupingid,
            'name' => $name,
            'description' => $description,
            'idnumber' => $idnumber,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:managegroups', $coursecontext);

        return update_grouping_operation::execute((int) $courseid, (int) $groupingid, $name, $description, $idnumber);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_groupings::grouping_structure();
    }
}
