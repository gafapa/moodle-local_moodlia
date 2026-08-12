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
 * List quiz questions external function.
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
use local_moodlia\operation\get_quiz_questions as get_quiz_questions_operation;

/**
 * External API adapter for get_quiz_questions.
 */
class get_quiz_questions extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @return array
     */
    public static function execute(int $quizmoduleid): array {
        ['quiz_module_id' => $quizmoduleid] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quizmoduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:manage', $modulecontext);

        return get_quiz_questions_operation::execute((int) $quizmoduleid);
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
            'questions' => new external_multiple_structure(new external_single_structure([
                'slot' => new external_value(PARAM_INT, 'Quiz slot number'),
                'slot_id' => new external_value(PARAM_INT, 'Quiz slot id'),
                'question_id' => new external_value(PARAM_INT, 'Moodle question id'),
                'name' => new external_value(PARAM_TEXT, 'Question name'),
                'question_type' => new external_value(PARAM_PLUGIN, 'Question type'),
                'page' => new external_value(PARAM_INT, 'Quiz page number'),
                'maxmark' => new external_value(PARAM_FLOAT, 'Slot maximum mark'),
            ])),
        ]);
    }
}
