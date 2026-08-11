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
 * View assignment grading table external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\view_assignment_grading_table as view_assignment_grading_table_operation;

/**
 * External API adapter for view_assignment_grading_table.
 */
class view_assignment_grading_table extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
        ]);
    }

    public static function execute(int $course_id, int $module_id): array {
        ['course_id' => $courseid, 'module_id' => $moduleid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
        ]);

        get_assignment_submissions::require_assignment_context((int) $courseid, (int) $moduleid, 'mod/assign:grade');

        return view_assignment_grading_table_operation::execute((int) $courseid, (int) $moduleid);
    }

    public static function execute_returns(): external_single_structure {
        return view_assignment::view_structure();
    }
}
