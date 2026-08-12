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
 * View quiz attempt review external function.
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
use local_moodlia\operation\view_quiz_attempt_review as view_quiz_attempt_review_operation;

/**
 * External API adapter for view_quiz_attempt_review.
 */
class view_quiz_attempt_review extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt_id' => new external_value(PARAM_INT, 'Quiz attempt id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $attemptid Attemptid.
     * @return array
     */
    public static function execute(int $quizmoduleid, int $attemptid): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
        ]);

        get_quiz_attempt_review::validate_quiz_attempt_review_context((int) $quizmoduleid);

        return view_quiz_attempt_review_operation::execute((int) $quizmoduleid, (int) $attemptid);
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
            'viewed' => new external_value(PARAM_BOOL, 'Whether Moodle registered the attempt review view'),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }
}
