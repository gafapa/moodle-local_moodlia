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
 * Get quiz attempt review external function.
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
use local_moodlia\operation\get_quiz_attempt_review as get_quiz_attempt_review_operation;

/**
 * External API adapter for get_quiz_attempt_review.
 */
class get_quiz_attempt_review extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt_id' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'page' => new external_value(PARAM_INT, 'Review page number, or -1 for all pages', VALUE_DEFAULT, -1),
        ]);
    }

    public static function execute(int $quiz_module_id, int $attempt_id, int $page = -1): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'attempt_id' => $attemptid,
            'page' => $reviewpage,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'attempt_id' => $attempt_id,
            'page' => $page,
        ]);

        self::validate_quiz_attempt_review_context((int) $quizmoduleid);

        return get_quiz_attempt_review_operation::execute((int) $quizmoduleid, (int) $attemptid, (int) $reviewpage);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt' => start_quiz_attempt::attempt_structure(),
            'grade' => new external_value(PARAM_RAW, 'Formatted attempt grade where visible'),
            'page' => new external_value(PARAM_INT, 'Requested review page number'),
            'additional_data' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_RAW, 'Additional data identifier'),
                'title' => new external_value(PARAM_RAW, 'Additional data title'),
                'content' => new external_value(PARAM_RAW, 'Additional data rendered content'),
            ])),
            'questions' => new external_multiple_structure(get_quiz_attempt_data::question_structure()),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }

    /**
     * Validate shared quiz attempt review context and capability.
     *
     * @param int $quizmoduleid Quiz course module id.
     */
    public static function validate_quiz_attempt_review_context(int $quizmoduleid): void {
        self::validate_context(\context_system::instance());
        require_capability('local/moodlia:useapi', \context_system::instance());

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        if (
            !has_capability('mod/quiz:attempt', $modulecontext) &&
            !has_capability('mod/quiz:preview', $modulecontext) &&
            !has_capability('mod/quiz:viewreports', $modulecontext)
        ) {
            throw new \required_capability_exception($modulecontext, 'mod/quiz:attempt', 'nopermissions', '');
        }
    }
}
