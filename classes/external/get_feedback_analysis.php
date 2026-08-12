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
 * Get feedback analysis external function.
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
use local_moodlia\operation\feedback_tools;
use local_moodlia\operation\get_feedback_analysis as get_feedback_analysis_operation;

/**
 * External API adapter for get_feedback_analysis.
 */
class get_feedback_analysis extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id, or 0 for Moodle default', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $moduleid Module id.
     * @param int $groupid Group id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $groupid = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
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
        require_capability('mod/feedback:viewanalysepage', $modulecontext);

        return get_feedback_analysis_operation::execute((int) $courseid, (int) $moduleid, (int) $groupid);
    }

    /**
     * Analysis item structure.
     *
     * @return external_single_structure
     */
    public static function analysis_item_structure(): external_single_structure {
        return new external_single_structure([
            'item' => get_feedback_items::item_structure(),
            'data_json' => new external_value(PARAM_RAW, 'JSON-encoded analysis values returned by Moodle'),
        ]);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'feedback_id' => new external_value(PARAM_INT, 'Feedback instance id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id'),
            'completed_count' => new external_value(PARAM_INT, 'Number of completed submissions'),
            'items_count' => new external_value(PARAM_INT, 'Number of feedback items'),
            'items_data' => new external_multiple_structure(self::analysis_item_structure()),
            'warnings' => get_course_feedbacks::warnings_structure(),
        ]);
    }
}
