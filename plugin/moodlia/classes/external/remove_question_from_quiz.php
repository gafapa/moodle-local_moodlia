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
 * Remove question from quiz external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\module_tools;
use local_moodlia\operation\remove_question_from_quiz as remove_question_from_quiz_operation;

/**
 * External API adapter for remove_question_from_quiz.
 */
class remove_question_from_quiz extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'slot' => new external_value(PARAM_INT, 'Quiz slot number', VALUE_DEFAULT, null, NULL_ALLOWED),
            'question_id' => new external_value(PARAM_INT, 'Question id', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quiz_module_id Quiz course module id.
     * @param int|null $slot Quiz slot number.
     * @param int|null $question_id Question id.
     * @return array
     */
    public static function execute(int $quiz_module_id, ?int $slot = null, ?int $question_id = null): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'slot' => $slot,
            'question_id' => $questionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'slot' => $slot,
            'question_id' => $question_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course_and_cm_from_cmid((int) $quizmoduleid, 'quiz')[0];
        $cm = module_tools::get_quiz_module($course, (int) $quizmoduleid);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/quiz:manage', $context);

        return remove_question_from_quiz_operation::execute(
            (int) $quizmoduleid,
            $slot === null ? null : (int) $slot,
            $questionid === null ? null : (int) $questionid
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'question_id' => new external_value(PARAM_INT, 'Removed question id'),
            'slot' => new external_value(PARAM_INT, 'Removed slot number'),
            'removed' => new external_value(PARAM_BOOL, 'Whether the question slot was removed'),
        ]);
    }
}
