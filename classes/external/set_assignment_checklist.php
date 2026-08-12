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
 * Set assignment checklist external function.
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
use local_moodlia\operation\set_assignment_checklist as set_assignment_checklist_operation;

/**
 * External API adapter for set_assignment_checklist.
 */
class set_assignment_checklist extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'name' => new external_value(PARAM_TEXT, 'Checklist definition name'),
            'description' => new external_value(PARAM_RAW, 'Checklist description HTML', VALUE_DEFAULT, ''),
            'items' => new external_value(PARAM_RAW, 'JSON object with items array', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $items Items.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $name,
        string $description = '',
        string $items = '{}'
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'name' => $name,
            'description' => $description,
            'items' => $items,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'name' => $name,
            'description' => $description,
            'items' => $items,
        ]);

        get_assignment_grading_form::require_assignment_context((int) $courseid, (int) $moduleid, true);
        return set_assignment_checklist_operation::execute((int) $courseid, (int) $moduleid, $name, $description, $items);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_assignment_grading_form::grading_form_structure();
    }
}
