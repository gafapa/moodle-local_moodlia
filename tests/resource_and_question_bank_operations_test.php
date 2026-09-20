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
 * Resource replacement and shared question bank tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\backup_course;
use local_moodlia\operation\create_question_category;
use local_moodlia\operation\restore_course_backup;
use local_moodlia\operation\update_resource;

/**
 * Exercises identity-preserving resource updates and course-shared question banks.
 */
final class resource_and_question_bank_operations_test extends \advanced_testcase {
    /**
     * Replacing a resource file retains the course module and instance identifiers.
     */
    public function test_update_resource_replaces_file_without_recreating_module(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $resource = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'name' => 'Original resource',
            'intro' => '<p>Original description</p>',
            'introformat' => FORMAT_HTML,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
        ], [
            'filename' => 'original.pdf',
            'filecontent' => 'Original PDF content',
        ]);
        $cm = get_coursemodule_from_instance('resource', $resource->id, $course->id, false, MUST_EXIST);
        $cmbefore = $DB->get_record('course_modules', ['id' => $cm->id], '*', MUST_EXIST);
        $draftitemid = $this->create_draft_file('replacement.pdf', 'Replacement PDF content');

        $updated = update_resource::execute(
            (int) $course->id,
            (int) $cm->id,
            'replacement.pdf',
            '',
            $draftitemid,
            'Updated resource',
            '<p>Updated description</p>',
            'html'
        );

        $cmafter = $DB->get_record('course_modules', ['id' => $cm->id], '*', MUST_EXIST);
        $stored = $DB->get_record('resource', ['id' => $resource->id], '*', MUST_EXIST);
        $modulecontext = \context_module::instance((int) $cm->id);
        $files = get_file_storage()->get_area_files(
            $modulecontext->id,
            'mod_resource',
            'content',
            0,
            'sortorder, id',
            false
        );

        $this->assertSame((int) $cm->id, (int) $updated['course_module_id']);
        $this->assertSame((int) $cm->id, (int) $updated['module_id']);
        $this->assertSame((int) $resource->id, (int) $updated['instance_id']);
        $this->assertSame((int) $cmbefore->instance, (int) $cmafter->instance);
        $this->assertSame((int) $cmbefore->completion, (int) $cmafter->completion);
        $this->assertSame((int) $cmbefore->completionview, (int) $cmafter->completionview);
        $this->assertSame('Updated resource', $stored->name);
        $this->assertSame('<p>Updated description</p>', $stored->intro);
        $this->assertCount(1, $files);
        $replacement = reset($files);
        $this->assertSame('replacement.pdf', $replacement->get_filename());
        $this->assertSame('Replacement PDF content', $replacement->get_content());
        $this->assertCount(1, $updated['files']);
        $this->assertSame('replacement.pdf', $updated['files'][0]['filename']);
    }

    /**
     * Native backup and restore retain the replaced resource and shared question bank.
     */
    public function test_backup_restores_replaced_resource_and_shared_question_bank(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $resource = $this->getDataGenerator()->create_module('resource', [
            'course' => $course->id,
            'name' => 'Portable resource',
        ], [
            'filename' => 'old.pdf',
            'filecontent' => 'Old resource content',
        ]);
        $cm = get_coursemodule_from_instance('resource', $resource->id, $course->id, false, MUST_EXIST);
        update_resource::execute(
            (int) $course->id,
            (int) $cm->id,
            'portable.pdf',
            '',
            $this->create_draft_file('portable.pdf', 'Portable resource content')
        );

        $category = create_question_category::execute(
            (int) $course->id,
            'Shared portable questions',
            null,
            'Questions stored in the shared course bank.',
            'course_shared'
        );
        $this->assertSame('course_shared', $category['bank_scope']);
        $hasstandalonequestionbanks = is_readable($CFG->dirroot . '/mod/qbank/lib.php');
        if ($hasstandalonequestionbanks) {
            $this->assertNotNull($category['question_bank_module_id']);
        } else {
            $this->assertNull($category['question_bank_module_id']);
            $this->assertSame(
                \context_course::instance((int) $course->id)->id,
                (int) $category['context_id']
            );
        }

        $backup = backup_course::execute((int) $course->id, 'resource-qbank-portability.mbz');
        $restored = restore_course_backup::execute(
            (int) $backup['file_id'],
            'new_course',
            0,
            1,
            'Restored resource and question bank course',
            'restored-resource-qbank-course'
        );
        $restoredcourse = get_course((int) $restored['course_id']);
        $modinfo = get_fast_modinfo($restoredcourse);

        $resources = $modinfo->get_instances_of('resource');
        $this->assertCount(1, $resources);
        $restoredresource = reset($resources);
        $resourcecontext = \context_module::instance((int) $restoredresource->id);
        $restoredfile = get_file_storage()->get_file(
            $resourcecontext->id,
            'mod_resource',
            'content',
            0,
            '/',
            'portable.pdf'
        );
        $this->assertNotFalse($restoredfile);
        $this->assertSame('Portable resource content', $restoredfile->get_content());

        if ($hasstandalonequestionbanks) {
            $qbanks = $modinfo->get_instances_of('qbank');
            $this->assertCount(1, $qbanks);
            $restoredqbank = reset($qbanks);
            $questionbankcontext = \context_module::instance((int) $restoredqbank->id);
        } else {
            $questionbankcontext = \context_course::instance((int) $restoredcourse->id);
        }
        $this->assertTrue($DB->record_exists('question_categories', [
            'contextid' => $questionbankcontext->id,
            'name' => 'Shared portable questions',
        ]));
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
