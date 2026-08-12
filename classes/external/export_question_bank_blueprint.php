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
 * Export question bank blueprint external function.
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
use local_moodlia\operation\export_question_bank_blueprint as export_question_bank_blueprint_operation;
use local_moodlia\operation\question_tools;

/**
 * Export question bank blueprint implementation.
 */
class export_question_bank_blueprint extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope: course_shared or quiz_private', VALUE_DEFAULT, question_tools::BANK_SCOPE_COURSE_SHARED),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id for quiz_private scope', VALUE_DEFAULT, null, NULL_ALLOWED),
            'category_id' => new external_value(PARAM_INT, 'Optional single source category id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'include_unsupported' => new external_value(PARAM_BOOL, 'Include unsupported questions as skipped entries', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param string $bankscope Bank scope.
     * @param int|null $questionbankmoduleid Question bank module id.
     * @param int|null $quizmoduleid Quiz module id.
     * @param int|null $categoryid Category id.
     * @param bool $includeunsupported Include unsupported.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $bankscope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        ?int $categoryid = null,
        bool $includeunsupported = true
    ): array {
        [
            'course_id' => $courseid,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
            'category_id' => $categoryid,
            'include_unsupported' => $includeunsupported,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
            'category_id' => $categoryid,
            'include_unsupported' => $includeunsupported,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $location = question_tools::resolve_existing_question_bank_location(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid
        );
        if ($location === null) {
            throw new \invalid_parameter_exception('No matching question bank exists in the selected course.');
        }
        self::validate_context($location['context']);
        require_capability('moodle/question:viewall', $location['context']);

        return export_question_bank_blueprint_operation::execute(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid,
            $categoryid === null ? null : (int) $categoryid,
            (bool) $includeunsupported
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope'),
            'context_id' => new external_value(PARAM_INT, 'Question bank context id'),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_REQUIRED, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id', VALUE_REQUIRED, null, NULL_ALLOWED),
            'category_count' => new external_value(PARAM_INT, 'Exported category count'),
            'question_count' => new external_value(PARAM_INT, 'Exported question count'),
            'skipped_question_count' => new external_value(PARAM_INT, 'Skipped unsupported question count'),
            'blueprint_json' => new external_value(PARAM_RAW, 'MoodlIA question bank blueprint JSON'),
        ]);
    }
}
