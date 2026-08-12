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
 * Get database entries external function.
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
use local_moodlia\operation\data_tools;
use local_moodlia\operation\get_data_entries as get_data_entries_operation;

/**
 * External API adapter for get_data_entries.
 */
class get_data_entries extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
            'search' => new external_value(PARAM_RAW, 'Search text', VALUE_DEFAULT, ''),
            'include_contents' => new external_value(PARAM_BOOL, 'Include field contents', VALUE_DEFAULT, true),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'per_page' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 20),
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
        string $search = '',
        bool $includecontents = true,
        int $page = 0,
        int $perpage = 20
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'search' => $search,
            'include_contents' => $includecontents,
            'page' => $page,
            'per_page' => $perpage,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'search' => $search,
            'include_contents' => $includecontents,
            'page' => $page,
            'per_page' => $perpage,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = data_tools::get_data_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/data:viewentry', $modulecontext);

        return get_data_entries_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $search,
            (bool) $includecontents,
            (int) $page,
            (int) $perpage
        );
    }

    /**
     * Return a database entry structure.
     *
     * @return external_single_structure
     */
    public static function entry_structure(): external_single_structure {
        return new external_single_structure([
            'entry_id' => new external_value(PARAM_INT, 'Database entry id'),
            'data_id' => new external_value(PARAM_INT, 'Database instance id'),
            'module_id' => new external_value(PARAM_INT, 'Database course module id'),
            'user_id' => new external_value(PARAM_INT, 'Entry user id'),
            'group_id' => new external_value(PARAM_INT, 'Entry group id'),
            'approved' => new external_value(PARAM_BOOL, 'Whether the entry is approved'),
            'time_created' => new external_value(PARAM_INT, 'Entry creation time'),
            'time_modified' => new external_value(PARAM_INT, 'Entry modification time'),
            'contents_json' => new external_value(PARAM_RAW, 'JSON-encoded entry field contents'),
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
            'count' => new external_value(PARAM_INT, 'Entry count'),
            'entries' => new external_multiple_structure(self::entry_structure()),
        ]);
    }
}
