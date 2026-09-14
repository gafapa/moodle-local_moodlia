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
 * Section operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\backup_course;
use local_moodlia\operation\create_section;
use local_moodlia\operation\restore_course_backup;
use local_moodlia\operation\section_tools;
use local_moodlia\operation\update_section;

/**
 * Exercises section summary format handling through Moodle's course APIs.
 */
final class section_operations_test extends \advanced_testcase {
    /**
     * Section creation stores and returns an explicit HTML summary format.
     */
    public function test_create_section_accepts_html_summary_format(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $created = create_section::execute(
            (int) $course->id,
            'HTML section',
            '<p>Rich summary</p>',
            'html'
        );
        $stored = $DB->get_record(
            'course_sections',
            ['id' => $created['section_id']],
            'id, summary, summaryformat',
            MUST_EXIST
        );

        $this->assertSame('<p>Rich summary</p>', $stored->summary);
        $this->assertSame((int) FORMAT_HTML, (int) $stored->summaryformat);
        $this->assertSame('html', $created['summary_format']);
    }

    /**
     * Section updates can replace a plain summary with an HTML summary.
     */
    public function test_update_section_accepts_html_summary_format(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $created = create_section::execute((int) $course->id, 'Section', 'Plain summary');

        $updated = update_section::execute(
            (int) $course->id,
            (int) $created['section_id'],
            null,
            null,
            '<p>Updated summary</p>',
            'html'
        );
        $stored = $DB->get_record(
            'course_sections',
            ['id' => $created['section_id']],
            'id, summary, summaryformat',
            MUST_EXIST
        );

        $this->assertSame('<p>Updated summary</p>', $stored->summary);
        $this->assertSame((int) FORMAT_HTML, (int) $stored->summaryformat);
        $this->assertSame('html', $updated['summary_format']);
    }

    /**
     * Section updates retain the current format and existing section files.
     */
    public function test_update_section_renders_html_details_and_retains_section_files(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $created = create_section::execute(
            (int) $course->id,
            'HTML section',
            '<p>Initial summary</p>',
            'html'
        );
        $coursecontext = \context_course::instance((int) $course->id);
        $filestorage = get_file_storage();
        $filestorage->create_file_from_string([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'section',
            'itemid' => (int) $created['section_id'],
            'filepath' => '/',
            'filename' => 'section-guide.txt',
        ], 'Section guide');
        $summary = '<details><summary>Teaching notes</summary><p>Read the '
            . '<a href="@@PLUGINFILE@@/section-guide.txt">section guide</a>.</p></details>';

        $updated = update_section::execute(
            (int) $course->id,
            (int) $created['section_id'],
            null,
            null,
            $summary
        );
        $stored = $DB->get_record(
            'course_sections',
            ['id' => $created['section_id']],
            'id, summary, summaryformat',
            MUST_EXIST
        );

        $this->assertSame($summary, $stored->summary);
        $this->assertSame((int) FORMAT_HTML, (int) $stored->summaryformat);
        $this->assertSame('html', $updated['summary_format']);
        $this->assertStringContainsString('<details>', $updated['summary']);
        $this->assertStringContainsString('<summary>Teaching notes</summary>', $updated['summary']);
        $this->assertStringContainsString('/pluginfile.php/', $updated['summary']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $updated['summary']);
        $this->assertNotFalse($filestorage->get_file(
            $coursecontext->id,
            'course',
            'section',
            (int) $created['section_id'],
            '/',
            'section-guide.txt'
        ));
    }

    /**
     * Section updates attach a user draft file without removing existing files.
     */
    public function test_update_section_attaches_draft_file_and_preserves_existing_files(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $created = create_section::execute((int) $course->id, 'HTML section', '', 'html');
        $coursecontext = \context_course::instance((int) $course->id);
        $filestorage = get_file_storage();
        $filestorage->create_file_from_string([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'section',
            'itemid' => (int) $created['section_id'],
            'filepath' => '/',
            'filename' => 'existing.txt',
        ], 'Existing file');
        $draftitemid = $this->create_draft_file('office-team-hero.jpg', 'JPEG image bytes');
        $summary = '<p><img src="@@PLUGINFILE@@/office-team-hero.jpg" alt="Office team"></p>';

        $updated = update_section::execute(
            (int) $course->id,
            (int) $created['section_id'],
            null,
            null,
            $summary,
            'html',
            null,
            'office-team-hero.jpg',
            '',
            $draftitemid
        );

        $this->assertCount(1, $updated['uploaded_files']);
        $this->assertSame('office-team-hero.jpg', $updated['uploaded_files'][0]['filename']);
        $uploadedfile = $filestorage->get_file(
            $coursecontext->id,
            'course',
            'section',
            (int) $created['section_id'],
            '/',
            'office-team-hero.jpg'
        );
        $this->assertNotFalse($uploadedfile);
        $this->assertSame('JPEG image bytes', $uploadedfile->get_content());
        $this->assertNotFalse($filestorage->get_file(
            $coursecontext->id,
            'course',
            'section',
            (int) $created['section_id'],
            '/',
            'existing.txt'
        ));
        $this->assertStringContainsString('/pluginfile.php/', $updated['summary']);
        $this->assertStringContainsString('office-team-hero.jpg', $updated['summary']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $updated['summary']);
    }

    /**
     * Native Moodle backup and restore retain an attached section summary file.
     */
    public function test_section_summary_file_survives_native_backup_restore(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $created = create_section::execute((int) $course->id, 'Portable section', '', 'html');
        $draftitemid = $this->create_draft_file('portable-image.jpg', 'Portable image bytes');
        $summary = '<p><img src="@@PLUGINFILE@@/portable-image.jpg" alt="Portable image"></p>';
        update_section::execute(
            (int) $course->id,
            (int) $created['section_id'],
            null,
            null,
            $summary,
            'html',
            null,
            'portable-image.jpg',
            '',
            $draftitemid
        );

        $backup = backup_course::execute((int) $course->id, 'section-file-portability.mbz');
        $restored = restore_course_backup::execute(
            (int) $backup['file_id'],
            'new_course',
            0,
            1,
            'Restored section file course',
            'restored-section-file-course'
        );
        $restoredcourse = get_course((int) $restored['course_id']);
        $restoredsection = get_fast_modinfo($restoredcourse)->get_section_info((int) $created['section_number']);
        $this->assertNotFalse($restoredsection);
        $stored = $DB->get_record(
            'course_sections',
            ['id' => (int) $restoredsection->id],
            'id, summary',
            MUST_EXIST
        );
        $this->assertStringContainsString('@@PLUGINFILE@@/portable-image.jpg', $stored->summary);

        $restoredcontext = \context_course::instance((int) $restoredcourse->id);
        $restoredfile = get_file_storage()->get_file(
            $restoredcontext->id,
            'course',
            'section',
            (int) $restoredsection->id,
            '/',
            'portable-image.jpg'
        );
        $this->assertNotFalse($restoredfile);
        $this->assertSame('Portable image bytes', $restoredfile->get_content());
        $rendered = section_tools::render_summary($restoredcourse, $restoredsection);
        $this->assertStringContainsString('/pluginfile.php/', $rendered);
        $this->assertStringContainsString('portable-image.jpg', $rendered);
    }

    /**
     * Updating only the format is rejected because no replacement summary was supplied.
     */
    public function test_update_section_rejects_format_without_summary(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $created = create_section::execute((int) $course->id, 'Section');

        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('summary is required when summary_format is provided.');
        update_section::execute(
            (int) $course->id,
            (int) $created['section_id'],
            null,
            null,
            null,
            'html'
        );
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
