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
 * Get book chapters external function.
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
use local_moodlia\operation\get_book_chapters as get_book_chapters_operation;

/**
 * External API adapter for get_book_chapters.
 */
class get_book_chapters extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'include_content' => new external_value(PARAM_BOOL, 'Include rendered chapter content', VALUE_DEFAULT, true),
            'include_hidden' => new external_value(PARAM_BOOL, 'Include hidden chapters where allowed', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the external function.
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
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'include_content' => $includecontent,
            'include_hidden' => $includehidden,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'include_content' => $includecontent,
            'include_hidden' => $includehidden,
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

        return get_book_chapters_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (bool) $includecontent,
            (bool) $includehidden
        );
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
            'count' => new external_value(PARAM_INT, 'Number of returned chapters'),
            'chapters' => new external_multiple_structure(self::chapter_returns()),
        ]);
    }

    /**
     * Shared book chapter return structure.
     *
     * @return external_single_structure
     */
    public static function chapter_returns(): external_single_structure {
        return new external_single_structure([
            'chapter_id' => new external_value(PARAM_INT, 'Book chapter id'),
            'book_id' => new external_value(PARAM_INT, 'Book instance id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'title' => new external_value(PARAM_TEXT, 'Book chapter title'),
            'content' => new external_value(PARAM_RAW, 'Rendered chapter content'),
            'content_format' => new external_value(PARAM_INT, 'Moodle content format'),
            'page_number' => new external_value(PARAM_INT, 'Chapter page number'),
            'subchapter' => new external_value(PARAM_BOOL, 'Whether this chapter is a subchapter'),
            'hidden' => new external_value(PARAM_BOOL, 'Whether this chapter is hidden'),
            'parent_chapter_id' => new external_value(PARAM_INT, 'Parent chapter id or 0'),
            'previous_chapter_id' => new external_value(PARAM_INT, 'Previous chapter id or 0'),
            'next_chapter_id' => new external_value(PARAM_INT, 'Next chapter id or 0'),
            'url' => new external_value(PARAM_URL, 'Book chapter URL'),
        ]);
    }
}
