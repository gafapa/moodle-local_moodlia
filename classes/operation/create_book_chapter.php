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
 * Create book chapter operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle Book chapter.
 */
class create_book_chapter {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Book course module id.
     * @param string $title Chapter title.
     * @param string $content Chapter content.
     * @param int $contentformat Moodle content format.
     * @param bool $subchapter Whether the chapter is a subchapter.
     * @param int|null $afterchapterid Chapter after which to insert.
     * @param bool $hidden Whether the chapter is hidden.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $title,
        string $content,
        int $contentformat = FORMAT_HTML,
        bool $subchapter = false,
        ?int $afterchapterid = null,
        bool $hidden = false
    ): array {
        book_tools::require_book_api();

        $course = course_tools::get_course($courseid);
        $cm = book_tools::get_book_module($course, $moduleid);
        $book = book_chapter_tools::get_book_record($cm);

        return book_chapter_tools::create_chapter(
            $book,
            $cm,
            $title,
            $content,
            $contentformat,
            $subchapter,
            $afterchapterid,
            $hidden
        );
    }
}
