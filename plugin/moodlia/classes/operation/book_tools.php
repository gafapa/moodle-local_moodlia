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
 * Shared book helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle book operations.
 */
class book_tools {
    /**
     * Load Moodle book APIs.
     */
    public static function require_book_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');
        require_once($CFG->dirroot . '/mod/book/classes/external.php');
    }

    /**
     * Verify that a course module belongs to a book activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_book_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'book') {
            throw new \invalid_parameter_exception('module_id must reference a book activity.');
        }

        return $cm;
    }

    /**
     * Return book instance metadata for a course module.
     *
     * @param int $courseid Moodle course id.
     * @param \cm_info $cm Book course module.
     * @return \stdClass
     */
    public static function get_book_instance(int $courseid, \cm_info $cm): \stdClass {
        self::require_book_api();

        $result = \mod_book_external::get_books_by_courses([$courseid]);
        foreach (($result['books'] ?? []) as $book) {
            if ((int) ($book['coursemodule'] ?? 0) === (int) $cm->id) {
                return (object) [
                    'id' => (int) $cm->instance,
                    'name' => (string) ($book['name'] ?? $cm->name),
                    'numbering' => (int) ($book['numbering'] ?? BOOK_NUM_NUMBERS),
                    'customtitles' => (int) ($book['customtitles'] ?? 0),
                    'revision' => (int) ($book['revision'] ?? 0),
                ];
            }
        }

        throw new \invalid_parameter_exception('module_id must reference a visible book activity in the selected course.');
    }

    /**
     * Convert Moodle external warnings to the canonical response shape.
     *
     * @param array $warnings Moodle warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $items = [];
        foreach ($warnings as $warning) {
            $warning = (array) $warning;
            $items[] = [
                'item' => (string) ($warning['item'] ?? ''),
                'item_id' => (int) ($warning['itemid'] ?? $warning['item_id'] ?? 0),
                'warning_code' => (string) ($warning['warningcode'] ?? $warning['warning_code'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Return a canonical Book summary from Moodle's Book external API payload.
     *
     * @param array $book Moodle book payload.
     * @return array
     */
    public static function summary_to_response(array $book): array {
        $moduleid = (int) ($book['coursemodule'] ?? $book['cmid'] ?? 0);
        $url = $moduleid > 0 ? (new \moodle_url('/mod/book/view.php', ['id' => $moduleid]))->out(false) : '';

        return [
            'book_id' => (int) ($book['id'] ?? 0),
            'module_id' => $moduleid,
            'course_id' => (int) ($book['course'] ?? 0),
            'name' => (string) ($book['name'] ?? ''),
            'numbering' => (int) ($book['numbering'] ?? BOOK_NUM_NUMBERS),
            'custom_titles' => (bool) ($book['customtitles'] ?? false),
            'revision' => (int) ($book['revision'] ?? 0),
            'time_modified' => (int) ($book['timemodified'] ?? 0),
            'url' => $url,
        ];
    }

    /**
     * Return course books in the canonical response shape.
     *
     * @param \stdClass $course Moodle course.
     * @param array $result Moodle external result.
     * @return array
     */
    public static function course_books_to_response(\stdClass $course, array $result): array {
        $items = [];
        foreach (($result['books'] ?? []) as $book) {
            $summary = self::summary_to_response((array) $book);
            if ((int) $summary['course_id'] === (int) $course->id) {
                $items[] = $summary;
            }
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($items),
            'books' => $items,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return chapters for a book activity.
     *
     * @param \stdClass $book Book instance object.
     * @param \cm_info $cm Book course module.
     * @param bool $includecontent Include rendered chapter content.
     * @param bool $includehidden Include hidden chapters where allowed.
     * @return array
     */
    public static function get_chapters(\stdClass $book, \cm_info $cm, bool $includecontent, bool $includehidden): array {
        self::require_book_api();

        $context = \context_module::instance($cm->id);
        $canviewhidden = has_capability('mod/book:viewhiddenchapters', $context);
        $chapters = book_preload_chapters($book);
        $mapped = [];

        foreach ($chapters as $chapter) {
            if (!empty($chapter->hidden) && (!$includehidden || !$canviewhidden)) {
                continue;
            }
            $mapped[] = self::chapter_to_response($book, $cm, $context, $chapter, $includecontent);
        }

        return $mapped;
    }

    /**
     * Return a canonical chapter response.
     *
     * @param \stdClass $book Book instance object.
     * @param \cm_info $cm Book course module.
     * @param \context_module $context Module context.
     * @param \stdClass $chapter Book chapter.
     * @param bool $includecontent Include rendered chapter content.
     * @return array
     */
    public static function chapter_to_response(
        \stdClass $book,
        \cm_info $cm,
        \context_module $context,
        \stdClass $chapter,
        bool $includecontent
    ): array {
        $content = '';
        if ($includecontent) {
            $rewritten = file_rewrite_pluginfile_urls(
                (string) ($chapter->content ?? ''),
                'pluginfile.php',
                $context->id,
                'mod_book',
                'chapter',
                (int) $chapter->id
            );
            $content = format_text($rewritten, (int) ($chapter->contentformat ?? FORMAT_HTML), [
                'noclean' => true,
                'overflowdiv' => true,
                'context' => $context,
            ]);
        }

        $url = new \moodle_url('/mod/book/view.php', [
            'id' => (int) $cm->id,
            'chapterid' => (int) $chapter->id,
        ]);

        return [
            'chapter_id' => (int) $chapter->id,
            'book_id' => (int) $book->id,
            'module_id' => (int) $cm->id,
            'title' => (string) ($chapter->title ?? ''),
            'content' => $content,
            'content_format' => (int) ($chapter->contentformat ?? FORMAT_HTML),
            'page_number' => (int) ($chapter->pagenum ?? 0),
            'subchapter' => (bool) ($chapter->subchapter ?? false),
            'hidden' => (bool) ($chapter->hidden ?? false),
            'parent_chapter_id' => (int) ($chapter->parent ?? 0),
            'previous_chapter_id' => (int) ($chapter->prev ?? 0),
            'next_chapter_id' => (int) ($chapter->next ?? 0),
            'url' => $url->out(false),
        ];
    }
}
