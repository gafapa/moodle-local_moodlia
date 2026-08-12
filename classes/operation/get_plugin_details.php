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
 * Moodle plugin detail operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns details for one Moodle plugin.
 */
final class get_plugin_details {
    /**
     * Execute the operation.
     *
     * @param string $component Component.
     * @return array
     */
    public static function execute(string $component): array {
        $plugin = plugin_management_tools::get_plugin($component);
        $record = plugin_management_tools::plugin_record($plugin);
        $record['dependency_count'] = count(plugin_management_tools::manager()->resolve_requirements($plugin));
        $record['required_by_count'] = count(plugin_management_tools::manager()->other_plugins_that_require($plugin->component));

        return $record;
    }
}
