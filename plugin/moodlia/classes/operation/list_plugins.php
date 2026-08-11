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
 * Moodle plugin inventory operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Returns a filtered administrative plugin inventory.
 */
final class list_plugins {
    /**
     * Execute the operation.
     *
     * @param string $plugintype Optional Moodle plugin type.
     * @param string $source Source filter.
     * @param string $status Status filter.
     * @return array
     */
    public static function execute(string $plugintype = '', string $source = 'all', string $status = 'all'): array {
        $plugintype = clean_param(trim($plugintype), PARAM_PLUGIN);
        $sources = ['all', 'standard', 'additional', 'missing'];
        $statuses = ['all', 'nodb', 'uptodate', 'new', 'upgrade', 'delete', 'downgrade', 'missing'];
        if (!in_array($source, $sources, true)) {
            throw new \invalid_parameter_exception('source is not a supported plugin source filter.');
        }
        if (!in_array($status, $statuses, true)) {
            throw new \invalid_parameter_exception('status is not a supported Moodle plugin status filter.');
        }
        $records = [];

        foreach (plugin_management_tools::all_plugins() as $plugin) {
            $record = plugin_management_tools::plugin_record($plugin);
            if ($plugintype !== '' && $record['plugin_type'] !== $plugintype) {
                continue;
            }
            if ($source !== 'all' && $record['source'] !== $source) {
                continue;
            }
            if ($status !== 'all' && $record['status'] !== $status) {
                continue;
            }
            $records[] = $record;
        }

        return [
            'total' => count($records),
            'plugins' => $records,
        ];
    }
}
