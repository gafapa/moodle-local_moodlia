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
 * Get course quizzes external function.
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
use local_moodlia\operation\get_course_quizzes as get_course_quizzes_operation;
use local_moodlia\operation\question_quiz_attempt_tools;

/**
 * External API adapter for get_course_quizzes.
 */
class get_course_quizzes extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Single Moodle course id', VALUE_DEFAULT, 0),
            'course_ids' => new external_value(PARAM_RAW, 'JSON array, comma-separated list, or single Moodle course id; [] for visible quizzes', VALUE_DEFAULT, '[]'),
        ]);
    }

    public static function execute(int $course_id = 0, string $course_ids = '[]'): array {
        [
            'course_id' => $courseid,
            'course_ids' => $courseids,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'course_ids' => $course_ids,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $decodedcourseids = question_quiz_attempt_tools::decode_id_list((string) $courseids);
        if ((int) $courseid > 0) {
            array_unshift($decodedcourseids, (int) $courseid);
        }

        return get_course_quizzes_operation::execute(array_values(array_unique($decodedcourseids)));
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_ids' => new external_multiple_structure(new external_value(PARAM_INT, 'Requested course id')),
            'count' => new external_value(PARAM_INT, 'Returned quiz count'),
            'quizzes' => new external_multiple_structure(self::quiz_summary_structure()),
            'warnings' => get_quiz_attempt_data::warnings_structure(),
        ]);
    }

    public static function quiz_summary_structure(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'name' => new external_value(PARAM_TEXT, 'Quiz activity name'),
            'intro' => new external_value(PARAM_RAW, 'Quiz intro HTML'),
            'intro_format' => new external_value(PARAM_INT, 'Quiz intro format'),
            'time_open' => new external_value(PARAM_INT, 'Opening timestamp'),
            'time_close' => new external_value(PARAM_INT, 'Closing timestamp'),
            'time_limit' => new external_value(PARAM_INT, 'Time limit in seconds'),
            'attempts_allowed' => new external_value(PARAM_INT, 'Allowed attempts, or 0 for unlimited'),
            'grade' => new external_value(PARAM_FLOAT, 'Maximum quiz grade'),
            'sum_grades' => new external_value(PARAM_FLOAT, 'Raw sum of question grades'),
            'preferred_behaviour' => new external_value(PARAM_ALPHANUMEXT, 'Question behaviour plugin name'),
            'questions_per_page' => new external_value(PARAM_INT, 'Questions per page'),
            'navigation_method' => new external_value(PARAM_ALPHANUMEXT, 'Quiz navigation method'),
            'has_feedback' => new external_value(PARAM_BOOL, 'Whether the quiz has overall feedback configured'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the quiz module is visible'),
            'url' => new external_value(PARAM_URL, 'Quiz view URL'),
        ]);
    }
}
