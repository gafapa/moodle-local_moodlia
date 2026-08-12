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
 * Guarded Moodle plugin state operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Enables or disables one compatible plugin and verifies the resulting state.
 */
final class set_plugin_enabled {
    /**
     * Execute the operation.
     *
     * @param string $component Component.
     * @param bool $enabled Enabled.
     * @return array
     */
    public static function execute(string $component, bool $enabled): array {
        $plugin = plugin_management_tools::get_plugin($component);
        if ($plugin->component === 'local_moodlia') {
            throw new \moodle_exception('cannotdisablemoodlia', 'local_moodlia');
        }
        if (!plugin_management_tools::can_change_enabled($plugin)) {
            throw new \moodle_exception('pluginstateunsupported', 'local_moodlia', '', $plugin->component);
        }

        $manager = plugin_management_tools::manager();
        if ($enabled) {
            foreach ($manager->resolve_requirements($plugin) as $requirement) {
                if (($requirement->status ?? '') !== \core_plugin_manager::REQUIREMENT_STATUS_OK) {
                    throw new \moodle_exception('plugindependenciesunsatisfied', 'local_moodlia', '', $plugin->component);
                }
            }
        } else {
            foreach ($manager->other_plugins_that_require($plugin->component) as $dependentcomponent) {
                $dependent = $manager->get_plugin_info($dependentcomponent);
                if ($dependent && $dependent->versiondb !== null && $dependent->is_enabled() !== false) {
                    throw new \moodle_exception(
                        'plugindependentsenabled',
                        'local_moodlia',
                        '',
                        ['plugin' => $plugin->component, 'dependent' => $dependentcomponent]
                    );
                }
            }
        }

        $previous = $plugin->is_enabled() === true;
        if ($previous === $enabled) {
            return [
                'component' => (string) $plugin->component,
                'previous_enabled' => $previous,
                'enabled' => $previous,
                'changed' => false,
            ];
        }

        $pluginclass = get_class($plugin);
        $pluginclass::enable_plugin($plugin->name, $enabled ? 1 : 0);
        \core_plugin_manager::reset_caches();

        $verifiedplugin = plugin_management_tools::get_plugin($plugin->component);
        $verified = $verifiedplugin->is_enabled() === true;
        if ($verified !== $enabled) {
            throw new \moodle_exception('pluginstateverificationfailed', 'local_moodlia', '', $plugin->component);
        }

        return [
            'component' => (string) $verifiedplugin->component,
            'previous_enabled' => $previous,
            'enabled' => $verified,
            'changed' => $previous !== $verified,
        ];
    }
}
