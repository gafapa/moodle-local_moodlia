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

use local_moodlia\operation\create_section;
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
}
