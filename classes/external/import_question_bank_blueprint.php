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
 * Import question bank blueprint external function.
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
use local_moodlia\operation\import_question_bank_blueprint as import_question_bank_blueprint_operation;
use local_moodlia\operation\question_tools;

/**
 * Import question bank blueprint implementation.
 */
class import_question_bank_blueprint extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'blueprint_json' => new external_value(PARAM_RAW, 'MoodlIA question bank blueprint JSON'),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope: course_shared or quiz_private', VALUE_DEFAULT, question_tools::BANK_SCOPE_COURSE_SHARED),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id for quiz_private scope', VALUE_DEFAULT, null, NULL_ALLOWED),
            'category_id' => new external_value(PARAM_INT, 'Optional target category id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'create_categories' => new external_value(PARAM_BOOL, 'Create category structure from the blueprint', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $blueprintjson Blueprintjson.
     * @param string $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @param int|null $categoryid Categoryid.
     * @param bool $createcategories Createcategories.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $blueprintjson,
        string $bankscope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        ?int $categoryid = null,
        bool $createcategories = true
    ): array {
        [
            'course_id' => $courseid,
            'blueprint_json' => $blueprintjson,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
            'category_id' => $categoryid,
            'create_categories' => $createcategories,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'blueprint_json' => $blueprintjson,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
            'category_id' => $categoryid,
            'create_categories' => $createcategories,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $location = question_tools::resolve_question_bank_location(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid
        );
        self::validate_context($location['context']);
        require_capability('moodle/question:managecategory', $location['context']);
        require_capability('moodle/question:add', $location['context']);

        return import_question_bank_blueprint_operation::execute(
            (int) $courseid,
            $blueprintjson,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid,
            $categoryid === null ? null : (int) $categoryid,
            (bool) $createcategories
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
            'created_category_count' => new external_value(PARAM_INT, 'Created category count'),
            'created_question_count' => new external_value(PARAM_INT, 'Created question count'),
            'created_categories_json' => new external_value(PARAM_RAW, 'Created categories JSON'),
            'created_questions_json' => new external_value(PARAM_RAW, 'Created questions JSON'),
        ]);
    }
}
