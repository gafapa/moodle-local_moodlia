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
 * Get quiz feedback for grade external function.
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
use local_moodlia\operation\get_quiz_feedback_for_grade as get_quiz_feedback_for_grade_operation;

/**
 * External API adapter for get_quiz_feedback_for_grade.
 */
class get_quiz_feedback_for_grade extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'grade' => new external_value(PARAM_FLOAT, 'Grade value'),
        ]);
    }

    public static function execute(int $quiz_module_id, float $grade): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'grade' => $gradevalue,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'grade' => $grade,
        ]);

        self::validate_context(\context_system::instance());
        require_capability('local/moodlia:useapi', \context_system::instance());

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:view', $modulecontext);

        return get_quiz_feedback_for_grade_operation::execute((int) $quizmoduleid, (float) $gradevalue);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'grade' => new external_value(PARAM_FLOAT, 'Requested grade value'),
            'feedback_text' => new external_value(PARAM_RAW, 'Feedback text, or empty string'),
            'feedback_format' => new external_value(PARAM_INT, 'Feedback text format'),
        ]);
    }
}
