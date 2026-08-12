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
 * Moodle plugin dependency operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Returns resolved and reverse dependencies for one plugin.
 */
final class get_plugin_dependencies {
    /**
     * Execute the operation.
     *
     * @param string $component Plugin component.
     * @return array
     */
    public static function execute(string $component): array {
        $manager = plugin_management_tools::manager();
        $plugin = plugin_management_tools::get_plugin($component);
        $dependencies = [];
        $satisfied = $plugin->versiondisk !== null;

        foreach ($manager->resolve_requirements($plugin) as $dependencycomponent => $requirement) {
            $status = (string) ($requirement->status ?? 'unknown');
            if ($status !== \core_plugin_manager::REQUIREMENT_STATUS_OK) {
                $satisfied = false;
            }
            $dependencies[] = [
                'component' => (string) $dependencycomponent,
                'installed_version' => $requirement->hasver === null ? '' : (string) $requirement->hasver,
                'required_version' => $requirement->reqver === null ? '' : (string) $requirement->reqver,
                'status' => $status,
                'availability' => $requirement->availability === null ? '' : (string) $requirement->availability,
            ];
        }

        $requiredby = array_values($manager->other_plugins_that_require($plugin->component));
        sort($requiredby);

        return [
            'component' => (string) $plugin->component,
            'satisfied' => $satisfied,
            'dependencies' => $dependencies,
            'required_by' => $requiredby,
        ];
    }
}
