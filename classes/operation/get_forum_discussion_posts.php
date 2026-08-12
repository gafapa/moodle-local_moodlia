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
 * List forum discussion posts operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists posts from a Moodle forum discussion.
 */
class get_forum_discussion_posts {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @param int $discussionid Moodle forum discussion id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $discussionid): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        forum_tools::get_raw_discussion($cm, $discussionid);

        $posts = [];
        foreach (forum_tools::get_raw_posts($discussionid) as $post) {
            $posts[] = forum_tools::post_to_response($course, $cm, $discussionid, $post);
        }

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'forum_id' => (int) $cm->instance,
            'discussion_id' => $discussionid,
            'posts' => $posts,
        ];
    }
}
