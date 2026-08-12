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
 * Create question category external function.
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
use local_moodlia\operation\course_tools;
use local_moodlia\operation\create_question_category as create_question_category_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for create_question_category.
 */
class create_question_category extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Question category name'),
            'parent_id' => new external_value(PARAM_INT, 'Parent question category id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'description' => new external_value(PARAM_RAW, 'Question category description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope: course_shared or quiz_private', VALUE_DEFAULT, question_tools::BANK_SCOPE_COURSE_SHARED),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id for quiz_private scope', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param int|null $parentid Parentid.
     * @param string|null $description Description.
     * @param string $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        ?int $parentid = null,
        ?string $description = null,
        string $bankscope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null
    ): array {
        [
            'course_id' => $courseid,
            'name' => $name,
            'parent_id' => $parentid,
            'description' => $description,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'name' => $name,
            'parent_id' => $parentid,
            'description' => $description,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = course_tools::get_course((int) $courseid);
        $coursecontext = \context_course::instance($course->id);
        self::validate_context($coursecontext);
        $location = question_tools::resolve_question_bank_location(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid
        );
        self::validate_context($location['context']);
        require_capability('moodle/question:managecategory', $location['context']);

        return create_question_category_operation::execute(
            (int) $courseid,
            $name,
            $parentid === null ? null : (int) $parentid,
            $description,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'name' => new external_value(PARAM_TEXT, 'Question category name'),
            'context_id' => new external_value(PARAM_INT, 'Question category context id'),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope'),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_REQUIRED, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id', VALUE_REQUIRED, null, NULL_ALLOWED),
        ]);
    }
}
