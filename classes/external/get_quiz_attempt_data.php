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
 * Get quiz attempt data external function.
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
use local_moodlia\operation\get_quiz_attempt_data as get_quiz_attempt_data_operation;
use local_moodlia\operation\question_quiz_attempt_tools;

/**
 * External API adapter for get_quiz_attempt_data.
 */
class get_quiz_attempt_data extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt_id' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'page' => new external_value(PARAM_INT, 'Attempt page number', VALUE_DEFAULT, 0),
            'preflight_data' => new external_value(PARAM_RAW, 'JSON array of preflight name/value pairs', VALUE_DEFAULT, '[]'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quiz module id.
     * @param int $attemptid Attempt id.
     * @param int $page Page.
     * @param string $preflightdata Preflight data.
     * @return array
     */
    public static function execute(
        int $quizmoduleid,
        int $attemptid,
        int $page = 0,
        string $preflightdata = '[]'
    ): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
            'page' => $attemptpage,
            'preflight_data' => $preflightdata,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
            'page' => $page,
            'preflight_data' => $preflightdata,
        ]);

        self::validate_quiz_attempt_context((int) $quizmoduleid);

        return get_quiz_attempt_data_operation::execute(
            (int) $quizmoduleid,
            (int) $attemptid,
            (int) $attemptpage,
            question_quiz_attempt_tools::decode_preflight_data((string) $preflightdata)
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt' => start_quiz_attempt::attempt_structure(),
            'page' => new external_value(PARAM_INT, 'Requested page number'),
            'next_page' => new external_value(PARAM_INT, 'Next page number, or -1 when there is no next page'),
            'messages' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Access message')),
            'questions' => new external_multiple_structure(self::question_structure()),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Validate shared quiz attempt context and capability.
     *
     * @param int $quizmoduleid Quiz course module id.
     */
    public static function validate_quiz_attempt_context(int $quizmoduleid): void {
        self::validate_context(\context_system::instance());
        require_capability('local/moodlia:useapi', \context_system::instance());

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        if (!has_capability('mod/quiz:attempt', $modulecontext) && !has_capability('mod/quiz:preview', $modulecontext)) {
            throw new \required_capability_exception($modulecontext, 'mod/quiz:attempt', 'nopermissions', '');
        }
    }

    /**
     * Return canonical attempt question structure.
     *
     * @return external_single_structure
     */
    public static function question_structure(): external_single_structure {
        return new external_single_structure([
            'slot' => new external_value(PARAM_INT, 'Question slot number'),
            'question_type' => new external_value(PARAM_ALPHANUMEXT, 'Question type'),
            'page' => new external_value(PARAM_INT, 'Attempt page number'),
            'question_number' => new external_value(PARAM_RAW, 'Displayed question number'),
            'html' => new external_value(PARAM_RAW, 'Rendered question HTML from Moodle'),
            'flagged' => new external_value(PARAM_BOOL, 'Whether the question is flagged'),
            'sequence_check' => new external_value(PARAM_INT, 'Question attempt sequence check count'),
            'last_action_time' => new external_value(PARAM_INT, 'Most recent question action timestamp'),
            'has_autosaved_step' => new external_value(PARAM_BOOL, 'Whether the question has autosaved data'),
            'state' => new external_value(PARAM_TEXT, 'Question state where visible'),
            'state_class' => new external_value(PARAM_TEXT, 'Question state CSS class where visible'),
            'status' => new external_value(PARAM_RAW, 'Human readable question status'),
            'blocked_by_previous' => new external_value(PARAM_BOOL, 'Whether the question is blocked by a previous question'),
            'mark' => new external_value(PARAM_RAW, 'Question mark where visible'),
            'max_mark' => new external_value(PARAM_FLOAT, 'Maximum question mark where visible'),
            'settings' => new external_value(PARAM_RAW, 'Question settings JSON where exposed by Moodle'),
            'response_file_area_count' => new external_value(PARAM_INT, 'Number of response file areas'),
        ]);
    }

    /**
     * Return canonical warning structure.
     *
     * @return external_multiple_structure
     */
    public static function warnings_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'item' => new external_value(PARAM_TEXT, 'Warning item'),
            'item_id' => new external_value(PARAM_INT, 'Warning item id'),
            'warning_code' => new external_value(PARAM_TEXT, 'Warning code'),
            'message' => new external_value(PARAM_TEXT, 'Warning message'),
        ]));
    }
}
