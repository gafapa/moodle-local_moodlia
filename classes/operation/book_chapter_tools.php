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
 * Shared book chapter mutation helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Owns the audited Moodle Book chapter write boundary.
 *
 * Moodle Book does not expose a public external or component writer API for
 * chapters. This helper follows mod/book/edit.php, delete.php, and move.php:
 * validate Book ownership first, write only Book's own chapter records through
 * Moodle DML, maintain revision/page order, update files/tags where relevant,
 * and trigger Book chapter events.
 */
class book_chapter_tools {
    /**
     * Return a Book record for the selected course module.
     *
     * @param \cm_info $cm Book course module.
     * @return \stdClass
     */
    public static function get_book_record(\cm_info $cm): \stdClass {
        global $DB;

        book_tools::require_book_api();

        return $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);
    }

    /**
     * Return a Book chapter record and verify Book ownership.
     *
     * @param \stdClass $book Book record.
     * @param int $chapterid Book chapter id.
     * @return \stdClass
     */
    public static function get_chapter_record(\stdClass $book, int $chapterid): \stdClass {
        global $DB;

        if ($chapterid <= 0) {
            throw new \invalid_parameter_exception('chapter_id must be a positive integer.');
        }

        return $DB->get_record('book_chapters', ['id' => $chapterid, 'bookid' => $book->id], '*', MUST_EXIST);
    }

    /**
     * Create a chapter response from the latest Book structure.
     *
     * @param \stdClass $book Book record.
     * @param \cm_info $cm Book course module.
     * @param int $chapterid Book chapter id.
     * @return array
     */
    public static function chapter_response(\stdClass $book, \cm_info $cm, int $chapterid): array {
        $context = \context_module::instance($cm->id);
        $chapters = book_preload_chapters($book);
        if (empty($chapters[$chapterid])) {
            throw new \moodle_exception('invalidchapterid', 'book');
        }

        return book_tools::chapter_to_response($book, $cm, $context, $chapters[$chapterid], true);
    }

    /**
     * Create a Book chapter.
     *
     * @param \stdClass $book Book record.
     * @param \cm_info $cm Book course module.
     * @param string $title Chapter title.
     * @param string $content Chapter HTML content.
     * @param int $contentformat Moodle text format.
     * @param bool $subchapter Whether the chapter is a subchapter.
     * @param int|null $afterchapterid Chapter id after which to insert, or 0 for first.
     * @param bool $hidden Whether the chapter is hidden.
     * @return array
     */
    public static function create_chapter(
        \stdClass $book,
        \cm_info $cm,
        string $title,
        string $content,
        int $contentformat = FORMAT_HTML,
        bool $subchapter = false,
        ?int $afterchapterid = null,
        bool $hidden = false
    ): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        $title = self::normalise_title($title);
        self::validate_content($content);

        $pagenum = self::resolve_insert_page_number($book, $afterchapterid);
        if ($pagenum === 1 && $subchapter) {
            throw new \invalid_parameter_exception('The first book chapter cannot be a subchapter.');
        }

        $chapters = self::ordered_chapter_records($book);
        foreach ($chapters as $chapter) {
            if ((int) $chapter->pagenum >= $pagenum) {
                $chapter->pagenum = (int) $chapter->pagenum + 1;
                $DB->update_record('book_chapters', $chapter);
            }
        }

        $record = (object) [
            'bookid' => (int) $book->id,
            'pagenum' => $pagenum,
            'subchapter' => $subchapter ? 1 : 0,
            'title' => $title,
            'content' => $content,
            'contentformat' => $contentformat,
            'hidden' => $hidden ? 1 : 0,
            'timecreated' => time(),
            'timemodified' => time(),
            'importsrc' => '',
        ];

        $record->id = $DB->insert_record('book_chapters', $record);
        $record = self::get_chapter_record($book, (int) $record->id);
        self::bump_revision($book);
        book_preload_chapters($book);

        \mod_book\event\chapter_created::create_from_chapter($book, $context, $record)->trigger();

        return self::chapter_response($book, $cm, (int) $record->id);
    }

    /**
     * Update a Book chapter.
     *
     * @param \stdClass $book Book record.
     * @param \cm_info $cm Book course module.
     * @param int $chapterid Book chapter id.
     * @param string|null $title New title.
     * @param string|null $content New content.
     * @param int|null $contentformat New content format.
     * @param bool|null $subchapter New subchapter flag.
     * @param bool|null $hidden New hidden flag.
     * @return array
     */
    public static function update_chapter(
        \stdClass $book,
        \cm_info $cm,
        int $chapterid,
        ?string $title = null,
        ?string $content = null,
        ?int $contentformat = null,
        ?bool $subchapter = null,
        ?bool $hidden = null
    ): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        $chapter = self::get_chapter_record($book, $chapterid);
        $changed = false;

        if ($title !== null) {
            $chapter->title = self::normalise_title($title);
            $changed = true;
        }

        if ($content !== null) {
            self::validate_content($content);
            $chapter->content = $content;
            $chapter->contentformat = $contentformat ?? (int) ($chapter->contentformat ?? FORMAT_HTML);
            $changed = true;
        } else if ($contentformat !== null) {
            $chapter->contentformat = $contentformat;
            $changed = true;
        }

        if ($subchapter !== null) {
            if ((int) $chapter->pagenum === 1 && $subchapter) {
                throw new \invalid_parameter_exception('The first book chapter cannot be a subchapter.');
            }
            $chapter->subchapter = $subchapter ? 1 : 0;
            $changed = true;
        }

        if ($hidden !== null) {
            $chapter->hidden = $hidden ? 1 : 0;
            $changed = true;
        }

        if (!$changed) {
            throw new \invalid_parameter_exception('At least one chapter field is required.');
        }

        $chapter->timemodified = time();
        $DB->update_record('book_chapters', $chapter);
        self::bump_revision($book);
        book_preload_chapters($book);

        $chapter = self::get_chapter_record($book, $chapterid);
        \mod_book\event\chapter_updated::create_from_chapter($book, $context, $chapter)->trigger();

        return self::chapter_response($book, $cm, $chapterid);
    }

    /**
     * Move a Book chapter or top-level chapter block after another chapter.
     *
     * @param \stdClass $book Book record.
     * @param \cm_info $cm Book course module.
     * @param int $chapterid Chapter id to move.
     * @param int|null $afterchapterid Destination chapter id, 0 for first, or null for last.
     * @return array
     */
    public static function move_chapter(\stdClass $book, \cm_info $cm, int $chapterid, ?int $afterchapterid = null): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        $chapter = self::get_chapter_record($book, $chapterid);
        $ordered = array_values(self::ordered_chapter_records($book));
        if (count($ordered) <= 1) {
            return self::chapter_response($book, $cm, $chapterid);
        }

        $movingids = self::moving_block_ids($ordered, $chapter);
        if ($afterchapterid !== null && $afterchapterid > 0 && in_array($afterchapterid, $movingids, true)) {
            throw new \invalid_parameter_exception('after_chapter_id cannot point to the chapter block being moved.');
        }

        $remaining = array_values(array_filter($ordered, static function (\stdClass $item) use ($movingids): bool {
            return !in_array((int) $item->id, $movingids, true);
        }));
        $moving = array_values(array_filter($ordered, static function (\stdClass $item) use ($movingids): bool {
            return in_array((int) $item->id, $movingids, true);
        }));

        $insertat = count($remaining);
        if ($afterchapterid === 0) {
            $insertat = 0;
        } else if ($afterchapterid !== null) {
            $insertat = null;
            foreach ($remaining as $index => $item) {
                if ((int) $item->id === $afterchapterid) {
                    $insertat = $index + 1;
                    break;
                }
            }
            if ($insertat === null) {
                throw new \invalid_parameter_exception('after_chapter_id must belong to the same book.');
            }
        }

        array_splice($remaining, $insertat, 0, $moving);
        $pagenum = 1;
        foreach ($remaining as $item) {
            $item->pagenum = $pagenum++;
            $DB->update_record('book_chapters', $item);
            $updated = self::get_chapter_record($book, (int) $item->id);
            \mod_book\event\chapter_updated::create_from_chapter($book, $context, $updated)->trigger();
        }

        self::bump_revision($book);
        book_preload_chapters($book);

        return self::chapter_response($book, $cm, $chapterid);
    }

    /**
     * Delete a Book chapter using Moodle Book's delete semantics.
     *
     * @param \stdClass $book Book record.
     * @param \cm_info $cm Book course module.
     * @param int $chapterid Chapter id.
     * @return array
     */
    public static function delete_chapter(\stdClass $book, \cm_info $cm, int $chapterid): array {
        global $DB;

        $context = \context_module::instance($cm->id);
        $chapter = self::get_chapter_record($book, $chapterid);
        $deletedids = [];
        $fs = get_file_storage();

        if (empty($chapter->subchapter)) {
            foreach (self::ordered_chapter_records($book) as $candidate) {
                if ((int) $candidate->pagenum <= (int) $chapter->pagenum) {
                    continue;
                }
                if (empty($candidate->subchapter)) {
                    break;
                }
                self::delete_single_chapter($book, $context, $candidate, $fs);
                $deletedids[] = (int) $candidate->id;
            }
        }

        self::delete_single_chapter($book, $context, $chapter, $fs);
        $deletedids[] = (int) $chapter->id;

        self::bump_revision($book);
        book_preload_chapters($book);

        return [
            'course_id' => (int) $cm->course,
            'module_id' => (int) $cm->id,
            'book_id' => (int) $book->id,
            'chapter_id' => (int) $chapterid,
            'deleted' => true,
            'deleted_chapter_ids' => $deletedids,
        ];
    }

    /**
     * Return all chapter records ordered by page number.
     *
     * @param \stdClass $book Book record.
     * @return array
     */
    private static function ordered_chapter_records(\stdClass $book): array {
        global $DB;

        return array_values($DB->get_records('book_chapters', ['bookid' => $book->id], 'pagenum'));
    }

    /**
     * Resolve insert page number.
     *
     * @param \stdClass $book Book record.
     * @param int|null $afterchapterid Chapter after which to insert, null for append.
     * @return int
     */
    private static function resolve_insert_page_number(\stdClass $book, ?int $afterchapterid): int {
        $chapters = self::ordered_chapter_records($book);
        if ($afterchapterid === null) {
            return count($chapters) + 1;
        }
        if ($afterchapterid === 0) {
            return 1;
        }

        foreach ($chapters as $chapter) {
            if ((int) $chapter->id === $afterchapterid) {
                return (int) $chapter->pagenum + 1;
            }
        }

        throw new \invalid_parameter_exception('after_chapter_id must belong to the same book.');
    }

    /**
     * Return the ids that move together.
     *
     * @param array $ordered Ordered chapter records.
     * @param \stdClass $chapter Selected chapter.
     * @return array
     */
    private static function moving_block_ids(array $ordered, \stdClass $chapter): array {
        $ids = [(int) $chapter->id];
        if (!empty($chapter->subchapter)) {
            return $ids;
        }

        $collect = false;
        foreach ($ordered as $candidate) {
            if ((int) $candidate->id === (int) $chapter->id) {
                $collect = true;
                continue;
            }
            if (!$collect) {
                continue;
            }
            if (empty($candidate->subchapter)) {
                break;
            }
            $ids[] = (int) $candidate->id;
        }

        return $ids;
    }

    /**
     * Delete one chapter record and its files/tags/events.
     *
     * @param \stdClass $book Book record.
     * @param \context_module $context Book module context.
     * @param \stdClass $chapter Chapter record.
     * @param \file_storage $fs Moodle file storage.
     */
    private static function delete_single_chapter(
        \stdClass $book,
        \context_module $context,
        \stdClass $chapter,
        \file_storage $fs
    ): void {
        global $DB;

        \core_tag_tag::remove_all_item_tags('mod_book', 'book_chapters', $chapter->id);
        $fs->delete_area_files($context->id, 'mod_book', 'chapter', $chapter->id);
        $DB->delete_records('book_chapters', ['id' => $chapter->id]);
        \mod_book\event\chapter_deleted::create_from_chapter($book, $context, $chapter)->trigger();
    }

    /**
     * Increment Book revision.
     *
     * @param \stdClass $book Book record.
     */
    private static function bump_revision(\stdClass $book): void {
        global $DB;

        $book->revision = (int) ($book->revision ?? 0) + 1;
        $DB->set_field('book', 'revision', $book->revision, ['id' => $book->id]);
    }

    /**
     * Validate and normalize chapter title.
     *
     * @param string $title Raw title.
     * @return string
     */
    private static function normalise_title(string $title): string {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title is required.');
        }
        if (\core_text::strlen($title) > 1333) {
            throw new \invalid_parameter_exception('title must not exceed 1333 characters.');
        }

        return $title;
    }

    /**
     * Validate chapter content.
     *
     * @param string $content Raw content.
     */
    private static function validate_content(string $content): void {
        if (trim($content) === '') {
            throw new \invalid_parameter_exception('content is required.');
        }
    }
}
