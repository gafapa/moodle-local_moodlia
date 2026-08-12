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
 * View book external function.
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
use local_moodlia\operation\book_tools;
use local_moodlia\operation\view_book as view_book_operation;

/**
 * External API adapter for view_book.
 */
class view_book extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'chapter_id' => new external_value(PARAM_INT, 'Book chapter id. Zero selects the first visible chapter.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Book course module id.
     * @param int $chapterid Book chapter id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $chapterid = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        $course = get_course($courseid);
        $cm = book_tools::get_book_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/book:read', $modulecontext);

        return view_book_operation::execute((int) $courseid, (int) $moduleid, (int) $chapterid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'book_id' => new external_value(PARAM_INT, 'Book instance id'),
            'chapter_id' => new external_value(PARAM_INT, 'Viewed book chapter id, or 0 when the book has no visible chapters'),
            'viewed' => new external_value(PARAM_BOOL, 'Whether Moodle registered the view'),
            'warnings' => new external_multiple_structure(new external_single_structure([
                'item' => new external_value(PARAM_TEXT, 'Warning item'),
                'item_id' => new external_value(PARAM_INT, 'Warning item id'),
                'warning_code' => new external_value(PARAM_TEXT, 'Warning code'),
                'message' => new external_value(PARAM_TEXT, 'Warning message'),
            ])),
        ]);
    }
}
