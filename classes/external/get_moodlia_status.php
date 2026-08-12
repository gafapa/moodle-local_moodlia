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
 * MoodlIA status external function.
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
use local_moodlia\operation\get_moodlia_status as get_moodlia_status_operation;

/**
 * External API adapter for get_moodlia_status.
 */
class get_moodlia_status extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Execute the operation.
     *
     * @return array
     */
    public static function execute(): array {
        self::validate_parameters(self::execute_parameters(), []);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        return get_moodlia_status_operation::execute();
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'component' => new external_value(PARAM_TEXT, 'Moodle plugin component'),
            'site_url' => new external_value(PARAM_URL, 'Moodle site URL'),
            'site_name' => new external_value(PARAM_TEXT, 'Moodle site name'),
            'moodle_release' => new external_value(PARAM_TEXT, 'Moodle release string'),
            'moodle_version' => new external_value(PARAM_TEXT, 'Moodle version number'),
            'plugin_release' => new external_value(PARAM_TEXT, 'MoodlIA plugin release'),
            'plugin_version' => new external_value(PARAM_INT, 'MoodlIA plugin version'),
            'user_id' => new external_value(PARAM_INT, 'Authenticated Moodle user id'),
            'username' => new external_value(PARAM_USERNAME, 'Authenticated Moodle username'),
            'can_use_api' => new external_value(PARAM_BOOL, 'Whether the user has local/moodlia:useapi'),
            'rest_service' => new external_value(PARAM_ALPHANUMEXT, 'MoodlIA REST service shortname'),
            'function_count' => new external_value(PARAM_INT, 'Declared MoodlIA REST function count'),
            'functions_json' => new external_value(PARAM_RAW, 'JSON array with declared MoodlIA REST function names'),
        ]);
    }
}
