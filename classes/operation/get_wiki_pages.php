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
 * Get wiki pages operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists Moodle wiki pages through Moodle external APIs.
 */
class get_wiki_pages {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $groupid Groupid.
     * @param int $userid Userid.
     * @param string $sortby Sortby.
     * @param string $sortdirection Sortdirection.
     * @param bool $includecontent Includecontent.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $groupid = -1,
        int $userid = 0,
        string $sortby = 'title',
        string $sortdirection = 'ASC',
        bool $includecontent = true
    ): array {
        wiki_tools::require_wiki_api();

        $course = course_tools::get_course($courseid);
        $cm = wiki_tools::get_wiki_module($course, $moduleid);
        $pages = wiki_tools::get_pages($cm, $groupid, $userid, $sortby, $sortdirection, $includecontent);
        $mapped = array_map(static fn(array $page): array => wiki_tools::page_to_response($cm, $page), $pages);

        return [
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'wiki_id' => (int) $cm->instance,
            'count' => count($mapped),
            'pages' => $mapped,
        ];
    }
}
