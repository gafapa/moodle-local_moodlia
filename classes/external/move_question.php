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
 * Move question external function.
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
use local_moodlia\operation\move_question as move_question_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for move_question.
 */
class move_question extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'target_category_id' => new external_value(PARAM_INT, 'Destination question category id'),
            'target_bank_scope' => new external_value(
                PARAM_ALPHANUMEXT,
                'Destination question bank scope: course_shared or quiz_private',
                VALUE_DEFAULT,
                question_tools::BANK_SCOPE_COURSE_SHARED
            ),
            'target_question_bank_module_id' => new external_value(PARAM_INT, 'Destination course question bank module id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'target_quiz_module_id' => new external_value(PARAM_INT, 'Destination quiz module id for quiz_private scope', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $questionid Questionid.
     * @param int $targetcategoryid Targetcategoryid.
     * @param string $targetbankscope Targetbankscope.
     * @param int|null $targetquestionbankmoduleid Targetquestionbankmoduleid.
     * @param int|null $targetquizmoduleid Targetquizmoduleid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $questionid,
        int $targetcategoryid,
        string $targetbankscope = question_tools::BANK_SCOPE_COURSE_SHARED,
        ?int $targetquestionbankmoduleid = null,
        ?int $targetquizmoduleid = null
    ): array {
        [
            'course_id' => $courseid,
            'question_id' => $questionid,
            'target_category_id' => $targetcategoryid,
            'target_bank_scope' => $targetbankscope,
            'target_question_bank_module_id' => $targetquestionbankmoduleid,
            'target_quiz_module_id' => $targetquizmoduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'question_id' => $questionid,
            'target_category_id' => $targetcategoryid,
            'target_bank_scope' => $targetbankscope,
            'target_question_bank_module_id' => $targetquestionbankmoduleid,
            'target_quiz_module_id' => $targetquizmoduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $question = question_tools::get_question((int) $questionid);
        question_require_capability_on($question, 'move');

        $targetlocation = question_tools::resolve_existing_question_bank_location(
            (int) $courseid,
            $targetbankscope,
            $targetquestionbankmoduleid === null ? null : (int) $targetquestionbankmoduleid,
            $targetquizmoduleid === null ? null : (int) $targetquizmoduleid
        );
        if ($targetlocation === null) {
            throw new \invalid_parameter_exception('No matching target question bank exists in the selected course.');
        }
        question_tools::validate_category_in_location((int) $courseid, (int) $targetcategoryid, $targetlocation);
        self::validate_context($targetlocation['context']);
        require_capability('moodle/question:add', $targetlocation['context']);

        return move_question_operation::execute(
            (int) $courseid,
            (int) $questionid,
            (int) $targetcategoryid,
            $targetbankscope,
            $targetquestionbankmoduleid === null ? null : (int) $targetquestionbankmoduleid,
            $targetquizmoduleid === null ? null : (int) $targetquizmoduleid
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'question_id' => new external_value(PARAM_INT, 'Question id'),
            'source_category_id' => new external_value(PARAM_INT, 'Source question category id'),
            'target_category_id' => new external_value(PARAM_INT, 'Destination question category id'),
            'target_context_id' => new external_value(PARAM_INT, 'Destination question bank context id'),
            'target_bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Destination question bank scope'),
            'target_question_bank_module_id' => new external_value(PARAM_INT, 'Destination course question bank module id', VALUE_REQUIRED, null, NULL_ALLOWED),
            'target_quiz_module_id' => new external_value(PARAM_INT, 'Destination quiz module id', VALUE_REQUIRED, null, NULL_ALLOWED),
            'moved' => new external_value(PARAM_BOOL, 'Whether the question was moved'),
        ]);
    }
}
