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
 * Save quiz attempt external function.
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
use local_moodlia\operation\save_quiz_attempt as save_quiz_attempt_operation;

/**
 * External API adapter for save_quiz_attempt.
 */
class save_quiz_attempt extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt_id' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'data' => new external_value(PARAM_RAW, 'JSON array of attempt response name/value pairs', VALUE_DEFAULT, '[]'),
            'preflight_data' => new external_value(PARAM_RAW, 'JSON array of preflight name/value pairs', VALUE_DEFAULT, '[]'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $attemptid Attemptid.
     * @param string $data Data.
     * @param string $preflightdata Preflightdata.
     * @return array
     */
    public static function execute(
        int $quizmoduleid,
        int $attemptid,
        string $data = '[]',
        string $preflightdata = '[]'
    ): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
            'data' => $attemptdata,
            'preflight_data' => $preflightdata,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
            'data' => $data,
            'preflight_data' => $preflightdata,
        ]);

        get_quiz_attempt_data::validate_quiz_attempt_context((int) $quizmoduleid);

        return save_quiz_attempt_operation::execute(
            (int) $quizmoduleid,
            (int) $attemptid,
            question_quiz_attempt_tools::decode_quiz_attempt_data((string) $attemptdata),
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
            'saved' => new external_value(PARAM_BOOL, 'Whether Moodle saved the attempt data'),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }
}
