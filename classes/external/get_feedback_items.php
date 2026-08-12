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
 * Get feedback items external function.
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
use local_moodlia\operation\get_feedback_items as get_feedback_items_operation;

/**
 * External API adapter for get_feedback_items.
 */
class get_feedback_items extends external_api {
    /**
     * Define input parameters.
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
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Feedback course module id.
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
        $course = get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        if (!has_any_capability(['mod/feedback:edititems', 'mod/feedback:viewreports'], $modulecontext)) {
            throw new \required_capability_exception($modulecontext, 'mod/feedback:viewreports', 'nopermissions', '');
        }

        return get_feedback_items_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Return a feedback item structure.
     *
     * @return external_single_structure
     */
    public static function item_structure(): external_single_structure {
        return new external_single_structure([
            'item_id' => new external_value(PARAM_INT, 'Feedback item id'),
            'feedback_id' => new external_value(PARAM_INT, 'Feedback instance id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'name' => new external_value(PARAM_RAW, 'Feedback item name'),
            'name_format' => new external_value(PARAM_INT, 'Feedback item name format'),
            'label' => new external_value(PARAM_TEXT, 'Feedback item label'),
            'presentation' => new external_value(PARAM_RAW, 'Feedback item presentation or choices'),
            'presentation_format' => new external_value(PARAM_INT, 'Feedback item presentation format'),
            'type' => new external_value(PARAM_ALPHA, 'Feedback item type'),
            'has_value' => new external_value(PARAM_BOOL, 'Whether the item records a value'),
            'position' => new external_value(PARAM_INT, 'Item position'),
            'item_number' => new external_value(PARAM_INT, 'Item number among value-bearing items'),
            'required' => new external_value(PARAM_BOOL, 'Whether the item is required'),
            'depend_item_id' => new external_value(PARAM_INT, 'Dependency item id'),
            'depend_value' => new external_value(PARAM_RAW, 'Dependency value'),
            'options' => new external_value(PARAM_RAW, 'Feedback item options'),
            'other_data' => new external_value(PARAM_RAW, 'Additional item data exposed by Moodle'),
        ]);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'feedback_id' => new external_value(PARAM_INT, 'Feedback instance id'),
            'count' => new external_value(PARAM_INT, 'Number of feedback items'),
            'items' => new external_multiple_structure(self::item_structure()),
        ]);
    }
}
