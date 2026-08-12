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
 * Get assignment submission status external function.
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
use local_moodlia\operation\assignment_tools;
use local_moodlia\operation\get_assignment_submission_status as get_assignment_submission_status_operation;

/**
 * External API adapter for get_assignment_submission_status.
 */
class get_assignment_submission_status extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 for the current user', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $userid = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $course = get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/assign:view', $modulecontext);

        return get_assignment_submission_status_operation::execute((int) $courseid, (int) $moduleid, (int) $userid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return self::submission_status_structure();
    }

    /**
     * Return the canonical assignment submission status structure.
     *
     * @return external_single_structure
     */
    public static function submission_status_structure(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
            'user_id' => new external_value(PARAM_INT, 'Submission owner user id'),
            'submission_id' => new external_value(PARAM_INT, 'Assignment submission id, or 0 when none exists'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Assignment submission status'),
            'attempt_number' => new external_value(PARAM_INT, 'Assignment attempt number'),
            'can_edit' => new external_value(PARAM_BOOL, 'Whether the current user can edit the submission'),
            'submitted' => new external_value(PARAM_BOOL, 'Whether the submission has been submitted for grading'),
            'online_text' => new external_value(PARAM_RAW, 'Submitted online text HTML'),
            'graded' => new external_value(PARAM_BOOL, 'Whether the submission has a saved grade'),
            'grade' => new external_value(PARAM_FLOAT, 'Saved assignment grade, or 0 when not graded'),
            'grader_id' => new external_value(PARAM_INT, 'Grader user id, or 0 when not graded'),
            'grading_status' => new external_value(PARAM_ALPHANUMEXT, 'Moodle assignment grading status'),
            'feedback_comment' => new external_value(PARAM_RAW, 'Assignment feedback comment HTML'),
        ]);
    }
}
