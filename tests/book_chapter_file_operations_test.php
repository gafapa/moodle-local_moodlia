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
 * Book chapter file operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\backup_course;
use local_moodlia\operation\create_book_chapter;
use local_moodlia\operation\restore_course_backup;
use local_moodlia\operation\update_book_chapter;

/**
 * Exercises Book chapter editor files and native backup portability.
 */
final class book_chapter_file_operations_test extends \advanced_testcase {
    /**
     * Chapter creation stores an uploaded draft file under the chapter id.
     */
    public function test_create_book_chapter_attaches_draft_file_and_resolves_pluginfile_reference(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, $book, $cm] = $this->create_book();
        $draftitemid = $this->create_draft_file('chapter hero.jpg', 'Book image bytes');
        $content = '<p><img src="@@PLUGINFILE@@/chapter%20hero.jpg" alt="Chapter hero"></p>';

        $created = create_book_chapter::execute(
            (int) $course->id,
            (int) $cm->id,
            'Portable chapter',
            $content,
            FORMAT_HTML,
            false,
            null,
            false,
            'chapter hero.jpg',
            '',
            $draftitemid
        );

        $chapter = $DB->get_record('book_chapters', ['id' => $created['chapter_id']], '*', MUST_EXIST);
        $modulecontext = \context_module::instance((int) $cm->id);
        $file = get_file_storage()->get_file(
            $modulecontext->id,
            'mod_book',
            'chapter',
            (int) $chapter->id,
            '/',
            'chapter hero.jpg'
        );

