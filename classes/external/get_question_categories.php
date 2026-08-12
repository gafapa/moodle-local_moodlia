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
 * List question categories external function.
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
use local_moodlia\operation\get_question_categories as get_question_categories_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for get_question_categories.
 */
class get_question_categories extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope: course_shared or quiz_private', VALUE_DEFAULT, question_tools::BANK_SCOPE_COURSE_SHARED),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id for quiz_private scope', VALUE_DEFAULT, null, NULL_ALLOWED),
            'include_top' => new external_value(PARAM_BOOL, 'Include the synthetic top category', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param string $bankscope Question bank scope.
     * @param int|null $questionbankmoduleid Course qbank module id.
     * @param int|null $quizmoduleid Quiz module id.
     * @param bool $includetop Include top category.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $bankscope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        bool $includetop = false
    ): array {
        [
            'course_id' => $courseid,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
            'include_top' => $includetop,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
            'include_top' => $includetop,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $location = question_tools::resolve_existing_question_bank_location(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid
        );
        if ($location !== null) {
            self::validate_context($location['context']);
            require_capability('moodle/question:viewall', $location['context']);
        }

        return get_question_categories_operation::execute(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid,
            (bool) $includetop
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(new external_single_structure([
                'category_id' => new external_value(PARAM_INT, 'Question category id'),
                'name' => new external_value(PARAM_TEXT, 'Question category name'),
                'context_id' => new external_value(PARAM_INT, 'Question category context id'),
                'parent_id' => new external_value(PARAM_INT, 'Parent category id'),
                'question_count' => new external_value(PARAM_INT, 'Visible question count'),
                'is_top' => new external_value(PARAM_BOOL, 'Whether this is the top category'),
                'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope'),
                'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_REQUIRED, null, NULL_ALLOWED),
                'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id', VALUE_REQUIRED, null, NULL_ALLOWED),
                'url' => new external_value(PARAM_URL, 'Question category URL'),
            ])),
        ]);
    }
}
