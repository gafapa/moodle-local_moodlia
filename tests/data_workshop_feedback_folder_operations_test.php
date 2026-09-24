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
 * Database, workshop, feedback, and folder write operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\allocate_workshop_submission;
use local_moodlia\operation\create_data_entry;
use local_moodlia\operation\create_data_field;
use local_moodlia\operation\create_feedback_item;
use local_moodlia\operation\create_workshop_submission;
use local_moodlia\operation\delete_data_entry;
use local_moodlia\operation\delete_data_field;
use local_moodlia\operation\delete_feedback_item;
use local_moodlia\operation\delete_folder_file;
use local_moodlia\operation\delete_workshop_submission;
use local_moodlia\operation\evaluate_workshop_assessment;
use local_moodlia\operation\set_workshop_grading_form;
use local_moodlia\operation\set_workshop_phase;
use local_moodlia\operation\update_data_entry;
use local_moodlia\operation\update_data_field;
use local_moodlia\operation\update_feedback_item;
use local_moodlia\operation\update_workshop_assessment;
use local_moodlia\operation\update_workshop_submission;
use local_moodlia\operation\upload_folder_file;
use local_moodlia\operation\view_feedback;

/**
 * Verifies Database fields and entries, Workshop phases and assessment, Feedback items, and Folder files.
 *
 * @covers \local_moodlia\operation\create_data_field
 * @covers \local_moodlia\operation\update_data_field
 * @covers \local_moodlia\operation\delete_data_field
 * @covers \local_moodlia\operation\create_data_entry
 * @covers \local_moodlia\operation\update_data_entry
 * @covers \local_moodlia\operation\delete_data_entry
 * @covers \local_moodlia\operation\set_workshop_grading_form
 * @covers \local_moodlia\operation\set_workshop_phase
 * @covers \local_moodlia\operation\create_workshop_submission
 * @covers \local_moodlia\operation\update_workshop_submission
 * @covers \local_moodlia\operation\delete_workshop_submission
 * @covers \local_moodlia\operation\allocate_workshop_submission
 * @covers \local_moodlia\operation\update_workshop_assessment
 * @covers \local_moodlia\operation\evaluate_workshop_assessment
 * @covers \local_moodlia\operation\view_feedback
 * @covers \local_moodlia\operation\create_feedback_item
 * @covers \local_moodlia\operation\update_feedback_item
 * @covers \local_moodlia\operation\delete_feedback_item
 * @covers \local_moodlia\operation\upload_folder_file
 * @covers \local_moodlia\operation\delete_folder_file
 */
final class data_workshop_feedback_folder_operations_test extends advanced_testcase {
    /**
     * Database fields and entries are created, updated, and deleted.
     */
    public function test_database_fields_and_entries(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $data = $this->getDataGenerator()->create_module('data', ['course' => $course->id]);

        $field = create_data_field::execute((int) $course->id, (int) $data->cmid, 'text', 'Title', 'Entry title', true);
        $fieldid = (int) $field['field_id'];
        $extra = create_data_field::execute((int) $course->id, (int) $data->cmid, 'text', 'Notes');
        update_data_field::execute((int) $course->id, (int) $data->cmid, (int) $extra['field_id'], 'Comments', 'Free text');
        $this->assertSame('Comments', $DB->get_field('data_fields', 'name', ['id' => $extra['field_id']]));
        delete_data_field::execute((int) $course->id, (int) $data->cmid, (int) $extra['field_id']);
        $this->assertFalse($DB->record_exists('data_fields', ['id' => $extra['field_id']]));

        $entry = create_data_entry::execute((int) $course->id, (int) $data->cmid, ['Title' => 'Hello']);
        $entryid = (int) $entry['entry_id'];
        $this->assertSame('Hello', $DB->get_field('data_content', 'content', ['recordid' => $entryid, 'fieldid' => $fieldid]));

        update_data_entry::execute((int) $course->id, (int) $data->cmid, $entryid, [(string) $fieldid => 'World']);
        $this->assertSame('World', $DB->get_field('data_content', 'content', ['recordid' => $entryid, 'fieldid' => $fieldid]));

        delete_data_entry::execute((int) $course->id, (int) $data->cmid, $entryid);
        $this->assertFalse($DB->record_exists('data_records', ['id' => $entryid]));
    }

