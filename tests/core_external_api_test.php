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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Qualification of referenced native external methods.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

/**
 * Verifies static activity external references against the installed core.
 */
final class core_external_api_test extends \advanced_testcase {
    /**
     * Every referenced native activity external method exists on this core.
     */
    public function test_referenced_activity_methods_exist(): void {
        global $CFG;

        $methods = [];
        foreach (glob($CFG->dirroot . '/local/moodlia/classes/operation/*.php') as $path) {
            preg_match_all('/\\\\mod_(\w+)_external::(\w+)\s*\(/', file_get_contents($path), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $methods[$match[1]][$match[2]] = true;
            }
        }
        $this->assertNotEmpty($methods);
        foreach ($methods as $module => $references) {
            foreach (['classes/external.php', 'externallib.php'] as $relative) {
                $path = $CFG->dirroot . '/mod/' . $module . '/' . $relative;
                if (is_readable($path)) {
                    require_once($path);
                }
            }
            $classname = '\\mod_' . $module . '_external';
            $this->assertTrue(class_exists($classname), $classname);
            foreach (array_keys($references) as $method) {
                $this->assertTrue(method_exists($classname, $method), $classname . '::' . $method);
            }
        }
    }
}
