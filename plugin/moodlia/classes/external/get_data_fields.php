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
 * Get database fields external function.
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
use local_moodlia\operation\data_tools;
use local_moodlia\operation\get_data_fields as get_data_fields_operation;

/**
 * External API adapter for get_data_fields.
 */
class get_data_fields extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Database course module id.
     * @return array
     */
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

        $course = get_course($courseid);
        $cm = data_tools::get_data_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/data:viewentry', $modulecontext);

        return get_data_fields_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Return a database field structure.
     *
     * @return external_single_structure
     */
    public static function field_structure(): external_single_structure {
        return new external_single_structure([
            'field_id' => new external_value(PARAM_INT, 'Database field id'),
            'data_id' => new external_value(PARAM_INT, 'Database instance id'),
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
            'type' => new external_value(PARAM_ALPHA, 'Database field type'),
            'name' => new external_value(PARAM_TEXT, 'Database field name'),
            'description' => new external_value(PARAM_RAW, 'Database field description'),
            'required' => new external_value(PARAM_BOOL, 'Whether the field is required'),
            'params_json' => new external_value(PARAM_RAW, 'JSON-encoded field parameters'),
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
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
            'data_id' => new external_value(PARAM_INT, 'Database instance id'),
            'count' => new external_value(PARAM_INT, 'Field count'),
            'fields' => new external_multiple_structure(self::field_structure()),
        ]);
    }
}