    /**
     * A workshop runs from setup through submission, assessment, and evaluation.
     */
    public function test_workshop_lifecycle(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $author = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $reviewer = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setAdminUser();
        $workshop = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id, 'strategy' => 'accumulative']);

        set_workshop_grading_form::execute(
            (int) $course->id,
            (int) $workshop->cmid,
            'accumulative',
            json_encode(['dimensions' => [['description' => 'Clarity', 'grade' => 10, 'weight' => 1]]])
        );
        $dimensionid = (int) $DB->get_field('workshopform_accumulative', 'id', ['workshopid' => $workshop->id], MUST_EXIST);

        $phase = set_workshop_phase::execute((int) $course->id, (int) $workshop->cmid, 'submission');
        $this->assertSame('submission', $phase['phase']);

        $this->setUser($author);
        $submission = create_workshop_submission::execute((int) $course->id, (int) $workshop->cmid, 'Essay', '<p>Draft</p>');
        $submissionid = (int) $submission['submission_id'];
        update_workshop_submission::execute((int) $course->id, (int) $workshop->cmid, $submissionid, 'Final essay', '*Final*', 'markdown');
        $stored = $DB->get_record('workshop_submissions', ['id' => $submissionid], '*', MUST_EXIST);
        $this->assertSame('Final essay', $stored->title);
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $stored->contentformat);
        // Workshop allows one submission per author, so the one to delete comes from another student.
        $this->setUser($reviewer);
        $spare = create_workshop_submission::execute((int) $course->id, (int) $workshop->cmid, 'Spare', 'Spare');

        $this->setAdminUser();
        delete_workshop_submission::execute((int) $course->id, (int) $workshop->cmid, (int) $spare['submission_id']);
        $this->assertFalse($DB->record_exists('workshop_submissions', ['id' => $spare['submission_id']]));
        $allocation = allocate_workshop_submission::execute((int) $course->id, (int) $workshop->cmid, $submissionid, (int) $reviewer->id);
        $this->assertTrue($allocation['created']);
        $assessmentid = (int) $allocation['assessment_id'];
        set_workshop_phase::execute((int) $course->id, (int) $workshop->cmid, 'assessment');

        $this->setUser($reviewer);
        $assessed = update_workshop_assessment::execute((int) $course->id, (int) $workshop->cmid, $assessmentid, json_encode([
            ['name' => 'nodims', 'value' => 1],
            ['name' => 'gradeid__idx_0', 'value' => 0],
            ['name' => 'dimensionid__idx_0', 'value' => $dimensionid],
            ['name' => 'grade__idx_0', 'value' => 8],
            ['name' => 'peercomment__idx_0', 'value' => 'Clear'],
            ['name' => 'peercommentformat__idx_0', 'value' => FORMAT_HTML],
        ]));
        $this->assertTrue($assessed['updated'], json_encode($assessed['warnings']));
        $this->assertNotNull($DB->get_field('workshop_assessments', 'grade', ['id' => $assessmentid]));

        $this->setAdminUser();
        set_workshop_phase::execute((int) $course->id, (int) $workshop->cmid, 'evaluation');
        $evaluated = evaluate_workshop_assessment::execute(
            (int) $course->id,
            (int) $workshop->cmid,
            $assessmentid,
            'Fair review',
            'plain',
            1,
            '15'
        );
        $this->assertTrue($evaluated["evaluated"], json_encode($evaluated["warnings"]));
        $this->assertSame((int) FORMAT_PLAIN, (int) $DB->get_field('workshop_assessments', 'feedbackreviewerformat', ['id' => $assessmentid]));
    }

    /**
     * Feedback activities are viewed and their items created, updated, and deleted.
     */
    public function test_feedback_items(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $feedback = $this->getDataGenerator()->create_module('feedback', ['course' => $course->id]);

        $this->assertTrue(view_feedback::execute((int) $course->id, (int) $feedback->cmid)['viewed']);

        $item = create_feedback_item::execute((int) $course->id, (int) $feedback->cmid, 'textfield', 'Your name', '{"size":20}');
        $itemid = (int) $item['item_id'];
        $this->assertSame('20|255', $DB->get_field('feedback_item', 'presentation', ['id' => $itemid]));

        update_feedback_item::execute((int) $course->id, (int) $feedback->cmid, $itemid, 'Full name', null, null, 'name', true);
        $stored = $DB->get_record('feedback_item', ['id' => $itemid], '*', MUST_EXIST);
        $this->assertSame('Full name', $stored->name);
        $this->assertSame('name', $stored->label);
        $this->assertSame(1, (int) $stored->required);

        delete_feedback_item::execute((int) $course->id, (int) $feedback->cmid, $itemid);
        $this->assertFalse($DB->record_exists('feedback_item', ['id' => $itemid]));
    }

    /**
     * Folder files are uploaded from a draft area and deleted by id.
     */
    public function test_folder_files(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $course->id]);
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance((int) $USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'notes.txt',
        ], 'folder notes');

        $uploaded = upload_folder_file::execute((int) $course->id, (int) $folder->cmid, 'notes.txt', '', $draftitemid);
        $context = \context_module::instance($folder->cmid);
        $file = get_file_storage()->get_file($context->id, 'mod_folder', 'content', 0, '/', 'notes.txt');
        $this->assertNotFalse($file);
        $this->assertSame('folder notes', $file->get_content());

        delete_folder_file::execute((int) $course->id, (int) $folder->cmid, (int) $uploaded['file_id']);
        $this->assertFalse(get_file_storage()->get_file($context->id, 'mod_folder', 'content', 0, '/', 'notes.txt'));
    }
}
