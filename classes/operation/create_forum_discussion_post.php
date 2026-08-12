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
 * Create forum discussion post operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a reply post in a Moodle forum discussion.
 */
class create_forum_discussion_post {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @param int $discussionid Moodle forum discussion id.
     * @param int|null $parentpostid Parent post id.
     * @param string $subject Reply subject.
     * @param string $message Reply message.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $discussionid,
        ?int $parentpostid,
        string $subject,
        string $message
    ): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        $discussion = forum_tools::get_raw_discussion($cm, $discussionid);
        $parentpostid = $parentpostid ?: (int) ($discussion['id'] ?? $discussion['firstpost'] ?? 0);

        if ($parentpostid <= 0) {
            throw new \moodle_exception('invalidparentpostid', 'forum');
        }

        $result = \mod_forum_external::add_discussion_post($parentpostid, $subject, $message, [], FORMAT_HTML);
        $postid = (int) ($result['postid'] ?? 0);

        if ($postid <= 0) {
            throw new \moodle_exception('couldnotadd', 'forum');
        }

        $post = forum_tools::get_raw_post($discussionid, $postid);
        return forum_tools::post_to_response($course, $cm, $discussionid, $post);
    }
}
