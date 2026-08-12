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
 * Get assignment grades external function.
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
use local_moodlia\operation\get_assignment_grades as get_assignment_grades_operation;

/**
 * External API adapter for get_assignment_grades.
 */
class get_assignment_grades extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'since' => new external_value(PARAM_INT, 'Only grades modified at or after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $since Since.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $since = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'since' => $modifiedsince,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'since' => $since,
        ]);

        get_assignment_submissions::require_assignment_context((int) $courseid, (int) $moduleid, 'mod/assign:grade');

        return get_assignment_grades_operation::execute((int) $courseid, (int) $moduleid, (int) $modifiedsince);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
            'grades' => new external_multiple_structure(new external_single_structure([
                'grade_id' => new external_value(PARAM_INT, 'Assignment grade id'),
                'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
                'user_id' => new external_value(PARAM_INT, 'Student user id'),
                'attempt_number' => new external_value(PARAM_INT, 'Attempt number'),
                'created' => new external_value(PARAM_INT, 'Creation timestamp'),
                'modified' => new external_value(PARAM_INT, 'Modified timestamp'),
                'grader_id' => new external_value(PARAM_INT, 'Grader user id'),
                'grade' => new external_value(PARAM_FLOAT, 'Raw grade'),
                'grade_formatted' => new external_value(PARAM_RAW, 'Formatted grade, or empty string'),
            ])),
        ]);
    }
}
