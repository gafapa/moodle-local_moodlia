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
 * Create book chapter external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\book_tools;
use local_moodlia\operation\create_book_chapter as create_book_chapter_operation;

/**
 * External API adapter for create_book_chapter.
 */
class create_book_chapter extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'title' => new external_value(PARAM_RAW, 'Book chapter title'),
            'content' => new external_value(PARAM_RAW, 'Book chapter content'),
            'content_format' => new external_value(PARAM_INT, 'Moodle content format', VALUE_DEFAULT, FORMAT_HTML),
            'subchapter' => new external_value(PARAM_BOOL, 'Whether the chapter is a subchapter', VALUE_DEFAULT, false),
            'after_chapter_id' => new external_value(PARAM_INT, 'Insert after this chapter id, 0 for first, null for last', VALUE_DEFAULT, null, NULL_ALLOWED),
            'hidden' => new external_value(PARAM_BOOL, 'Whether the chapter is hidden', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $title Title.
     * @param string $content Content.
     * @param int $contentformat Contentformat.
     * @param bool $subchapter Subchapter.
     * @param int|null $afterchapterid Afterchapterid.
     * @param bool $hidden Hidden.
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
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'subchapter' => $issubchapter,
            'after_chapter_id' => $afterchapterid,
            'hidden' => $ishidden,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'subchapter' => $subchapter,
            'after_chapter_id' => $afterchapterid,
            'hidden' => $hidden,
        ]);

        self::validate_write_context((int) $courseid, (int) $moduleid);

        return create_book_chapter_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $title,
            $content,
            (int) $contentformat,
            (bool) $issubchapter,
            $afterchapterid === null ? null : (int) $afterchapterid,
            (bool) $ishidden
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

    /**
     * Validate write context.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return void
     */
    public static function validate_write_context(int $courseid, int $moduleid): void {
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = book_tools::get_book_module($course, $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/book:edit', $modulecontext);
    }
}
