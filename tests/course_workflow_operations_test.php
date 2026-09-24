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
 * Course blueprint, enrolment sync, backup file, and completion repair operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

use advanced_testcase;
use local_moodlia\operation\apply_course_blueprint;
use local_moodlia\operation\copy_course_structure;
use local_moodlia\operation\create_course_from_blueprint;
use local_moodlia\operation\delete_course_backup_file;
use local_moodlia\operation\export_course_blueprint;
use local_moodlia\operation\repair_course_completion;
use local_moodlia\operation\sync_course_enrolments;
use local_moodlia\operation\upload_course_backup;

/**
 * Verifies the course workflow write operations.
 *
 * @covers \local_moodlia\operation\create_course_from_blueprint
 * @covers \local_moodlia\operation\apply_course_blueprint
 * @covers \local_moodlia\operation\copy_course_structure
 * @covers \local_moodlia\operation\sync_course_enrolments
 * @covers \local_moodlia\operation\upload_course_backup
 * @covers \local_moodlia\operation\delete_course_backup_file
 * @covers \local_moodlia\operation\repair_course_completion
 */
final class course_workflow_operations_test extends advanced_testcase {
    /**
     * Exported blueprints create new courses and are applied or copied into existing ones.
     */
    public function test_blueprint_create_apply_and_copy(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $source = $this->getDataGenerator()->create_course(['fullname' => 'Source', 'numsections' => 2]);
        $this->getDataGenerator()->create_module('page', ['course' => $source->id, 'section' => 1, 'name' => 'Welcome page']);
        $blueprint = json_decode(export_course_blueprint::execute((int) $source->id)['blueprint_json'], true);

        $blueprint['course']['fullname'] = 'From blueprint';
        $blueprint['course']['shortname'] = 'from-blueprint';
        $created = create_course_from_blueprint::execute($blueprint);
        $this->assertSame('From blueprint', $DB->get_field('course', 'fullname', ['id' => $created['course_id']]));
        $this->assertTrue($DB->record_exists('page', ['course' => $created['course_id'], 'name' => 'Welcome page']));

        $applytarget = $this->getDataGenerator()->create_course();
        $applied = apply_course_blueprint::execute((int) $applytarget->id, $blueprint);
        $this->assertNotSame('[]', $applied['modules_json']);
        $this->assertTrue($DB->record_exists('page', ['course' => $applytarget->id, 'name' => 'Welcome page']));

        $copytarget = $this->getDataGenerator()->create_course();
        copy_course_structure::execute((int) $source->id, (int) $copytarget->id);
        $this->assertTrue($DB->record_exists('page', ['course' => $copytarget->id, 'name' => 'Welcome page']));
    }

    /**
     * Enrolment sync enrols listed users and optionally removes missing manual enrolments.
     */
    public function test_sync_course_enrolments(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $kept = $this->getDataGenerator()->create_user();
        $removed = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $context = \context_course::instance($course->id);

        $result = sync_course_enrolments::execute((int) $course->id, [
            ['user_id' => (int) $kept->id, 'role_archetype' => 'student'],
        ], true);

        $this->assertTrue(is_enrolled($context, $kept));
        $this->assertFalse(is_enrolled($context, $removed));
        $this->assertCount(1, json_decode($result['unenrolled_json'], true));
    }

    /**
     * Backup files are uploaded from a draft area into private files and deleted.
     */
    public function test_upload_and_delete_course_backup_file(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $usercontext = \context_user::instance((int) $USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'course.mbz',
        ], 'backup bytes');

        $uploaded = upload_course_backup::execute('course.mbz', '', $draftitemid);
        $file = get_file_storage()->get_file($usercontext->id, 'user', 'private', 0, '/', 'course.mbz');
        $this->assertNotFalse($file);
        $this->assertSame('backup bytes', $file->get_content());

        $deleted = delete_course_backup_file::execute((int) $uploaded['file_id']);
        $this->assertTrue($deleted['deleted']);
        $this->assertFalse(get_file_storage()->get_file($usercontext->id, 'user', 'private', 0, '/', 'course.mbz'));

        $this->expectException(\invalid_parameter_exception::class);
        upload_course_backup::execute('course.zip', '', $draftitemid);
    }

    /**
     * Completion repair reports planned book changes in dry-run mode and applies them otherwise.
     */
    public function test_repair_course_completion(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $book = $this->getDataGenerator()->create_module('book', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);
        // Books have no grade item, so grade-based completion can never be met; this is what the repair fixes.
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $book->cmid]);
        rebuild_course_cache((int) $course->id, true);

        $dryrun = repair_course_completion::execute((int) $course->id, 'book_view_only', true);
        $this->assertTrue($dryrun['dry_run']);
        $this->assertSame(1, $dryrun['changed_count']);
        $this->assertSame(0, (int) $DB->get_field('course_modules', 'completiongradeitemnumber', ['id' => $book->cmid]));

        $applied = repair_course_completion::execute((int) $course->id, 'book_view_only', false);
        $this->assertSame(1, $applied['changed_count']);
        $this->assertNull($DB->get_field('course_modules', 'completiongradeitemnumber', ['id' => $book->cmid]));
        $this->assertSame(1, (int) $DB->get_field('course_modules', 'completionview', ['id' => $book->cmid]));

        // Releases up to 0.1.215 stored -1, which Moodle reads as "grade item -1 required".
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
            'printintro' => 1,
            'printlastmodified' => 1,
        ]);
        $displayoptions = $DB->get_field('page', 'displayoptions', ['id' => $page->id]);
        $DB->set_field('course_modules', 'completiongradeitemnumber', -1, ['id' => $page->cmid]);
        rebuild_course_cache((int) $course->id, true);
        $repaired = repair_course_completion::execute((int) $course->id, 'all_grade_to_view', false);
        $this->assertSame(1, $repaired['changed_count']);
        $this->assertNull($DB->get_field('course_modules', 'completiongradeitemnumber', ['id' => $page->cmid]));
        $this->assertSame($displayoptions, $DB->get_field('page', 'displayoptions', ['id' => $page->id]));

        $this->expectException(\invalid_parameter_exception::class);
        repair_course_completion::execute((int) $course->id, 'everything', true);
    }
}
