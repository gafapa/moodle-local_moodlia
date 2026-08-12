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
 * Create database field external function.
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
use local_moodlia\operation\create_data_field as create_data_field_operation;
use local_moodlia\operation\data_tools;

/**
 * External API adapter for create_data_field.
 */
class create_data_field extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
            'field_type' => new external_value(PARAM_ALPHA, 'Database field type'),
            'name' => new external_value(PARAM_TEXT, 'Database field name'),
            'description' => new external_value(PARAM_RAW, 'Database field description', VALUE_DEFAULT, ''),
            'required' => new external_value(PARAM_BOOL, 'Whether the field is required', VALUE_DEFAULT, false),
            'options' => new external_value(PARAM_RAW, 'JSON-encoded field options', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $fieldtype,
        string $name,
        string $description = '',
        bool $required = false,
        string $options = '{}'
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'field_type' => $fieldtype,
            'name' => $name,
            'description' => $description,
            'required' => $required,
            'options' => $options,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'field_type' => $fieldtype,
            'name' => $name,
            'description' => $description,
            'required' => $required,
            'options' => $options,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = data_tools::get_data_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/data:managetemplates', $modulecontext);

        return create_data_field_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $fieldtype,
            $name,
            $description,
            (bool) $required,
            data_tools::decode_json_object($options, 'options')
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_data_fields::field_structure();
    }
}
