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
 * Update book chapter external function.
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
use local_moodlia\operation\update_book_chapter as update_book_chapter_operation;

/**
 * External API adapter for update_book_chapter.
 */
class update_book_chapter extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Book course module id'),
            'chapter_id' => new external_value(PARAM_INT, 'Book chapter id'),
            'title' => new external_value(PARAM_RAW, 'Book chapter title', VALUE_DEFAULT, null, NULL_ALLOWED),
            'content' => new external_value(PARAM_RAW, 'Book chapter content', VALUE_DEFAULT, null, NULL_ALLOWED),
            'content_format' => new external_value(PARAM_INT, 'Moodle content format', VALUE_DEFAULT, null, NULL_ALLOWED),
            'subchapter' => new external_value(PARAM_BOOL, 'Whether the chapter is a subchapter', VALUE_DEFAULT, null, NULL_ALLOWED),
            'hidden' => new external_value(PARAM_BOOL, 'Whether the chapter is hidden', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    public static function execute(
        int $course_id,
        int $module_id,
        int $chapter_id,
        ?string $title = null,
        ?string $content = null,
        ?int $content_format = null,
        ?bool $subchapter = null,
        ?bool $hidden = null
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'chapter_id' => $chapterid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'subchapter' => $issubchapter,
            'hidden' => $ishidden,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'chapter_id' => $chapter_id,
            'title' => $title,
            'content' => $content,
            'content_format' => $content_format,
            'subchapter' => $subchapter,
            'hidden' => $hidden,
        ]);

        create_book_chapter::validate_write_context((int) $courseid, (int) $moduleid);

        return update_book_chapter_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $chapterid,
            $title,
            $content,
            $contentformat === null ? null : (int) $contentformat,
            $issubchapter === null ? null : (bool) $issubchapter,
            $ishidden === null ? null : (bool) $ishidden
        );
    }

    public static function execute_returns() {
        return get_book_chapters::chapter_returns();
    }
}
