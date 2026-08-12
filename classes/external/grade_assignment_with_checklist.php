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
 * Grade assignment with checklist external function.
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
use local_moodlia\operation\grade_assignment_with_checklist as grade_assignment_with_checklist_operation;

/**
 * External API adapter for grade_assignment_with_checklist.
 */
class grade_assignment_with_checklist extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'user_id' => new external_value(PARAM_INT, 'Student user id'),
            'items' => new external_value(PARAM_RAW, 'JSON object with checklist grade items'),
            'feedback_comment' => new external_value(PARAM_RAW, 'Feedback comment HTML', VALUE_DEFAULT, ''),
            'attempt_number' => new external_value(PARAM_INT, 'Attempt number, or -1 for latest', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $moduleid Module id.
     * @param int $userid User id.
     * @param string $items Items.
     * @param string $feedbackcomment Feedback comment.
     * @param int $attemptnumber Attempt number.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $userid,
        string $items = '{}',
        string $feedbackcomment = '',
        int $attemptnumber = -1
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
            'items' => $items,
            'feedback_comment' => $feedbackcomment,
            'attempt_number' => $attemptnumber,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
            'items' => $items,
            'feedback_comment' => $feedbackcomment,
            'attempt_number' => $attemptnumber,
        ]);

        get_assignment_grading_form::require_assignment_context((int) $courseid, (int) $moduleid, false);
        return grade_assignment_with_checklist_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $userid,
            $items,
            $feedbackcomment,
            (int) $attemptnumber
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_assignment_submission_status::submission_status_structure();
    }
}
