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
 * Move book chapter external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\move_book_chapter as move_book_chapter_operation;

/**
 * External API adapter for move_book_chapter.
 */
class move_book_chapter extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'chapter_id' => new external_value(PARAM_INT, 'Book chapter id'),
            'after_chapter_id' => new external_value(PARAM_INT, 'Move after this chapter id, 0 for first, null for last', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $moduleid Module id.
     * @param int $chapterid Chapter id.
     * @param int|null $afterchapterid After chapter id.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $chapterid,
        ?int $afterchapterid = null
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
            'after_chapter_id' => $afterchapterid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
            'after_chapter_id' => $afterchapterid,
        ]);

        create_book_chapter::validate_write_context((int) $courseid, (int) $moduleid);

        return move_book_chapter_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $chapterid,
            $afterchapterid === null ? null : (int) $afterchapterid
        );
    }

    /**
     * Execute returns.
     *
     * @return mixed
     */
    public static function execute_returns() {
        return get_book_chapters::chapter_returns();
    }
}
