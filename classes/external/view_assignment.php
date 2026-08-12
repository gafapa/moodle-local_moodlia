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
 * View assignment external function.
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
use local_moodlia\operation\view_assignment as view_assignment_operation;

/**
 * External API adapter for view_assignment.
 */
class view_assignment extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $moduleid Module id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        ['course_id' => $courseid, 'module_id' => $moduleid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ]);

        get_assignment_submissions::require_assignment_context((int) $courseid, (int) $moduleid, 'mod/assign:view');

        return view_assignment_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return self::view_structure();
    }

    /**
     * View structure.
     *
     * @return external_single_structure
     */
    public static function view_structure(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
            'view' => new external_value(PARAM_ALPHANUMEXT, 'Registered view name'),
            'viewed' => new external_value(PARAM_BOOL, 'Whether Moodle registered the view'),
        ]);
    }
}
