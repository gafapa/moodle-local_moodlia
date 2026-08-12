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
 * Moodle plugin dependencies external function.
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
use local_moodlia\operation\get_plugin_dependencies as get_plugin_dependencies_operation;

/**
 * External API adapter for get_plugin_dependencies.
 */
final class get_plugin_dependencies extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Frankenstyle plugin component'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $component Component.
     * @return array
     */
    public static function execute(string $component): array {
        ['component' => $component] = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);
        require_capability('local/moodlia:manageplugins', $context);

        return get_plugin_dependencies_operation::execute($component);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'component' => new external_value(PARAM_COMPONENT, 'Frankenstyle plugin component'),
            'satisfied' => new external_value(PARAM_BOOL, 'Whether every dependency is satisfied'),
            'dependencies' => new external_multiple_structure(new external_single_structure([
                'component' => new external_value(PARAM_COMPONENT, 'Required component or core'),
                'installed_version' => new external_value(PARAM_RAW, 'Installed version, or empty'),
                'required_version' => new external_value(PARAM_RAW, 'Required version'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Resolved requirement status'),
                'availability' => new external_value(PARAM_ALPHANUMEXT, 'Remote availability, or empty'),
            ])),
            'required_by' => new external_multiple_structure(
                new external_value(PARAM_COMPONENT, 'Component that requires this plugin')
            ),
        ]);
    }
}
