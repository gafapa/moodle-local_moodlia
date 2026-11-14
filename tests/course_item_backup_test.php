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
use local_moodlia\external\restore_course_item_backup as restore_course_item_backup_external;
use local_moodlia\operation\backup_course;
use local_moodlia\operation\course_backup_tools;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\backup_course_module::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\backup_course_section::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_moodlia\external\restore_course_item_backup::class)]
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
}
