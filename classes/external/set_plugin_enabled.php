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
 * Moodle plugin state external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\set_plugin_enabled as set_plugin_enabled_operation;

/**
 * External API adapter for set_plugin_enabled.
 */
final class set_plugin_enabled extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Frankenstyle plugin component'),
            'enabled' => new external_value(PARAM_BOOL, 'Requested enabled state'),
        ]);
    }

    public static function execute(string $component, bool $enabled): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'enabled' => $enabled,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);
        require_capability('local/moodlia:manageplugins', $context);

        return set_plugin_enabled_operation::execute($params['component'], (bool) $params['enabled']);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'component' => new external_value(PARAM_COMPONENT, 'Plugin component'),
            'previous_enabled' => new external_value(PARAM_BOOL, 'Enabled state before the request'),
            'enabled' => new external_value(PARAM_BOOL, 'Verified enabled state after the request'),
            'changed' => new external_value(PARAM_BOOL, 'Whether the enabled state changed'),
        ]);
    }
}
