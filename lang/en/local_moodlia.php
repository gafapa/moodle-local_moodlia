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

$string['assignmentupdatefailed'] = 'The assignment could not be updated. Correlation ID: {$a}';
$string['cannotdisablemoodlia'] = 'MoodlIA cannot change its own enabled state.';
$string['duplicatemodulenotcreated'] = 'The Moodle module duplicate could not be created.';
$string['eventassignmentupdatefailed'] = 'Assignment update database failure recorded';
$string['moodlia:manageplugins'] = 'Inspect and manage Moodle plugins through MoodlIA';
$string['moodlia:useapi'] = 'Use MoodlIA external functions';
$string['plugindependenciesunsatisfied'] = 'Plugin {$a} cannot be enabled because one or more requirements are not satisfied.';
$string['plugindependentsenabled'] = 'Plugin {$a->plugin} cannot be disabled while dependent plugin {$a->dependent} is enabled.';
$string['pluginname'] = 'MoodlIA';
$string['webmcpenabled'] = 'Enable WebMCP browser tools';
$string['webmcpenabled_desc'] = 'Expose MoodlIA tools to compatible browser agents for signed-in users with the MoodlIA API capability. Tools use the current user session and native Moodle permissions. The current page is context, not a restriction to one course. Agents can navigate this tab to show their work. Moodle does not choose or contact an AI provider; configure the agent according to your institution data policy.';
$string['webmcpdisabled'] = 'MoodlIA WebMCP browser tools are disabled.';
$string['webmcpfailed'] = 'The MoodlIA WebMCP request could not be completed.';
$string['webmcpoperationfailed'] = 'The Moodle operation could not be completed: {$a}';
$string['pluginstateunsupported'] = 'Plugin {$a} does not expose a supported enable or disable operation.';
$string['pluginstateverificationfailed'] = 'Moodle did not retain the requested enabled state for plugin {$a}.';
$string['privacy:metadata'] = 'The MoodlIA plugin does not store personal data in plugin-owned storage.';
$string['restoreprecheckfailed'] = 'The Moodle backup cannot be restored: {$a}';
