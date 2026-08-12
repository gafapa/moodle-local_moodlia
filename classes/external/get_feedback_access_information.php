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
 * Get feedback access information external function.
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
use local_moodlia\operation\feedback_tools;
use local_moodlia\operation\get_feedback_access_information as get_feedback_access_information_operation;

/**
 * External API adapter for get_feedback_access_information.
 */
class get_feedback_access_information extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $course = get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/feedback:view', $modulecontext);

        return get_feedback_access_information_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'feedback_id' => new external_value(PARAM_INT, 'Feedback instance id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'can_view_analysis' => new external_value(PARAM_BOOL, 'Whether current user can view analysis'),
            'can_complete' => new external_value(PARAM_BOOL, 'Whether current user can complete the feedback'),
            'can_submit' => new external_value(PARAM_BOOL, 'Whether current user can submit the feedback'),
            'can_delete_submissions' => new external_value(PARAM_BOOL, 'Whether current user can delete submissions'),
            'can_view_reports' => new external_value(PARAM_BOOL, 'Whether current user can view reports'),
            'can_edit_items' => new external_value(PARAM_BOOL, 'Whether current user can edit items'),
            'is_empty' => new external_value(PARAM_BOOL, 'Whether the feedback has no items'),
            'is_open' => new external_value(PARAM_BOOL, 'Whether the feedback is open'),
            'is_already_submitted' => new external_value(PARAM_BOOL, 'Whether current user already submitted'),
            'is_anonymous' => new external_value(PARAM_BOOL, 'Whether the feedback is anonymous'),
            'warnings' => get_course_feedbacks::warnings_structure(),
        ]);
    }
}
