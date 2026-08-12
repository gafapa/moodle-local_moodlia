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
 * Create feedback item external function.
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
use local_moodlia\operation\create_feedback_item as create_feedback_item_operation;
use local_moodlia\operation\feedback_tools;

/**
 * External API adapter for create_feedback_item.
 */
class create_feedback_item extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Feedback item type'),
            'name' => new external_value(PARAM_RAW, 'Feedback item name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'definition' => new external_value(PARAM_RAW, 'JSON item definition'),
            'position' => new external_value(PARAM_INT, 'One-based item position', VALUE_DEFAULT, null, NULL_ALLOWED),
            'label' => new external_value(PARAM_TEXT, 'Feedback item label', VALUE_DEFAULT, null, NULL_ALLOWED),
            'required' => new external_value(PARAM_BOOL, 'Whether the item is required', VALUE_DEFAULT, null, NULL_ALLOWED),
            'depend_item_id' => new external_value(PARAM_INT, 'Dependency item id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'depend_value' => new external_value(PARAM_RAW, 'Dependency value', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $type Type.
     * @param string|null $name Name.
     * @param string $definition Definition.
     * @param int|null $position Position.
     * @param string|null $label Label.
     * @param bool|null $required Required.
     * @param int|null $dependitemid Dependitemid.
     * @param string|null $dependvalue Dependvalue.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $type,
        ?string $name,
        string $definition,
        ?int $position = null,
        ?string $label = null,
        ?bool $required = null,
        ?int $dependitemid = null,
        ?string $dependvalue = null
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'type' => $itemtype,
            'name' => $itemname,
            'definition' => $definitionjson,
            'position' => $itemposition,
            'label' => $itemlabel,
            'required' => $itemrequired,
            'depend_item_id' => $dependitemid,
            'depend_value' => $dependvalue,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'type' => $type,
            'name' => $name,
            'definition' => $definition,
            'position' => $position,
            'label' => $label,
            'required' => $required,
            'depend_item_id' => $dependitemid,
            'depend_value' => $dependvalue,
        ]);

        self::require_edit_capability((int) $courseid, (int) $moduleid);

        return create_feedback_item_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (string) $itemtype,
            $itemname,
            (string) $definitionjson,
            $itemposition === null ? null : (int) $itemposition,
            $itemlabel,
            $itemrequired === null ? null : (bool) $itemrequired,
            $dependitemid === null ? null : (int) $dependitemid,
            $dependvalue
        );
    }

    /**
     * Validate shared API and Feedback edit capabilities.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     */
    private static function require_edit_capability(int $courseid, int $moduleid): void {
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        $course = get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/feedback:edititems', $modulecontext);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_feedback_items::item_structure();
    }
}
