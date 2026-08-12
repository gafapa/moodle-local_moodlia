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
 * Submit assignment for grading external function.
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
use local_moodlia\operation\assignment_tools;
use local_moodlia\operation\submit_assignment_for_grading as submit_assignment_for_grading_operation;

/**
 * External API adapter for submit_assignment_for_grading.
 */
class submit_assignment_for_grading extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'accept_submission_statement' => new external_value(
                PARAM_BOOL,
                'Whether the Moodle submission statement is accepted',
                VALUE_DEFAULT,
                true
            ),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Assignment course module id.
     * @param bool $accept_submission_statement Whether the Moodle submission statement is accepted.
     * @return array
     */
    public static function execute(int $course_id, int $module_id, bool $accept_submission_statement = true): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'accept_submission_statement' => $acceptsubmissionstatement,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'accept_submission_statement' => $accept_submission_statement,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);

        $course = get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/assign:submit', $modulecontext);

        return submit_assignment_for_grading_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (bool) $acceptsubmissionstatement
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_assignment_submission_status::submission_status_structure();
    }
}
