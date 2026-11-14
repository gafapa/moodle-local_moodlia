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
 * Single activity and section backup and import tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use core_external\external_api;
use local_moodlia\external\backup_course_module as backup_course_module_external;
use local_moodlia\external\backup_course_section as backup_course_section_external;
use local_moodlia\external\delete_course_backup_file as delete_course_backup_file_external;
use local_moodlia\external\restore_course_item_backup as restore_course_item_backup_external;
use local_moodlia\operation\backup_course;
use local_moodlia\operation\course_backup_tools;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\backup_course_module::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\backup_course_section::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\restore_course_item_backup::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\delete_course_backup_file::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\operation\course_backup_tools::class)]
/**
 * Exercises native single activity and section backups through the REST externals.
 */
final class course_item_backup_test extends \advanced_testcase {
    /**
     * An assignment is exported alone and imported into a chosen section of another course.
     */
    public function test_activity_backup_imports_into_target_section(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 2]);
        $target = $generator->create_course(['numsections' => 3]);
        $assign = $generator->create_module('assign', [
            'course' => $source->id,
            'section' => 1,
            'name' => 'Portable essay',
        ]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 1, 'name' => 'Not exported']);

        $backup = external_api::clean_returnvalue(
            backup_course_module_external::execute_returns(),
            backup_course_module_external::execute((int) $source->id, (int) $assign->cmid, 'portable-essay')
        );
        $this->assertSame((int) $assign->cmid, $backup['module_id']);
        $this->assertSame('assign', $backup['module_name']);
        $this->assertSame('portable-essay.mbz', $backup['filename']);
        // Moodle stores backups without user data in the caller's private backup area.
        $this->assertStringContainsString('/user/backup/', $backup['url']);

        $listed = course_backup_tools::list_backup_files((int) $source->id, true);
        $listedfiles = array_filter($listed['files'], static fn(array $file): bool => $file['file_id'] === $backup['file_id']);
        $this->assertCount(1, $listedfiles);
        $this->assertSame('user_backup', reset($listedfiles)['backup_area']);

        $restored = external_api::clean_returnvalue(
            restore_course_item_backup_external::execute_returns(),
            restore_course_item_backup_external::execute((int) $backup['file_id'], (int) $target->id, 3)
        );
        $this->assertSame('activity', $restored['backup_type']);
        $this->assertTrue($restored['restored']);
        $modules = json_decode($restored['modules_json'], true);
        $this->assertCount(1, $modules);
        $this->assertSame('assign', $modules[0]['module_name']);
        $this->assertSame('Portable essay', $modules[0]['name']);
        $this->assertSame(3, $modules[0]['section_number']);

        $targetcms = get_fast_modinfo($target->id)->get_cms();
        $this->assertCount(1, $targetcms);
        $this->assertSame(3, (int) reset($targetcms)->sectionnum);
        $this->assertSame((int) $modules[0]['module_id'], (int) reset($targetcms)->id);
    }

    /**
     * A section is exported with its activities and imported into another course.
     */
    public function test_section_backup_imports_section_with_its_activities(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 2]);
        $target = $generator->create_course(['numsections' => 1]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 2, 'name' => 'Unit page']);
        $generator->create_module('label', ['course' => $source->id, 'section' => 2]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 1, 'name' => 'Other section']);
        course_update_section($source, get_fast_modinfo($source)->get_section_info(2), ['name' => 'Unit two']);

        $backup = external_api::clean_returnvalue(
            backup_course_section_external::execute_returns(),
            backup_course_section_external::execute((int) $source->id, 2)
        );
        $this->assertSame(2, $backup['section_number']);
        $this->assertStringContainsString('/user/backup/', $backup['url']);

        $restored = external_api::clean_returnvalue(
            restore_course_item_backup_external::execute_returns(),
            restore_course_item_backup_external::execute((int) $backup['file_id'], (int) $target->id)
        );
        $this->assertSame('section', $restored['backup_type']);
        $modules = json_decode($restored['modules_json'], true);
        $this->assertCount(2, $modules);
        $this->assertEqualsCanonicalizing(['page', 'label'], array_column($modules, 'module_name'));
        $this->assertSame([2, 2], array_column($modules, 'section_number'));
        $this->assertNotContains('Other section', array_column($modules, 'name'));

        $section = get_fast_modinfo($target->id)->get_section_info(2);
        $this->assertNotNull($section);
        $this->assertSame('Unit two', $section->name);
    }

    /**
     * Section backups cannot be redirected, and course backups must use the course restore.
     */
    public function test_item_restore_rejects_unsupported_backups(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 1]);
        $target = $generator->create_course(['numsections' => 1]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 1]);

        $sectionbackup = backup_course_section_external::execute((int) $source->id, 1);
        try {
            restore_course_item_backup_external::execute((int) $sectionbackup['file_id'], (int) $target->id, 0);
            $this->fail('A section backup accepted target_section_number.');
        } catch (\invalid_parameter_exception $error) {
            $this->assertStringContainsString('target_section_number', $error->getMessage());
        }

        $coursebackup = backup_course::execute((int) $source->id, 'whole-course.mbz');
        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('restore_course_backup');
        restore_course_item_backup_external::execute((int) $coursebackup['file_id'], (int) $target->id);
    }

    /**
     * A teacher without restore capabilities cannot import an activity backup.
     */
    public function test_item_restore_requires_restore_capability_for_the_backup_type(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course();
        $target = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $source->id]);

        $teacher = $generator->create_user();
        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $generator->enrol_user($teacher->id, $source->id, $roleid);
        $generator->enrol_user($teacher->id, $target->id, $roleid);
        $systemcontext = \context_system::instance();
        $apirole = $generator->create_role();
        assign_capability('local/moodlia:useapi', CAP_ALLOW, $apirole, $systemcontext->id);
        role_assign($apirole, $teacher->id, $systemcontext->id);
        assign_capability(
            'moodle/restore:restoreactivity',
            CAP_PROHIBIT,
            $roleid,
            \context_course::instance($target->id)->id,
            true
        );

        // The teacher can back up the page, owns the file, and manages activities in the target course,
        // so only the missing restore capability blocks the import.
        $this->setUser($teacher);
        $backup = backup_course_module_external::execute((int) $source->id, (int) $page->cmid);
        try {
            restore_course_item_backup_external::execute((int) $backup['file_id'], (int) $target->id);
            $this->fail('The import ran without moodle/restore:restoreactivity.');
        } catch (\restore_controller_exception $error) {
            $this->assertSame('restore_user_missing_capability', $error->errorcode);
            $this->assertSame('moodle/restore:restoreactivity', $error->a->capability);
        }
        $this->assertSame([], get_fast_modinfo($target->id)->get_cms());
    }

    /**
     * Without a target section, an activity keeps its section number or falls back to section 0.
     */
    public function test_activity_restore_keeps_section_number_or_falls_back_to_section_zero(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 3]);
        $widetarget = $generator->create_course(['numsections' => 3]);
        $narrowtarget = $generator->create_course(['numsections' => 1]);
        $page = $generator->create_module('page', ['course' => $source->id, 'section' => 3, 'name' => 'Third unit page']);

        $backup = backup_course_module_external::execute((int) $source->id, (int) $page->cmid);
        $this->assertSame(3, $backup['section_number']);

        $kept = json_decode(restore_course_item_backup_external::execute(
            (int) $backup['file_id'],
            (int) $widetarget->id
        )['modules_json'], true);
        $this->assertSame(3, $kept[0]['section_number']);
        $this->assertSame('Third unit page', $kept[0]['name']);

        $fallback = json_decode(restore_course_item_backup_external::execute(
            (int) $backup['file_id'],
            (int) $narrowtarget->id
        )['modules_json'], true);
        $this->assertSame(0, $fallback[0]['section_number']);
        $this->assertSame(0, (int) get_fast_modinfo($narrowtarget->id)->get_cm($fallback[0]['module_id'])->sectionnum);
    }

    /**
     * A missing target section is rejected before Moodle restores anything.
     */
    public function test_missing_target_section_is_rejected_before_restoring(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 1]);
        $target = $generator->create_course(['numsections' => 1]);
        $page = $generator->create_module('page', ['course' => $source->id, 'section' => 1]);
        $backup = backup_course_module_external::execute((int) $source->id, (int) $page->cmid);

        try {
            restore_course_item_backup_external::execute((int) $backup['file_id'], (int) $target->id, 9);
            $this->fail('A missing target section was accepted.');
        } catch (\moodle_exception $error) {
            $this->assertSame('sectionnotexist', $error->errorcode);
        }
        $this->assertSame([], get_fast_modinfo($target->id)->get_cms());
    }

    /**
     * A section backup merged into an existing section keeps the target name and existing activities.
     */
    public function test_section_restore_into_existing_section_keeps_target_name_and_content(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 2]);
        $target = $generator->create_course(['numsections' => 2]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 2, 'name' => 'Imported page']);
        course_update_section($source, get_fast_modinfo($source)->get_section_info(2), ['name' => 'Source unit']);
        $existing = $generator->create_module('page', ['course' => $target->id, 'section' => 2, 'name' => 'Local page']);
        course_update_section($target, get_fast_modinfo($target)->get_section_info(2), ['name' => 'Local unit']);

        $backup = backup_course_section_external::execute((int) $source->id, 2);
        $restored = restore_course_item_backup_external::execute((int) $backup['file_id'], (int) $target->id);
        $modules = json_decode($restored['modules_json'], true);
        $this->assertSame(['Imported page'], array_column($modules, 'name'));
        $this->assertSame([2], array_column($modules, 'section_number'));

        $modinfo = get_fast_modinfo($target->id);
        $section = $modinfo->get_section_info(2);
        $this->assertSame('Local unit', $section->name);
        $sequence = array_map('intval', $modinfo->sections[2]);
        $this->assertSame([(int) $existing->cmid, (int) $modules[0]['module_id']], $sequence);
        $this->assertCount(3, $modinfo->get_section_info_all());
    }

    /**
     * A section exported without its activities restores no course modules.
     */
    public function test_section_backup_without_activities_restores_no_modules(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 1]);
        $target = $generator->create_course(['numsections' => 1]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 1]);

        $backup = backup_course_section_external::execute((int) $source->id, 1, '', false, false);
        $restored = restore_course_item_backup_external::execute((int) $backup['file_id'], (int) $target->id);
        $this->assertSame('section', $restored['backup_type']);
        $this->assertSame([], json_decode($restored['modules_json'], true));
        $this->assertSame([], get_fast_modinfo($target->id)->get_cms());
    }

    /**
     * Backups with user data are stored in the activity and section areas, listed, restored and deleted.
     */
    public function test_user_data_backups_use_context_backup_areas(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 2]);
        $target = $generator->create_course(['numsections' => 2]);
        $forum = $generator->create_module('forum', ['course' => $source->id, 'section' => 1, 'name' => 'Class forum']);
        $generator->create_module('page', ['course' => $source->id, 'section' => 2]);

        $activitybackup = backup_course_module_external::execute((int) $source->id, (int) $forum->cmid, 'forum-users', true);
        $this->assertStringContainsString('/backup/activity/', $activitybackup['url']);
        $sectionbackup = backup_course_section_external::execute((int) $source->id, 2, 'unit-users', true);
        $this->assertStringContainsString('/backup/section/', $sectionbackup['url']);

        $listed = [];
        foreach (course_backup_tools::list_backup_files((int) $source->id, false)['files'] as $file) {
            $listed[$file['file_id']] = $file;
        }
        $this->assertSame('activity', $listed[$activitybackup['file_id']]['backup_area']);
        $this->assertSame((int) $forum->cmid, $listed[$activitybackup['file_id']]['module_id']);
        $this->assertSame('section', $listed[$sectionbackup['file_id']]['backup_area']);
        $this->assertArrayNotHasKey('module_id', $listed[$sectionbackup['file_id']]);

        $restored = restore_course_item_backup_external::execute((int) $activitybackup['file_id'], (int) $target->id, 2);
        $this->assertSame('Class forum', json_decode($restored['modules_json'], true)[0]['name']);

        delete_course_backup_file_external::execute((int) $activitybackup['file_id']);
        $remaining = array_column(course_backup_tools::list_backup_files((int) $source->id, false)['files'], 'file_id');
        $this->assertNotContains($activitybackup['file_id'], $remaining);
        $this->assertContains($sectionbackup['file_id'], $remaining);
    }

    /**
     * An editing teacher exports and imports activities and sections between their own courses.
     */
    public function test_editing_teacher_exports_and_imports_between_own_courses(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 2]);
        $target = $generator->create_course(['numsections' => 2]);
        $assign = $generator->create_module('assign', ['course' => $source->id, 'section' => 1, 'name' => 'Weekly essay']);
        $generator->create_module('label', ['course' => $source->id, 'section' => 2]);
        $teacher = $this->create_api_user(['editingteacher' => [$source, $target]]);

        $this->setUser($teacher);
        $activity = backup_course_module_external::execute((int) $source->id, (int) $assign->cmid);
        $this->assertStringContainsString('/user/backup/', $activity['url']);
        $fs = get_file_storage();
        $this->assertNotFalse($fs->get_file_by_id((int) $activity['file_id']), 'after activity backup');
        $placed = restore_course_item_backup_external::execute((int) $activity['file_id'], (int) $target->id, 2);
        $this->assertSame(2, json_decode($placed['modules_json'], true)[0]['section_number']);
        $this->assertNotFalse($fs->get_file_by_id((int) $activity['file_id']), 'after activity restore');

        $section = backup_course_section_external::execute((int) $source->id, 2);
        $this->assertNotFalse($fs->get_file_by_id((int) $activity['file_id']), 'after section backup: '
            . json_encode([$activity['filename'], $section['filename'], course_backup_tools::list_backup_files(0, true)['files']]));
        $merged = restore_course_item_backup_external::execute((int) $section['file_id'], (int) $target->id);
        $this->assertSame(['label'], array_column(json_decode($merged['modules_json'], true), 'module_name'));

        $this->assertCount(2, get_fast_modinfo($target->id)->get_cms());
        delete_course_backup_file_external::execute((int) $activity['file_id']);
        delete_course_backup_file_external::execute((int) $section['file_id']);
        $this->assertSame([], course_backup_tools::list_backup_files(0, true)['files']);
    }

    /**
     * Teachers cannot restore or delete another user's private backup or a context backup they cannot create.
     */
    public function test_backup_files_of_other_users_and_unmanaged_contexts_are_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 1]);
        $target = $generator->create_course(['numsections' => 1]);
        $page = $generator->create_module('page', ['course' => $source->id, 'section' => 1]);
        $adminprivate = backup_course_module_external::execute((int) $source->id, (int) $page->cmid, 'admin-private');
        $adminactivity = backup_course_module_external::execute((int) $source->id, (int) $page->cmid, 'admin-users', true);
        $this->assertStringContainsString('/backup/activity/', $adminactivity['url']);

        // A non-editing teacher sees the source course but cannot back up its activities.
        $teacher = $this->create_api_user(['teacher' => [$source], 'editingteacher' => [$target]]);
        $this->setUser($teacher);
        $attempts = [
            'restore private' => fn() => restore_course_item_backup_external::execute(
                (int) $adminprivate['file_id'],
                (int) $target->id
            ),
            'restore activity area' => fn() => restore_course_item_backup_external::execute(
                (int) $adminactivity['file_id'],
                (int) $target->id
            ),
            'delete private' => fn() => delete_course_backup_file_external::execute((int) $adminprivate['file_id']),
            'delete activity area' => fn() => delete_course_backup_file_external::execute((int) $adminactivity['file_id']),
        ];
        foreach ($attempts as $label => $attempt) {
            try {
                $attempt();
                $this->fail("The teacher could {$label} another user's backup.");
            } catch (\required_capability_exception $error) {
                $this->assertSame('nopermissions', $error->errorcode, $label);
            }
        }

        $fs = get_file_storage();
        $this->assertNotFalse($fs->get_file_by_id((int) $adminprivate['file_id']));
        $this->assertNotFalse($fs->get_file_by_id((int) $adminactivity['file_id']));
        $this->assertSame([], get_fast_modinfo($target->id)->get_cms());
    }

    /**
     * A section with a Moodle 5.x subsection restores the subsection and its nested activities.
     */
    public function test_section_and_subsection_backups_keep_nested_activities_in_their_subsection(): void {
        global $CFG;

        if ((int) $CFG->branch < 500 || !\core_component::get_component_directory('mod_subsection')) {
            $this->markTestSkipped('Subsections require Moodle 5.0 or later.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $source = $generator->create_course(['numsections' => 2]);
        $target = $generator->create_course(['numsections' => 2]);
        $subsection = $generator->create_module('subsection', ['course' => $source->id, 'section' => 1, 'name' => 'Nested']);
        $delegated = get_fast_modinfo($source->id)->get_section_info_by_component('mod_subsection', (int) $subsection->id);
        $this->assertNotNull($delegated);
        $generator->create_module('page', [
            'course' => $source->id,
            'section' => (int) $delegated->section,
            'name' => 'Nested page',
        ]);
        $generator->create_module('page', ['course' => $source->id, 'section' => 1, 'name' => 'Top page']);

        $sectionbackup = backup_course_section_external::execute((int) $source->id, 1);
        $sectionrestore = restore_course_item_backup_external::execute((int) $sectionbackup['file_id'], (int) $target->id);
        $this->assert_nested_page_inside_restored_subsection(
            (int) $target->id,
            json_decode($sectionrestore['modules_json'], true),
            1
        );

        // An explicit target section moves the subsection itself, never the activities nested inside it.
        $subsectionbackup = backup_course_module_external::execute((int) $source->id, (int) $subsection->cmid);
        $moved = restore_course_item_backup_external::execute((int) $subsectionbackup['file_id'], (int) $target->id, 2);
        $this->assert_nested_page_inside_restored_subsection(
            (int) $target->id,
            json_decode($moved['modules_json'], true),
            2
        );
    }

    /**
     * Assert that a restored subsection sits in the expected section and holds the nested page.
     *
     * @param int $courseid Courseid.
     * @param array $modules Modules.
     * @param int $sectionnumber Sectionnumber.
     */
    private function assert_nested_page_inside_restored_subsection(int $courseid, array $modules, int $sectionnumber): void {
        $bytype = [];
        foreach ($modules as $module) {
            $bytype[$module['module_name'] . ':' . $module['name']] = $module;
        }
        $this->assertArrayHasKey('subsection:Nested', $bytype);
        $this->assertArrayHasKey('page:Nested page', $bytype);
        $this->assertSame($sectionnumber, $bytype['subsection:Nested']['section_number']);

        $modinfo = get_fast_modinfo($courseid);
        $subsectioncm = $modinfo->get_cm((int) $bytype['subsection:Nested']['module_id']);
        $delegated = $modinfo->get_section_info_by_component('mod_subsection', (int) $subsectioncm->instance);
        $this->assertNotNull($delegated);
        $nestedpage = $modinfo->get_cm((int) $bytype['page:Nested page']['module_id']);
        $this->assertSame((int) $delegated->section, (int) $nestedpage->sectionnum);
        $this->assertSame((int) $delegated->section, $bytype['page:Nested page']['section_number']);
    }

    /**
     * Create a user with the MoodlIA API capability and course roles.
     *
     * @param array $courseroles Courseroles.
     * @return \stdClass
     */
    private function create_api_user(array $courseroles): \stdClass {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        foreach ($courseroles as $shortname => $courses) {
            $roleid = (int) $DB->get_field('role', 'id', ['shortname' => $shortname], MUST_EXIST);
            foreach ($courses as $course) {
                $generator->enrol_user($user->id, $course->id, $roleid);
            }
        }
        $systemcontext = \context_system::instance();
        $apirole = $generator->create_role();
        assign_capability('local/moodlia:useapi', CAP_ALLOW, $apirole, $systemcontext->id);
        role_assign($apirole, $user->id, $systemcontext->id);

        return $user;
    }
}
