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

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\book_tools;
use local_moodlia\operation\create_book_chapter as create_book_chapter_operation;

/**
 * External API adapter for create_book_chapter.
 */
class create_book_chapter extends external_api {
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

    public static function execute(
        int $course_id,
        int $module_id,
        string $title,
        string $content,
        int $content_format = FORMAT_HTML,
        bool $subchapter = false,
        ?int $after_chapter_id = null,
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
            'course_id' => $course_id,
            'module_id' => $module_id,
            'title' => $title,
            'content' => $content,
            'content_format' => $content_format,
            'subchapter' => $subchapter,
            'after_chapter_id' => $after_chapter_id,
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

    public static function execute_returns() {
        return get_book_chapters::chapter_returns();
    }

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
