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
 * Get quiz access information external function.
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
use local_moodlia\operation\get_quiz_access_information as get_quiz_access_information_operation;

/**
 * External API adapter for get_quiz_access_information.
 */
class get_quiz_access_information extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
        ]);
    }

    public static function execute(int $quiz_module_id): array {
        ['quiz_module_id' => $quizmoduleid] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
        ]);

        self::validate_context(\context_system::instance());
        require_capability('local/moodlia:useapi', \context_system::instance());

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:view', $modulecontext);

        return get_quiz_access_information_operation::execute((int) $quizmoduleid);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'can_attempt' => new external_value(PARAM_BOOL, 'Whether the current user can attempt the quiz'),
            'can_manage' => new external_value(PARAM_BOOL, 'Whether the current user can manage the quiz'),
            'can_preview' => new external_value(PARAM_BOOL, 'Whether the current user can preview the quiz'),
            'can_review_my_attempts' => new external_value(PARAM_BOOL, 'Whether the current user can review own attempts'),
            'can_view_reports' => new external_value(PARAM_BOOL, 'Whether the current user can view quiz reports'),
            'access_rules' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Access rule description')),
            'active_rule_names' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Active access rule plugin name')),
            'prevent_access_reasons' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Access prevention reason')),
        ]);
    }
}
