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
 * WebMCP page output hook.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\webmcp;

/**
 * Loads the optional WebMCP adapter without changing Moodle page content.
 */
final class output {
    /**
     * Load the adapter for authenticated MoodlIA users on normal HTML pages.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook Hook.
     */
    public static function before_body(\core\hook\output\before_standard_top_of_body_html_generation $hook): void {
        global $PAGE;
        if (
            !get_config('local_moodlia', 'webmcp_enabled') || !isloggedin() || isguestuser() ||
            !has_capability('local/moodlia:useapi', \context_system::instance())
        ) {
            return;
        }
        $courseid = (int) ($PAGE->course->id ?? 0);
        $moduleid = $PAGE->cm ? (int) $PAGE->cm->id : 0;
        $PAGE->requires->js_call_amd('local_moodlia/webmcp', 'init', [[
            'endpoint' => (new \moodle_url('/local/moodlia/webmcp.php'))->out(false),
            'context' => ['course_id' => $courseid, 'module_id' => $moduleid],
        ]]);
    }
}
