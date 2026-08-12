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
 * Moodle plugin update external function.
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
use local_moodlia\operation\check_plugin_updates as check_plugin_updates_operation;

/**
 * External API adapter for check_plugin_updates.
 */
final class check_plugin_updates extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Optional Frankenstyle plugin component', VALUE_DEFAULT, ''),
            'refresh' => new external_value(PARAM_BOOL, 'Refresh update data from Moodle.org', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $component Component.
     * @param bool $refresh Refresh.
     * @return array
     */
    public static function execute(string $component = '', bool $refresh = false): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'refresh' => $refresh,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);
        require_capability('local/moodlia:manageplugins', $context);

        return check_plugin_updates_operation::execute($params['component'], (bool) $params['refresh']);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'refreshed' => new external_value(PARAM_BOOL, 'Whether Moodle.org was queried during this request'),
            'last_checked' => new external_value(PARAM_INT, 'Unix time of the most recent update check, or zero'),
            'total' => new external_value(PARAM_INT, 'Number of compatible plugin updates'),
            'updates' => new external_multiple_structure(new external_single_structure([
                'component' => new external_value(PARAM_COMPONENT, 'Plugin component'),
                'current_version' => new external_value(PARAM_RAW, 'Currently installed version'),
                'available_version' => new external_value(PARAM_RAW, 'Available version'),
                'release' => new external_value(PARAM_RAW, 'Available release name'),
                'maturity' => new external_value(PARAM_INT, 'Moodle maturity constant, or zero'),
                'information_url' => new external_value(PARAM_RAW, 'Update information URL, or empty'),
                'download_url' => new external_value(PARAM_RAW, 'Update package URL, or empty'),
            ])),
        ]);
    }
}
