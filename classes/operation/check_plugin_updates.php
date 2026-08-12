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
 * Moodle plugin update inspection operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns cached or freshly fetched plugin update information.
 */
final class check_plugin_updates {
    /**
     * Execute the operation.
     *
     * @param string $component Component.
     * @param bool $refresh Refresh.
     * @return array
     */
    public static function execute(string $component = '', bool $refresh = false): array {
        $checker = \core\update\checker::instance();
        if ($refresh) {
            $checker->fetch();
        }

        $plugins = [];
        if (trim($component) !== '') {
            $plugin = plugin_management_tools::get_plugin($component);
            $plugins[$plugin->component] = $plugin;
        } else {
            $plugins = plugin_management_tools::all_plugins();
        }

        $updates = [];
        foreach ($plugins as $plugin) {
            $available = $checker->get_update_info($plugin->component);
            if (!is_array($available)) {
                continue;
            }
            foreach ($available as $update) {
                $updates[] = [
                    'component' => (string) $plugin->component,
                    'current_version' => $plugin->versiondb === null ? '' : (string) $plugin->versiondb,
                    'available_version' => (string) ($update->version ?? ''),
                    'release' => (string) ($update->release ?? ''),
                    'maturity' => (int) ($update->maturity ?? 0),
                    'information_url' => (string) ($update->url ?? ''),
                    'download_url' => (string) ($update->download ?? ''),
                ];
            }
        }

        usort($updates, static function (array $left, array $right): int {
            return [$left['component'], $left['available_version']] <=> [$right['component'], $right['available_version']];
        });

        return [
            'refreshed' => $refresh,
            'last_checked' => (int) ($checker->get_last_timefetched() ?? 0),
            'total' => count($updates),
            'updates' => $updates,
        ];
    }
}
