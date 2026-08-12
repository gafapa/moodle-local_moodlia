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
 * View quiz attempt external function.
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
use local_moodlia\operation\question_quiz_attempt_tools;
use local_moodlia\operation\view_quiz_attempt as view_quiz_attempt_operation;

/**
 * External API adapter for view_quiz_attempt.
 */
class view_quiz_attempt extends external_api {
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

        get_quiz_attempt_data::validate_quiz_attempt_context((int) $quizmoduleid);

        return view_quiz_attempt_operation::execute(
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
            'attempt_id' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'page' => new external_value(PARAM_INT, 'Attempt page number'),
            'viewed' => new external_value(PARAM_BOOL, 'Whether Moodle registered the attempt view'),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }
}
