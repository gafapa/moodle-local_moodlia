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
 * Assignment authoring-content operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\assignment_tools;
use local_moodlia\operation\backup_course;
use local_moodlia\operation\restore_course_backup;
use local_moodlia\operation\update_assignment;

/**
 * Exercises assignment name, editor content, file, and backup behavior.
 */
final class assignment_content_operations_test extends \advanced_testcase {
    /**
     * Database write diagnostics expose only a correlation id to the caller.
     */
    public function test_assignment_update_database_error_has_safe_correlation_id(): void {
        $this->resetAfterTest();
        $logpath = make_request_directory() . '/assignment-update-error.log';
        $originalerrorlog = ini_get('error_log');
        ini_set('error_log', $logpath);

        try {
            $exception = new \dml_write_exception(
                'Duplicate entry for grading definition',
                'INSERT INTO {grading_definitions} (name) VALUES (?)',
                ['private-parameter-value']
            );
            $publicexception = assignment_tools::assignment_update_write_exception($exception, 2609, 7710);
            $log = file_get_contents($logpath);
        } finally {
            ini_set('error_log', $originalerrorlog);
        }

        $this->assertMatchesRegularExpression('/Correlation ID: mla-[a-f0-9]{16}/', $publicexception->getMessage());
        $this->assertStringNotContainsString('INSERT INTO', $publicexception->getMessage());
        $this->assertStringNotContainsString('private-parameter-value', $publicexception->getMessage());
        $this->assertStringContainsString('"operation":"update_assignment"', $log);
        $this->assertStringContainsString('"table":"grading_definitions"', $log);
        $this->assertStringContainsString('"failure_type":"duplicate_key"', $log);
        $this->assertStringContainsString('"exception_class":"dml_write_exception"', $log);
        $this->assertStringContainsString('Duplicate entry for grading definition', $log);
    }

    /**
     * Assignment content updates preserve unrelated assignment settings.
     */
    public function test_update_assignment_changes_authoring_content_and_preserves_settings(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $duedate = time() + DAYSECS;
        $assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Original assignment',
            'intro' => '<p>Original description</p>',
            'introformat' => FORMAT_HTML,
            'activity' => '<p>Original instructions</p>',
            'activityformat' => FORMAT_HTML,
            'duedate' => $duedate,
            'submissiondrafts' => 1,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);

        $updated = update_assignment::execute(
            (int) $course->id,
            (int) $cm->id,
            'Updated assignment',
            '<p>Updated <strong>description</strong></p>',
            'html',
            '<p>Updated activity instructions</p>',
            'html'
        );
        $stored = $DB->get_record('assign', ['id' => $assignment->id], '*', MUST_EXIST);

