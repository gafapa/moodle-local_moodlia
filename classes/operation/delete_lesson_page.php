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
 * Delete Lesson page operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes a Moodle Lesson page through Moodle Lesson component APIs.
 */
class delete_lesson_page {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Lesson course module id.
     * @param int $pageid Lesson page id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $pageid): array {
        lesson_tools::require_lesson_api();

        $course = course_tools::get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, $moduleid);
        $lesson = lesson_tools::get_lesson_object($course, $cm);
        lesson_tools::prepare_page_context($course, $cm);
        $page = lesson_tools::get_page($lesson, $cm, $pageid);

        $page->delete();
        rebuild_course_cache($course->id, true);

        return [
            'deleted' => true,
            'id' => $pageid,
        ];
    }
}
