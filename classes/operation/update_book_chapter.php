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
 * Update book chapter operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle Book chapter.
 */
class update_book_chapter {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $chapterid Chapterid.
     * @param string|null $title Title.
     * @param string|null $content Content.
     * @param int|null $contentformat Contentformat.
     * @param bool|null $subchapter Subchapter.
     * @param bool|null $hidden Hidden.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $chapterid,
        ?string $title = null,
        ?string $content = null,
        ?int $contentformat = null,
        ?bool $subchapter = null,
        ?bool $hidden = null
    ): array {
        book_tools::require_book_api();

        $course = course_tools::get_course($courseid);
        $cm = book_tools::get_book_module($course, $moduleid);
        $book = book_chapter_tools::get_book_record($cm);

        return book_chapter_tools::update_chapter(
            $book,
            $cm,
            $chapterid,
            $title,
            $content,
            $contentformat,
            $subchapter,
            $hidden
        );
    }
}
