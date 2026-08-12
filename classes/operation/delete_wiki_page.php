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
 * Delete wiki page operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Deletes a Moodle wiki page through Moodle wiki APIs.
 */
class delete_wiki_page {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $pageid Pageid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $pageid): array {
        wiki_tools::require_wiki_api();

        $course = course_tools::get_course($courseid);
        $cm = wiki_tools::get_wiki_module($course, $moduleid);
        $page = wiki_tools::get_page($cm, $pageid);
        $subwikiid = (int) ($page['subwikiid'] ?? 0);

        if ($subwikiid <= 0) {
            throw new \invalid_parameter_exception('page_id must reference a valid wiki subwiki page.');
        }

        $context = \context_module::instance($cm->id);
        wiki_delete_pages($context, $pageid, $subwikiid);

        $remaining = wiki_tools::get_pages($cm, -1, 0, 'title', 'ASC', false);
        foreach ($remaining as $remainingpage) {
            if ((int) ($remainingpage['id'] ?? 0) === $pageid) {
                throw new \moodle_exception('error', 'local_moodlia', '', null, 'Could not delete the wiki page.');
            }
        }

        return [
            'deleted' => true,
            'id' => (int) $pageid,
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'wiki_id' => (int) $cm->instance,
            'subwiki_id' => $subwikiid,
            'title' => (string) ($page['title'] ?? ''),
        ];
    }
}
