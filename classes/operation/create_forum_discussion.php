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
 * Create forum discussion operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a discussion in a Moodle forum activity.
 */
class create_forum_discussion {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @param string $name Discussion name.
     * @param string $message Discussion message.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $name, string $message): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        $result = \mod_forum_external::add_discussion((int) $cm->instance, $name, $message, 0);
        $discussionid = (int) ($result['discussionid'] ?? 0);

        if ($discussionid <= 0) {
            throw new \moodle_exception('couldnotadd', 'forum');
        }

        $discussion = forum_tools::get_raw_discussion($cm, $discussionid);
        return forum_tools::discussion_to_response($course, $cm, $discussion);
    }
}
