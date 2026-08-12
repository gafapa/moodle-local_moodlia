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
 * Add random questions to quiz external function.
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
use local_moodlia\operation\add_random_questions_to_quiz as add_random_questions_to_quiz_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for add_random_questions_to_quiz.
 */
class add_random_questions_to_quiz extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'number' => new external_value(PARAM_INT, 'Number of random question slots to add'),
            'slot' => new external_value(PARAM_INT, 'Requested quiz slot', VALUE_DEFAULT, null, NULL_ALLOWED),
            'include_subcategories' => new external_value(PARAM_BOOL, 'Include child question categories', VALUE_DEFAULT, false),
            'bank_scope' => new external_value(
                PARAM_ALPHANUMEXT,
                'Source question bank scope: course_shared or quiz_private',
                VALUE_DEFAULT,
                question_tools::BANK_SCOPE_COURSE_SHARED
            ),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Source course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quiz_module_id Quiz course module id.
     * @param int $category_id Question category id.
     * @param int $number Number of random slots.
     * @param int|null $slot Requested quiz slot.
     * @param bool $include_subcategories Include child categories.
     * @param string $bank_scope Source bank scope.
     * @param int|null $question_bank_module_id Source course qbank module id.
     * @return array
     */
    public static function execute(
        int $quiz_module_id,
        int $category_id,
        int $number,
        ?int $slot = null,
        bool $include_subcategories = false,
        string $bank_scope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $question_bank_module_id = null
    ): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'category_id' => $categoryid,
            'number' => $number,
            'slot' => $slot,
            'include_subcategories' => $includesubcategories,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'category_id' => $category_id,
            'number' => $number,
            'slot' => $slot,
            'include_subcategories' => $include_subcategories,
            'bank_scope' => $bank_scope,
            'question_bank_module_id' => $question_bank_module_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/quiz:manage', $modulecontext);

        question_tools::require_quiz_api();
        $quizobj = \mod_quiz\quiz_settings::create_for_cmid((int) $quizmoduleid);
        $course = $quizobj->get_course();
        $sourcelocation = question_tools::resolve_existing_question_bank_location(
            (int) $course->id,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $bankscope === question_tools::BANK_SCOPE_QUIZ_PRIVATE ? (int) $quizmoduleid : null
        );
        if ($sourcelocation === null) {
            throw new \invalid_parameter_exception('No matching source question bank exists in the quiz course.');
        }
        question_tools::validate_category_in_location((int) $course->id, (int) $categoryid, $sourcelocation);
        self::validate_context($sourcelocation['context']);
        require_capability('moodle/question:useall', $sourcelocation['context']);

        return add_random_questions_to_quiz_operation::execute(
            (int) $quizmoduleid,
            (int) $categoryid,
            (int) $number,
            $slot === null ? null : (int) $slot,
            (bool) $includesubcategories,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid
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
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'added_count' => new external_value(PARAM_INT, 'Number of added random slots'),
            'include_subcategories' => new external_value(PARAM_BOOL, 'Whether child categories were included'),
            'slots' => new external_multiple_structure(new external_single_structure([
                'slot' => new external_value(PARAM_INT, 'Quiz slot number'),
                'slot_id' => new external_value(PARAM_INT, 'Quiz slot id'),
                'question_id' => new external_value(PARAM_INT, 'Question id'),
                'name' => new external_value(PARAM_TEXT, 'Question name'),
                'question_type' => new external_value(PARAM_PLUGIN, 'Question type'),
                'page' => new external_value(PARAM_INT, 'Quiz page'),
                'maxmark' => new external_value(PARAM_FLOAT, 'Slot maximum mark'),
            ])),
        ]);
    }
}