        $this->assertNotFalse($file);
        $this->assertSame('Book image bytes', $file->get_content());
        $this->assertStringContainsString('@@PLUGINFILE@@/chapter%20hero.jpg', $chapter->content);
        $this->assertStringContainsString('/pluginfile.php/', $created['content']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $created['content']);
        $this->assertCount(1, $created['uploaded_files']);
        $this->assertSame('chapter hero.jpg', $created['uploaded_files'][0]['filename']);
        $this->assertSame((int) $chapter->id, (int) $file->get_itemid());
        $this->assertSame((int) $book->id, (int) $chapter->bookid);
    }

    /**
     * Chapter updates attach a new file without removing an existing file.
     */
    public function test_update_book_chapter_preserves_existing_files(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, , $cm] = $this->create_book();
        $firstdraft = $this->create_draft_file('existing.txt', 'Existing chapter file');
        $created = create_book_chapter::execute(
            (int) $course->id,
            (int) $cm->id,
            'Chapter',
            '<p><a href="@@PLUGINFILE@@/existing.txt">Existing</a></p>',
            FORMAT_HTML,
            false,
            null,
            false,
            'existing.txt',
            '',
            $firstdraft
        );
        $seconddraft = $this->create_draft_file('new image.png', 'New chapter image');
        $content = '<p><a href="@@PLUGINFILE@@/existing.txt">Existing</a>'
            . '<img src="@@PLUGINFILE@@/new%20image.png" alt="New image"></p>';

        $updated = update_book_chapter::execute(
            (int) $course->id,
            (int) $cm->id,
            (int) $created['chapter_id'],
            null,
            $content,
            FORMAT_HTML,
            null,
            null,
            'new image.png',
            '',
            $seconddraft
        );

        $modulecontext = \context_module::instance((int) $cm->id);
        $filestorage = get_file_storage();
        $this->assertNotFalse($filestorage->get_file(
            $modulecontext->id,
            'mod_book',
            'chapter',
            (int) $created['chapter_id'],
            '/',
            'existing.txt'
        ));
        $newfile = $filestorage->get_file(
            $modulecontext->id,
            'mod_book',
            'chapter',
            (int) $created['chapter_id'],
            '/',
            'new image.png'
        );
        $this->assertNotFalse($newfile);
        $this->assertSame('New chapter image', $newfile->get_content());
        $this->assertCount(1, $updated['uploaded_files']);
        $this->assertStringContainsString('/pluginfile.php/', $updated['content']);
    }

    /**
     * Chapter updates import every file from one authenticated draft item.
     */
    public function test_update_book_chapter_imports_multiple_draft_files(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, , $cm] = $this->create_book();
        $created = create_book_chapter::execute(
            (int) $course->id,
            (int) $cm->id,
            'Chapter',
            '<p>Initial</p>'
        );
        $draftitemid = $this->create_draft_file('hero image.jpg', 'Hero bytes');
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance((int) $USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/diagrams/',
            'filename' => 'flow.svg',
        ], '<svg></svg>');
        $content = '<p><img src="@@PLUGINFILE@@/hero%20image.jpg">'
            . '<img src="@@PLUGINFILE@@/diagrams/flow.svg"></p>';

        $updated = update_book_chapter::execute(
            (int) $course->id,
            (int) $cm->id,
            (int) $created['chapter_id'],
            null,
            $content,
            FORMAT_HTML,
            null,
            null,
            'hero image.jpg',
            '',
            $draftitemid
        );

        $this->assertCount(2, $updated['uploaded_files']);
        $this->assertCount(2, $updated['files']);
        $filenames = array_map(static fn(array $file): string => $file['filename'], $updated['files']);
        sort($filenames);
        $this->assertSame(['flow.svg', 'hero image.jpg'], $filenames);
    }

    /**
     * Native Moodle backup and restore retain Book chapter editor files.
     */
    public function test_book_chapter_file_survives_native_backup_restore(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        [$course, , $cm] = $this->create_book();
        $draftitemid = $this->create_draft_file('portable-book.png', 'Portable Book image');
        $content = '<p><img src="@@PLUGINFILE@@/portable-book.png" alt="Portable Book"></p>';
        create_book_chapter::execute(
            (int) $course->id,
            (int) $cm->id,
            'Backup chapter',
            $content,
            FORMAT_HTML,
            false,
            null,
            false,
            'portable-book.png',
            '',
            $draftitemid
        );

        $backup = backup_course::execute((int) $course->id, 'book-file-portability.mbz');
        $restored = restore_course_backup::execute(
            (int) $backup['file_id'],
            'new_course',
            0,
            1,
            'Restored Book file course',
            'restored-book-file-course'
        );
        $restoredcourse = get_course((int) $restored['course_id']);
        $restoredbooks = get_fast_modinfo($restoredcourse)->get_instances_of('book');
        $this->assertCount(1, $restoredbooks);
        $restoredcm = reset($restoredbooks);
        $restoredchapter = $DB->get_record(
            'book_chapters',
            ['bookid' => (int) $restoredcm->instance],
            '*',
            MUST_EXIST
        );
        $this->assertStringContainsString('@@PLUGINFILE@@/portable-book.png', $restoredchapter->content);

        $restoredcontext = \context_module::instance((int) $restoredcm->id);
        $restoredfile = get_file_storage()->get_file(
            $restoredcontext->id,
            'mod_book',
            'chapter',
            (int) $restoredchapter->id,
            '/',
            'portable-book.png'
        );
        $this->assertNotFalse($restoredfile);
        $this->assertSame('Portable Book image', $restoredfile->get_content());
        $this->assertSame((int) $restoredchapter->id, (int) $restoredfile->get_itemid());
    }

    /**
     * Create a Book activity for a test course.
     *
     * @return array
     */
    private function create_book(): array {
        $course = $this->getDataGenerator()->create_course();
        $book = $this->getDataGenerator()->create_module('book', [
            'course' => $course->id,
            'name' => 'MoodlIA Book',
        ]);
        $cm = get_coursemodule_from_instance('book', $book->id, $course->id, false, MUST_EXIST);

        return [$course, $book, $cm];
    }

    /**
     * Create a file in the current user's Moodle draft area.
     *
     * @param string $filename Filename.
     * @param string $content Content.
     * @return int
     */
    private function create_draft_file(string $filename, string $content): int {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance((int) $USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);

        return $draftitemid;
    }
}
