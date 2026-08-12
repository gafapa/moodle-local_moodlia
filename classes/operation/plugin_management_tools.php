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
 * Shared Moodle plugin administration helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Provides read-only plugin metadata and guarded plugin state changes.
 */
final class plugin_management_tools {
    /** Plugin types whose state API has more than the requested boolean states. */
    private const MULTISTATE_PLUGIN_TYPES = ['filter', 'repository'];

    /**
     * Return Moodle's plugin manager.
     *
     * @return \core_plugin_manager
     */
    public static function manager(): \core_plugin_manager {
        return \core_plugin_manager::instance();
    }

    /**
     * Resolve one plugin by Frankenstyle component name.
     *
     * @param string $component Plugin component.
     * @return \core\plugininfo\base
     */
    public static function get_plugin(string $component): \core\plugininfo\base {
        $component = clean_param(trim($component), PARAM_COMPONENT);
        if ($component === '') {
            throw new \invalid_parameter_exception('component must be a valid Moodle plugin component.');
        }

        $plugin = self::manager()->get_plugin_info($component);
        if (!$plugin) {
            throw new \invalid_parameter_exception('component must reference a known Moodle plugin.');
        }

        return $plugin;
    }

    /**
     * Return a stable public source label.
     *
     * @param \core\plugininfo\base $plugin Plugin metadata.
     * @return string
     */
    public static function source_label(\core\plugininfo\base $plugin): string {
        if ($plugin->get_status() === \core_plugin_manager::PLUGIN_STATUS_MISSING) {
            return 'missing';
        }

        return $plugin->is_standard() ? 'standard' : 'additional';
    }

    /**
     * Determine whether Moodle exposes an enable/disable implementation for this plugin.
     *
     * @param \core\plugininfo\base $plugin Plugin metadata.
     * @return bool
     */
    public static function can_change_enabled(\core\plugininfo\base $plugin): bool {
        if (
            $plugin->component === 'local_moodlia' ||
            in_array($plugin->type, self::MULTISTATE_PLUGIN_TYPES, true) ||
            !$plugin->rootdir ||
            $plugin->versiondb === null ||
            $plugin->get_status() !== \core_plugin_manager::PLUGIN_STATUS_UPTODATE
        ) {
            return false;
        }

        if ($plugin->is_enabled() === null) {
            return false;
        }

        $method = new \ReflectionMethod(get_class($plugin), 'enable_plugin');
        return $method->getDeclaringClass()->getName() !== \core\plugininfo\base::class;
    }

    /**
     * Convert Moodle plugin metadata to a transport-safe record.
     *
     * @param \core\plugininfo\base $plugin Plugin metadata.
     * @return array
     */
    public static function plugin_record(\core\plugininfo\base $plugin): array {
        $enabled = $plugin->is_enabled();
        $updates = $plugin->available_updates();

        return [
            'component' => (string) $plugin->component,
            'plugin_type' => (string) $plugin->type,
            'name' => (string) $plugin->name,
            'display_name' => (string) $plugin->displayname,
            'source' => self::source_label($plugin),
            'status' => (string) $plugin->get_status(),
            'version_disk' => $plugin->versiondisk === null ? '' : (string) $plugin->versiondisk,
            'version_db' => $plugin->versiondb === null ? '' : (string) $plugin->versiondb,
            'release' => $plugin->release === null ? '' : (string) $plugin->release,
            'enabled_known' => $enabled !== null,
            'enabled' => $enabled === true,
            'can_change_enabled' => self::can_change_enabled($plugin),
            'update_count' => is_array($updates) ? count($updates) : 0,
        ];
    }

    /**
     * Return all plugin objects as one flat component-indexed list.
     *
     * @return array
     */
    public static function all_plugins(): array {
        $plugins = [];
        foreach (self::manager()->get_plugins() as $typeplugins) {
            foreach ($typeplugins as $plugin) {
                $plugins[$plugin->component] = $plugin;
            }
        }
        ksort($plugins);

        return $plugins;
    }
}
