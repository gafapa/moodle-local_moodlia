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
 * Get feedback finished responses external function.
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
use local_moodlia\operation\feedback_tools;
use local_moodlia\operation\get_feedback_finished_responses as get_feedback_finished_responses_operation;

/**
 * External API adapter for get_feedback_finished_responses.
 */
class get_feedback_finished_responses extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
        ]);
    }

    public static function execute(int $course_id, int $module_id): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        $course = get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/feedback:view', $modulecontext);

        return get_feedback_finished_responses_operation::execute((int) $courseid, (int) $moduleid);
    }

    public static function response_structure(): external_single_structure {
        return new external_single_structure([
            'response_id' => new external_value(PARAM_INT, 'Feedback response value id'),
            'name' => new external_value(PARAM_RAW, 'Response field name'),
            'print_value' => new external_value(PARAM_RAW, 'Formatted response value'),
            'raw_value' => new external_value(PARAM_RAW, 'Raw response value'),
        ]);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'feedback_id' => new external_value(PARAM_INT, 'Feedback instance id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'count' => new external_value(PARAM_INT, 'Number of response values'),
            'responses' => new external_multiple_structure(self::response_structure()),
            'warnings' => get_course_feedbacks::warnings_structure(),
        ]);
    }
}
