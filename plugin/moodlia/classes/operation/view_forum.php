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
 * View forum operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Registers a Moodle Forum view through Moodle Forum external APIs.
 */
class view_forum {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        $result = \mod_forum_external::view_forum((int) $cm->instance);

        return [
            'forum_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'viewed' => (bool) ($result['status'] ?? false),
            'warnings' => forum_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
