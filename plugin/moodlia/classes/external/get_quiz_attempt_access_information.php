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
 * Get quiz attempt access information external function.
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
use local_moodlia\operation\get_quiz_attempt_access_information as get_quiz_attempt_access_information_operation;

/**
 * External API adapter for get_quiz_attempt_access_information.
 */
class get_quiz_attempt_access_information extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt_id' => new external_value(PARAM_INT, 'Attempt id, or 0 for the current user last attempt', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $quiz_module_id, int $attempt_id = 0): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'attempt_id' => $attempt_id,
        ]);

        self::validate_context(\context_system::instance());
        require_capability('local/moodlia:useapi', \context_system::instance());
        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:view', $modulecontext);

        return get_quiz_attempt_access_information_operation::execute((int) $quizmoduleid, (int) $attemptid);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt_id' => new external_value(PARAM_INT, 'Requested attempt id'),
            'end_time' => new external_value(PARAM_INT, 'Attempt end timestamp, or 0'),
            'is_finished' => new external_value(PARAM_BOOL, 'Whether Moodle considers attempts finished for this user'),
            'is_preflight_check_required' => new external_value(PARAM_BOOL, 'Whether a preflight check is required'),
            'prevent_new_attempt_reasons' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Prevent reason')),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }
}
