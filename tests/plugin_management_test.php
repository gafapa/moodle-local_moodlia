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
 * Plugin management operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\get_plugin_dependencies;
use local_moodlia\operation\get_plugin_details;
use local_moodlia\operation\list_plugins;


/**
 * Exercises read-only plugin management behavior against Moodle's plugin manager.
 */
final class plugin_management_test extends \advanced_testcase {
    /**
     * Inventory results are sorted, filterable, and contain MoodlIA.
     */
    public function test_list_plugins_returns_sorted_filterable_inventory(): void {
        $inventory = list_plugins::execute();

        $this->assertSame(count($inventory['plugins']), $inventory['total']);
        $this->assertGreaterThan(0, $inventory['total']);
        $components = array_column($inventory['plugins'], 'component');
        $sortedcomponents = $components;
        sort($sortedcomponents);
        $this->assertSame($sortedcomponents, $components);

        $index = array_search('local_moodlia', $components, true);
        $this->assertNotFalse($index);
        $moodlia = $inventory['plugins'][$index];

        foreach (
            [
                ['plugin_type' => $moodlia['plugin_type']],
                ['source' => $moodlia['source']],
                ['status' => $moodlia['status']],
            ] as $filter
        ) {
            $filtered = list_plugins::execute(
                $filter['plugin_type'] ?? '',
                $filter['source'] ?? 'all',
                $filter['status'] ?? 'all'
            );
            $this->assertSame(count($filtered['plugins']), $filtered['total']);
            $this->assertContains('local_moodlia', array_column($filtered['plugins'], 'component'));
            foreach ($filtered['plugins'] as $plugin) {
                foreach ($filter as $field => $value) {
                    $this->assertSame($value, $plugin[$field]);
                }
            }
        }
    }

    /**
     * Detail and dependency operations agree on their counts and component.
     */
    public function test_plugin_details_and_dependencies_are_consistent(): void {
        $details = get_plugin_details::execute('local_moodlia');
        $dependencies = get_plugin_dependencies::execute('local_moodlia');

        $this->assertSame('local_moodlia', $details['component']);
        $this->assertSame('local_moodlia', $dependencies['component']);
        $this->assertSame($details['dependency_count'], count($dependencies['dependencies']));
        $this->assertSame($details['required_by_count'], count($dependencies['required_by']));
        $this->assertIsBool($dependencies['satisfied']);

        $requiredby = $dependencies['required_by'];
        $sortedrequiredby = $requiredby;
        sort($sortedrequiredby);
        $this->assertSame($sortedrequiredby, $requiredby);

        foreach ($dependencies['dependencies'] as $dependency) {
            $this->assertNotSame('', $dependency['component']);
            $this->assertNotSame('', $dependency['status']);
        }
    }

    /**
     * Unknown components are rejected by both component-specific reads.
     */
    public function test_component_reads_reject_unknown_plugins(): void {
        foreach ([get_plugin_details::class, get_plugin_dependencies::class] as $operation) {
            try {
                $operation::execute('local_moodlia_missing_test_plugin');
                $this->fail('Unknown plugin component should have been rejected.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertStringContainsString('known Moodle plugin', $exception->getMessage());
            }
        }
    }
}
