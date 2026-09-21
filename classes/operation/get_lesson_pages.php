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
 * Get Lesson pages operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Lesson pages through Moodle Lesson external APIs.
 */
class get_lesson_pages {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $password Password.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $password = ''): array {
        lesson_tools::require_lesson_api();

        $course = course_tools::get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, $moduleid);
        $result = \mod_lesson_external::get_pages((int) $cm->instance, $password);
        $lesson = lesson_tools::get_lesson_object($course, $cm);
        lesson_tools::prepare_page_context($course, $cm);
        $pages = [];
        foreach (($result['pages'] ?? []) as $pageentry) {
            $pageentry = (array) $pageentry;
            $page = (array) ($pageentry['page'] ?? []);
            $pageid = (int) ($page['id'] ?? 0);
            if ($pageid > 0) {
                $pages[] = lesson_tools::page_to_response($cm, lesson_tools::get_page($lesson, $cm, $pageid));
            }
        }

        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'count' => count($pages),
            'pages' => $pages,
            'warnings' => lesson_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
