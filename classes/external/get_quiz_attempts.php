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
 * List quiz attempts external function.
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
use local_moodlia\operation\get_quiz_attempts as get_quiz_attempts_operation;

/**
 * External API adapter for get_quiz_attempts.
 */
class get_quiz_attempts extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 for current user', VALUE_DEFAULT, 0),
            'status' => new external_value(PARAM_ALPHA, 'Attempt status: all, finished, or unfinished', VALUE_DEFAULT, 'all'),
            'include_previews' => new external_value(PARAM_BOOL, 'Include preview attempts', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quiz_module_id Quiz course module id.
     * @param int $user_id Moodle user id, or 0 for current user.
     * @param string $status Attempt status: all, finished, or unfinished.
     * @param bool $include_previews Include preview attempts.
     * @return array
     */
    public static function execute(
        int $quiz_module_id,
        int $user_id = 0,
        string $status = 'all',
        bool $include_previews = true
    ): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'user_id' => $userid,
            'status' => $attemptstatus,
            'include_previews' => $includepreviews,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'user_id' => $user_id,
            'status' => $status,
            'include_previews' => $include_previews,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);

        global $USER;
        if ((int) $userid > 0 && (int) $userid !== (int) $USER->id) {
            require_capability('mod/quiz:viewreports', $modulecontext);
        } else if (!has_capability('mod/quiz:attempt', $modulecontext) && !has_capability('mod/quiz:preview', $modulecontext)) {
            throw new \required_capability_exception($modulecontext, 'mod/quiz:attempt', 'nopermissions', '');
        }

        return get_quiz_attempts_operation::execute(
            (int) $quizmoduleid,
            (int) $userid,
            (string) $attemptstatus,
            (bool) $includepreviews
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'user_id' => new external_value(PARAM_INT, 'Requested user id, or 0 for current user'),
            'attempts' => new external_multiple_structure(start_quiz_attempt::attempt_structure()),
        ]);
    }
}
