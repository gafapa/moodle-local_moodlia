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
 * Add question to quiz external function.
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
use local_moodlia\operation\add_question_to_quiz as add_question_to_quiz_operation;

/**
 * External API adapter for add_question_to_quiz.
 */
class add_question_to_quiz extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'slot' => new external_value(PARAM_INT, 'Requested quiz slot', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $questionid Question id.
     * @param int|null $slot Requested quiz slot.
     * @return array
     */
    public static function execute(int $quizmoduleid, int $questionid, ?int $slot = null): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'question_id' => $questionid,
            'slot' => $slot,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quizmoduleid,
            'question_id' => $questionid,
            'slot' => $slot,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:manage', $modulecontext);

        return add_question_to_quiz_operation::execute(
            (int) $quizmoduleid,
            (int) $questionid,
            $slot === null ? null : (int) $slot
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
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'slot' => new external_value(PARAM_INT, 'Resolved quiz slot'),
            'maxmark' => new external_value(PARAM_FLOAT, 'Resolved quiz slot maximum mark'),
        ]);
    }
}
