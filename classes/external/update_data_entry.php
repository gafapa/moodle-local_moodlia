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
 * Update database entry external function.
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
use local_moodlia\operation\data_tools;
use local_moodlia\operation\update_data_entry as update_data_entry_operation;

/**
 * External API adapter for update_data_entry.
 */
class update_data_entry extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
            'entry_id' => new external_value(PARAM_INT, 'Database entry id'),
            'values' => new external_value(PARAM_RAW, 'JSON object keyed by field name or field id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Database course module id.
     * @param int $entryid Database entry id.
     * @param string $values JSON object keyed by field name or field id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $entryid, string $values): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'entry_id' => $entryid,
            'values' => $values,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'entry_id' => $entryid,
            'values' => $values,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = data_tools::get_data_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        if (!has_any_capability(['mod/data:manageentries', 'mod/data:writeentry'], $modulecontext)) {
            throw new \required_capability_exception($modulecontext, 'mod/data:manageentries', 'nopermissions', '');
        }

        return update_data_entry_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $entryid,
            data_tools::decode_json_object($values, 'values')
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_data_entries::entry_structure();
    }
}
