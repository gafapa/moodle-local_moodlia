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
 * English language strings.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cannotdisablemoodlia'] = 'MoodlIA cannot change its own enabled state.';
$string['duplicatemodulenotcreated'] = 'The Moodle module duplicate could not be created.';
$string['moodlia:manageplugins'] = 'Inspect and manage Moodle plugins through MoodlIA';
$string['moodlia:useapi'] = 'Use MoodlIA external functions';
$string['plugindependenciesunsatisfied'] = 'Plugin {$a} cannot be enabled because one or more requirements are not satisfied.';
$string['plugindependentsenabled'] = 'Plugin {$a->plugin} cannot be disabled while dependent plugin {$a->dependent} is enabled.';
$string['pluginname'] = 'MoodlIA';
$string['pluginstateunsupported'] = 'Plugin {$a} does not expose a supported enable or disable operation.';
$string['pluginstateverificationfailed'] = 'Moodle did not retain the requested enabled state for plugin {$a}.';
$string['privacy:metadata'] = 'The MoodlIA plugin does not store personal data in plugin-owned storage.';
