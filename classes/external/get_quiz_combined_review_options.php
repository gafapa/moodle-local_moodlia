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
 * Get quiz combined review options external function.
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
use local_moodlia\operation\get_quiz_combined_review_options as get_quiz_combined_review_options_operation;

/**
 * External API adapter for get_quiz_combined_review_options.
 */
class get_quiz_combined_review_options extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id, or 0 for current user', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $quiz_module_id, int $user_id = 0): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'user_id' => $user_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:view', $modulecontext);

        return get_quiz_combined_review_options_operation::execute((int) $quizmoduleid, (int) $userid);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'user_id' => new external_value(PARAM_INT, 'Requested Moodle user id'),
            'some_options' => new external_multiple_structure(self::review_option_structure()),
            'all_options' => new external_multiple_structure(self::review_option_structure()),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }

    public static function review_option_structure(): external_single_structure {
        return new external_single_structure([
            'name' => new external_value(PARAM_ALPHANUMEXT, 'Review option name'),
            'value' => new external_value(PARAM_BOOL, 'Review option visibility'),
        ]);
    }
}
