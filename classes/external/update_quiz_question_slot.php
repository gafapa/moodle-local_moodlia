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
 * Update quiz question slot external function.
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
use local_moodlia\operation\module_tools;
use local_moodlia\operation\update_quiz_question_slot as update_quiz_question_slot_operation;

/**
 * External API adapter for update_quiz_question_slot.
 */
class update_quiz_question_slot extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'slot' => new external_value(PARAM_INT, 'Quiz slot number'),
            'max_mark' => new external_value(PARAM_FLOAT, 'Slot maximum mark'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $slot Quiz slot number.
     * @param float $maxmark Slot maximum mark.
     * @return array
     */
    public static function execute(int $quizmoduleid, int $slot, float $maxmark): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'slot' => $slot,
            'max_mark' => $maxmark,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quizmoduleid,
            'slot' => $slot,
            'max_mark' => $maxmark,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course_and_cm_from_cmid((int) $quizmoduleid, 'quiz')[0];
        $cm = module_tools::get_quiz_module($course, (int) $quizmoduleid);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/quiz:manage', $context);

        return update_quiz_question_slot_operation::execute((int) $quizmoduleid, (int) $slot, (float) $maxmark);
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
            'slot' => new external_value(PARAM_INT, 'Quiz slot number'),
            'slot_id' => new external_value(PARAM_INT, 'Quiz slot id'),
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'question_type' => new external_value(PARAM_PLUGIN, 'Question type'),
            'maxmark' => new external_value(PARAM_FLOAT, 'Updated slot maximum mark'),
            'updated' => new external_value(PARAM_BOOL, 'Whether the slot was updated'),
        ]);
    }
}
