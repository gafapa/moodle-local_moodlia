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
 * Get book chapters operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Lists Moodle book chapters through Moodle book APIs.
 */
class get_book_chapters {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Book course module id.
     * @param bool $includecontent Include rendered chapter content.
     * @param bool $includehidden Include hidden chapters where allowed.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        bool $includecontent = true,
        bool $includehidden = false
    ): array {
        book_tools::require_book_api();

        $course = course_tools::get_course($courseid);
        $cm = book_tools::get_book_module($course, $moduleid);
        $book = book_tools::get_book_instance($courseid, $cm);
        $chapters = book_tools::get_chapters($book, $cm, $includecontent, $includehidden);

        return [
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'book_id' => (int) $book->id,
            'count' => count($chapters),
            'chapters' => $chapters,
        ];
    }
}