        $this->assertSame('Updated assignment', $stored->name);
        $this->assertSame('<p>Updated <strong>description</strong></p>', $stored->intro);
        $this->assertSame((int) FORMAT_HTML, (int) $stored->introformat);
        $this->assertSame('<p>Updated activity instructions</p>', $stored->activity);
        $this->assertSame((int) FORMAT_HTML, (int) $stored->activityformat);
        $this->assertSame($duedate, (int) $stored->duedate);
        $this->assertSame(1, (int) $stored->submissiondrafts);
        $this->assertSame('Updated assignment', $updated['name']);
        $this->assertSame([], $updated['uploaded_files']);
    }

    /**
     * Updating assignment content preserves an active rubric and grade configuration.
     */
    public function test_update_assignment_preserves_active_rubric(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Rubric assignment',
            'intro' => '<p>Original rubric description</p>',
            'introformat' => FORMAT_HTML,
            'grade' => 42,
            'submissiondrafts' => 1,
            'assignsubmission_onlinetext_enabled' => 1,
            'assignsubmission_file_enabled' => 0,
            'assignfeedback_comments_enabled' => 1,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance((int) $cm->id);
        $rubricgenerator = $this->getDataGenerator()->get_plugin_generator('gradingform_rubric');
        $controller = $rubricgenerator->create_instance(
            $context,
            'mod_assign',
            'submissions',
            'Content rubric',
            'Regression rubric',
            [
                'Accuracy' => [
                    'Needs work' => 0,
                    'Meets expectations' => 2,
                    'Exceeds expectations' => 4,
                ],
                'Clarity' => [
                    'Unclear' => 0,
                    'Clear' => 2,
                ],
            ]
        );
        $definitionbefore = $controller->get_definition();
        $gradeitembefore = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $assignment->id,
            'itemnumber' => 0,
            'courseid' => $course->id,
        ]);
        $settingsbefore = $DB->get_record('assign', ['id' => $assignment->id], '*', MUST_EXIST);
        $pluginconfigbefore = $DB->get_records('assign_plugin_config', ['assignment' => $assignment->id]);

        update_assignment::execute(
            (int) $course->id,
            (int) $cm->id,
            null,
            '<p>Updated rubric description</p>',
            'html'
        );

        $settingsafter = $DB->get_record('assign', ['id' => $assignment->id], '*', MUST_EXIST);
        $gradeitemafter = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $assignment->id,
            'itemnumber' => 0,
            'courseid' => $course->id,
        ]);
        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
        $definitionafter = $gradingmanager->get_controller('rubric')->get_definition();
        $pluginconfigafter = $DB->get_records('assign_plugin_config', ['assignment' => $assignment->id]);

        $this->assertSame('<p>Updated rubric description</p>', $settingsafter->intro);
        $this->assertSame('rubric', $gradingmanager->get_active_method());
        $this->assertSame((int) $definitionbefore->id, (int) $definitionafter->id);
        $this->assertEquals($definitionbefore->rubric_criteria, $definitionafter->rubric_criteria);
        $this->assertSame((int) $gradeitembefore->id, (int) $gradeitemafter->id);
        foreach (['gradetype', 'grademin', 'grademax', 'scaleid', 'gradepass', 'categoryid', 'hidden', 'locked'] as $field) {
            $this->assertEquals($gradeitembefore->{$field}, $gradeitemafter->{$field}, "Grade item {$field} changed.");
        }
        $this->assertSame((int) $settingsbefore->grade, (int) $settingsafter->grade);
        $this->assertSame((int) $settingsbefore->submissiondrafts, (int) $settingsafter->submissiondrafts);
        $this->assertEquals($pluginconfigbefore, $pluginconfigafter);
    }

    /**
     * Assignment description updates retain existing files and attach a new user draft file.
     */
    public function test_update_assignment_attaches_intro_file_without_removing_existing_files(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Portable assignment',
            'intro' => '<p>Original description</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);
        $modulecontext = \context_module::instance((int) $cm->id);
        $filestorage = get_file_storage();
        $filestorage->create_file_from_string([
            'contextid' => $modulecontext->id,
            'component' => 'mod_assign',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'existing.txt',
        ], 'Existing assignment file');
        $draftitemid = $this->create_draft_file('assignment-hero.jpg', 'Assignment image bytes');
        $intro = '<p><img src="@@PLUGINFILE@@/assignment-hero.jpg" alt="Assignment hero"></p>';

        $updated = update_assignment::execute(
            (int) $course->id,
            (int) $cm->id,
            null,
            $intro,
            'html',
            null,
            null,
            'assignment-hero.jpg',
            '',
            $draftitemid,
            'intro'
        );

        $this->assertCount(1, $updated['uploaded_files']);
        $this->assertSame('assignment-hero.jpg', $updated['uploaded_files'][0]['filename']);
        $this->assertSame('intro', $updated['uploaded_files'][0]['file_area']);
        $this->assertNotFalse($filestorage->get_file(
            $modulecontext->id,
            'mod_assign',
            'intro',
            0,
            '/',
            'assignment-hero.jpg'
        ));
        $this->assertNotFalse($filestorage->get_file(
            $modulecontext->id,
            'mod_assign',
            'intro',
            0,
            '/',
            'existing.txt'
        ));
    }

    /**
     * Native Moodle backup and restore retain assignment description files.
     */
    public function test_assignment_intro_file_survives_native_backup_restore(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Backup assignment',
        ]);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $course->id, false, MUST_EXIST);
        $draftitemid = $this->create_draft_file('portable-assignment.jpg', 'Portable assignment image');
        $intro = '<p><img src="@@PLUGINFILE@@/portable-assignment.jpg" alt="Portable assignment"></p>';
        update_assignment::execute(
            (int) $course->id,
            (int) $cm->id,
            null,
            $intro,
            'html',
            null,
            null,
            'portable-assignment.jpg',
            '',
            $draftitemid
        );

        $backup = backup_course::execute((int) $course->id, 'assignment-file-portability.mbz');
        $restored = restore_course_backup::execute(
            (int) $backup['file_id'],
            'new_course',
            0,
            1,
            'Restored assignment file course',
            'restored-assignment-file-course'
        );
        $restoredcourse = get_course((int) $restored['course_id']);
        $restoredassignments = get_fast_modinfo($restoredcourse)->get_instances_of('assign');
        $this->assertCount(1, $restoredassignments);
        $restoredcm = reset($restoredassignments);
        $stored = $DB->get_record('assign', ['id' => (int) $restoredcm->instance], 'id, intro', MUST_EXIST);
        $this->assertStringContainsString('@@PLUGINFILE@@/portable-assignment.jpg', $stored->intro);

        $restoredcontext = \context_module::instance((int) $restoredcm->id);
        $restoredfile = get_file_storage()->get_file(
            $restoredcontext->id,
            'mod_assign',
            'intro',
            0,
            '/',
            'portable-assignment.jpg'
        );
        $this->assertNotFalse($restoredfile);
        $this->assertSame('Portable assignment image', $restoredfile->get_content());
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
