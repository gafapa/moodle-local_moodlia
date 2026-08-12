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
 * Delete forum discussion post operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Deletes a post from a Moodle forum discussion through Moodle forum APIs.
 */
class delete_forum_discussion_post {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @param int $discussionid Moodle forum discussion id.
     * @param int $postid Moodle forum post id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $discussionid, int $postid): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        forum_tools::get_raw_discussion($cm, $discussionid);
        $post = forum_tools::get_raw_post($discussionid, $postid);

        $result = \mod_forum_external::delete_post((int) $post['id']);
        if (!($result['status'] ?? false)) {
            throw new \moodle_exception('cannotdeletepost', 'forum');
        }

        return [
            'deleted' => true,
            'id' => (int) $post['id'],
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'discussion_id' => (int) $discussionid,
        ];
    }
}
