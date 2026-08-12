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
 * Delete book chapter external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\delete_book_chapter as delete_book_chapter_operation;

/**
 * External API adapter for delete_book_chapter.
 */
class delete_book_chapter extends external_api {
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
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $chapterid Chapterid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $chapterid): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
        ]);

        create_book_chapter::validate_write_context((int) $courseid, (int) $moduleid);

        return delete_book_chapter_operation::execute((int) $courseid, (int) $moduleid, (int) $chapterid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'book_id' => new external_value(PARAM_INT, 'Book instance id'),
            'chapter_id' => new external_value(PARAM_INT, 'Deleted chapter id'),
            'deleted' => new external_value(PARAM_BOOL, 'Whether the chapter was deleted'),
            'deleted_chapter_ids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Deleted chapter id')
            ),
        ]);
    }
}
