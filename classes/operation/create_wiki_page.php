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
 * Create wiki page operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle wiki page through Moodle external APIs.
 */
class create_wiki_page {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $title Title.
     * @param string $content Content.
     * @param string $contentformat Contentformat.
     * @param int $groupid Groupid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $title,
        string $content,
        string $contentformat = 'html',
        int $groupid = -1,
        int $userid = 0
    ): array {
        wiki_tools::require_wiki_api();

        $course = course_tools::get_course($courseid);
        $cm = wiki_tools::get_wiki_module($course, $moduleid);
        $contentformat = wiki_tools::validate_content_format($contentformat);

        $result = \mod_wiki_external::new_page($title, $content, $contentformat, null, (int) $cm->instance, $userid, $groupid);
        $page = wiki_tools::get_page($cm, (int) $result['pageid']);

        return wiki_tools::page_to_response($cm, $page);
    }
}
