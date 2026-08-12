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
 * List question banks external function.
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
use local_moodlia\operation\get_question_banks as get_question_banks_operation;

/**
 * External API adapter for get_question_banks.
 */
class get_question_banks extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'include_quiz_private' => new external_value(PARAM_BOOL, 'Include quiz-owned private banks', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param bool $includequizprivate Include quiz-owned private banks.
     * @return array
     */
    public static function execute(int $courseid, bool $includequizprivate = true): array {
        [
            'course_id' => $courseid,
            'include_quiz_private' => $includequizprivate,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'include_quiz_private' => $includequizprivate,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_question_banks_operation::execute((int) $courseid, (bool) $includequizprivate);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'banks' => new external_multiple_structure(new external_single_structure([
                'bank_scope' => new external_value(PARAM_ALPHANUMEXT, 'Question bank scope'),
                'module_id' => new external_value(PARAM_INT, 'Owning course module id'),
                'question_bank_module_id' => new external_value(PARAM_INT, 'Course question bank module id', VALUE_REQUIRED, null, NULL_ALLOWED),
                'quiz_module_id' => new external_value(PARAM_INT, 'Quiz module id', VALUE_REQUIRED, null, NULL_ALLOWED),
                'name' => new external_value(PARAM_TEXT, 'Question bank display name'),
                'context_id' => new external_value(PARAM_INT, 'Question bank context id'),
                'visible' => new external_value(PARAM_BOOL, 'Whether the owning module is visible'),
                'url' => new external_value(PARAM_URL, 'Question bank URL'),
            ])),
        ]);
    }
}
