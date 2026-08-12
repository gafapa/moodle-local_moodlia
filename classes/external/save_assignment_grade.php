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
 * Save assignment grade external function.
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
use local_moodlia\operation\save_assignment_grade as save_assignment_grade_operation;

/**
 * External API adapter for save_assignment_grade.
 */
class save_assignment_grade extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'user_id' => new external_value(PARAM_INT, 'Student user id'),
            'grade' => new external_value(PARAM_FLOAT, 'Assignment grade'),
            'feedback_comment' => new external_value(PARAM_RAW, 'Feedback comment HTML', VALUE_DEFAULT, ''),
            'attempt_number' => new external_value(PARAM_INT, 'Attempt number, or -1 for latest', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Assignment course module id.
     * @param int $userid Student user id.
     * @param float $grade Assignment grade.
     * @param string $feedbackcomment Feedback comment HTML.
     * @param int $attemptnumber Attempt number, or -1 for latest.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $userid,
        float $grade,
        string $feedbackcomment = '',
        int $attemptnumber = -1
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
            'grade' => $grade,
            'feedback_comment' => $feedbackcomment,
            'attempt_number' => $attemptnumber,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
            'grade' => $grade,
            'feedback_comment' => $feedbackcomment,
            'attempt_number' => $attemptnumber,
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
        require_capability('mod/assign:grade', $modulecontext);

        return save_assignment_grade_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $userid,
            (float) $grade,
            $feedbackcomment,
            (int) $attemptnumber
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
