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
 * Quiz results report external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_quiz_results_report as get_quiz_results_report_operation;

/**
 * External API adapter for get_quiz_results_report.
 */
class get_quiz_results_report extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'limit' => new external_value(PARAM_INT, 'Maximum users to include', VALUE_DEFAULT, 100),
            'include_previews' => new external_value(PARAM_BOOL, 'Include preview attempts', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quiz_module_id Quiz course module id.
     * @param int $limit Maximum users to include.
     * @param bool $include_previews Include preview attempts.
     * @return array
     */
    public static function execute(int $quiz_module_id, int $limit = 100, bool $include_previews = true): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'limit' => $userlimit,
            'include_previews' => $includepreviews,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'limit' => $limit,
            'include_previews' => $include_previews,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $cm = get_coursemodule_from_id('quiz', (int) $quizmoduleid, 0, false, MUST_EXIST);
        $modulecontext = \context_module::instance((int) $cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:viewreports', $modulecontext);

        $coursecontext = \context_course::instance((int) $cm->course);
        self::validate_context($coursecontext);
        require_capability('moodle/course:viewparticipants', $coursecontext);

        return get_quiz_results_report_operation::execute((int) $quizmoduleid, (int) $userlimit, (bool) $includepreviews);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'quiz_name' => new external_value(PARAM_TEXT, 'Quiz name'),
            'requested_limit' => new external_value(PARAM_INT, 'Requested maximum users'),
            'returned_user_count' => new external_value(PARAM_INT, 'Returned user count'),
            'total_enrolled_user_count' => new external_value(PARAM_INT, 'Total enrolled users visible to the caller'),
            'quiz_grade_max' => new external_value(PARAM_FLOAT, 'Quiz maximum grade'),
            'users_with_attempts_count' => new external_value(PARAM_INT, 'Returned users with attempts'),
            'users_with_finished_attempts_count' => new external_value(PARAM_INT, 'Returned users with finished attempts'),
            'users_with_grades_count' => new external_value(PARAM_INT, 'Returned users with best grades'),
            'average_best_grade' => new external_value(PARAM_FLOAT, 'Average best grade across returned users with grades'),
            'average_best_grade_percentage' => new external_value(PARAM_FLOAT, 'Average best grade percentage'),
            'users' => new external_multiple_structure(new external_single_structure([
                'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
                'username' => new external_value(PARAM_USERNAME, 'Moodle username'),
                'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                'roles' => new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'Role shortname')),
                'attempt_count' => new external_value(PARAM_INT, 'Attempt count'),
                'finished_attempt_count' => new external_value(PARAM_INT, 'Finished attempt count'),
                'in_progress_attempt_count' => new external_value(PARAM_INT, 'In-progress attempt count'),
                'preview_attempt_count' => new external_value(PARAM_INT, 'Preview attempt count'),
                'last_attempt_state' => new external_value(PARAM_TEXT, 'Last attempt state'),
                'last_attempt_time_start' => new external_value(PARAM_INT, 'Last attempt start timestamp'),
                'last_attempt_time_finish' => new external_value(PARAM_INT, 'Last attempt finish timestamp'),
                'has_grade' => new external_value(PARAM_BOOL, 'Whether the user has a best grade'),
                'best_grade' => new external_value(PARAM_FLOAT, 'Best grade'),
                'grade_to_pass' => new external_value(PARAM_FLOAT, 'Grade to pass'),
                'grade_percentage' => new external_value(PARAM_FLOAT, 'Best grade percentage'),
                'feedback_text' => new external_value(PARAM_RAW, 'Overall feedback text'),
                'feedback_format' => new external_value(PARAM_INT, 'Overall feedback text format'),
            ])),
            'warnings' => new external_multiple_structure(new external_single_structure([
                'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
                'warning_code' => new external_value(PARAM_ALPHANUMEXT, 'Warning code'),
                'message' => new external_value(PARAM_TEXT, 'Warning message'),
            ])),
        ]);
    }
}
