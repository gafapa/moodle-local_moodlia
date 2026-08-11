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
 * Moodle plugin inventory external function.
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
use local_moodlia\operation\list_plugins as list_plugins_operation;

/**
 * External API adapter for list_plugins.
 */
final class list_plugins extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'plugin_type' => new external_value(PARAM_PLUGIN, 'Optional Moodle plugin type', VALUE_DEFAULT, ''),
            'source' => new external_value(PARAM_ALPHA, 'Source: all, standard, additional, or missing', VALUE_DEFAULT, 'all'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Moodle plugin status or all', VALUE_DEFAULT, 'all'),
        ]);
    }

    public static function execute(string $plugin_type = '', string $source = 'all', string $status = 'all'): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'plugin_type' => $plugin_type,
            'source' => $source,
            'status' => $status,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);
        require_capability('local/moodlia:manageplugins', $context);

        return list_plugins_operation::execute($params['plugin_type'], $params['source'], $params['status']);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Number of matching plugins'),
            'plugins' => new external_multiple_structure(self::plugin_structure()),
        ]);
    }

    public static function plugin_structure(): external_single_structure {
        return new external_single_structure([
            'component' => new external_value(PARAM_COMPONENT, 'Frankenstyle plugin component'),
            'plugin_type' => new external_value(PARAM_PLUGIN, 'Moodle plugin type'),
            'name' => new external_value(PARAM_PLUGIN, 'Plugin name within its type'),
            'display_name' => new external_value(PARAM_TEXT, 'Localized plugin name'),
            'source' => new external_value(PARAM_ALPHA, 'Standard, additional, or missing source'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Moodle plugin installation status'),
            'version_disk' => new external_value(PARAM_RAW, 'Version found on disk, or empty'),
            'version_db' => new external_value(PARAM_RAW, 'Version installed in the database, or empty'),
            'release' => new external_value(PARAM_RAW, 'Human-readable release, or empty'),
            'enabled_known' => new external_value(PARAM_BOOL, 'Whether this plugin type exposes enabled state'),
            'enabled' => new external_value(PARAM_BOOL, 'Whether the plugin is enabled'),
            'can_change_enabled' => new external_value(PARAM_BOOL, 'Whether MoodlIA can safely request a state change'),
            'update_count' => new external_value(PARAM_INT, 'Number of cached compatible updates'),
        ]);
    }
}
