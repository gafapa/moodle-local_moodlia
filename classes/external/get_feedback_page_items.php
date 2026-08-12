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
 * Get feedback page items external function.
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
use local_moodlia\operation\get_feedback_page_items as get_feedback_page_items_operation;

/**
 * External API adapter for get_feedback_page_items.
 */
class get_feedback_page_items extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'page' => new external_value(PARAM_INT, 'Zero-based feedback page number', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $page Page.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $page = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'page' => $pagenumber,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'page' => $page,
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
        if (!has_any_capability(['mod/feedback:edititems', 'mod/feedback:viewreports'], $modulecontext)) {
            throw new \required_capability_exception($modulecontext, 'mod/feedback:viewreports', 'nopermissions', '');
        }

        return get_feedback_page_items_operation::execute((int) $courseid, (int) $moduleid, (int) $pagenumber);
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
            'page' => new external_value(PARAM_INT, 'Zero-based feedback page number'),
            'count' => new external_value(PARAM_INT, 'Number of items on the page'),
            'has_previous_page' => new external_value(PARAM_BOOL, 'Whether there is a previous page'),
            'has_next_page' => new external_value(PARAM_BOOL, 'Whether there is a next page'),
            'items' => new external_multiple_structure(get_feedback_items::item_structure()),
            'warnings' => get_course_feedbacks::warnings_structure(),
        ]);
    }
}
