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
 * List questions external function.
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
use local_moodlia\operation\get_questions as get_questions_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for get_questions.
 */
class get_questions extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope: course_shared or quiz_private', VALUE_DEFAULT, question_tools::BANK_SCOPE_COURSE_SHARED),
            'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id for quiz_private scope', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $categoryid Categoryid.
     * @param string $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $categoryid,
        string $bankscope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null
    ): array {
        [
            'course_id' => $courseid,
            'category_id' => $categoryid,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'category_id' => $categoryid,
            'bank_scope' => $bankscope,
            'question_bank_module_id' => $questionbankmoduleid,
            'quiz_module_id' => $quizmoduleid,
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
        if ($location === null) {
            throw new \invalid_parameter_exception('No matching question bank exists in the selected course.');
        }

        self::validate_context($location['context']);
        require_capability('moodle/question:viewall', $location['context']);

        $categories = question_tools::get_question_categories(
            (int) $courseid,
            $bankscope,
            $questionbankmoduleid === null ? null : (int) $questionbankmoduleid,
            $quizmoduleid === null ? null : (int) $quizmoduleid,
            true
        );
        $found = false;
        foreach ($categories as $category) {
            if ((int) $category['category_id'] === (int) $categoryid) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new \invalid_parameter_exception('category_id must belong to the selected question bank.');
        }

        return get_questions_operation::execute((int) $categoryid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'questions' => new external_multiple_structure(new external_single_structure([
                'question_id' => new external_value(PARAM_INT, 'Question id'),
                'category_id' => new external_value(PARAM_INT, 'Question category id'),
                'question_type' => new external_value(PARAM_PLUGIN, 'Question type'),
                'name' => new external_value(PARAM_TEXT, 'Question name'),
                'question_text' => new external_value(PARAM_RAW, 'Question text'),
                'default_mark' => new external_value(PARAM_FLOAT, 'Default mark'),
            ])),
        ]);
    }
}
