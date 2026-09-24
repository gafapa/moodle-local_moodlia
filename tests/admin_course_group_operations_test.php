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
 * Administration, course, group, cohort, and module write operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\add_cohort_member;
use local_moodlia\operation\add_group_member;
use local_moodlia\operation\add_group_to_grouping;
use local_moodlia\operation\assign_course_role;
use local_moodlia\operation\create_calendar_event;
use local_moodlia\operation\create_cohort;
use local_moodlia\operation\create_course_category;
use local_moodlia\operation\create_grouping;
use local_moodlia\operation\delete_calendar_event;
use local_moodlia\operation\delete_cohort;
use local_moodlia\operation\delete_course;
use local_moodlia\operation\delete_course_category;
use local_moodlia\operation\delete_group;
use local_moodlia\operation\delete_grouping;
use local_moodlia\operation\delete_module;
use local_moodlia\operation\delete_section;
use local_moodlia\operation\delete_user;
use local_moodlia\operation\duplicate_module;
use local_moodlia\operation\move_course;
use local_moodlia\operation\move_module;
use local_moodlia\operation\remove_cohort_member;
use local_moodlia\operation\remove_group_from_grouping;
use local_moodlia\operation\remove_group_member;
use local_moodlia\operation\set_course_publish_state;
use local_moodlia\operation\set_plugin_enabled;
use local_moodlia\operation\unassign_course_role;
use local_moodlia\operation\unenrol_user;
use local_moodlia\operation\update_calendar_event;
use local_moodlia\operation\update_cohort;
use local_moodlia\operation\update_course;
use local_moodlia\operation\update_course_category;
use local_moodlia\operation\update_grouping;
use local_moodlia\operation\update_user;

/**
 * Verifies the administration, course structure, group, cohort, and module write operations.
 *
 * @covers \local_moodlia\operation\create_course_category
 * @covers \local_moodlia\operation\update_course_category
 * @covers \local_moodlia\operation\delete_course_category
 * @covers \local_moodlia\operation\create_calendar_event
 * @covers \local_moodlia\operation\update_calendar_event
 * @covers \local_moodlia\operation\delete_calendar_event
 * @covers \local_moodlia\operation\update_user
 * @covers \local_moodlia\operation\delete_user
 * @covers \local_moodlia\operation\create_cohort
 * @covers \local_moodlia\operation\update_cohort
 * @covers \local_moodlia\operation\delete_cohort
 * @covers \local_moodlia\operation\add_cohort_member
 * @covers \local_moodlia\operation\remove_cohort_member
 * @covers \local_moodlia\operation\assign_course_role
 * @covers \local_moodlia\operation\unassign_course_role
 * @covers \local_moodlia\operation\unenrol_user
 * @covers \local_moodlia\operation\delete_group
 * @covers \local_moodlia\operation\create_grouping
 * @covers \local_moodlia\operation\update_grouping
 * @covers \local_moodlia\operation\delete_grouping
 * @covers \local_moodlia\operation\add_group_to_grouping
 * @covers \local_moodlia\operation\remove_group_from_grouping
 * @covers \local_moodlia\operation\add_group_member
 * @covers \local_moodlia\operation\remove_group_member
 * @covers \local_moodlia\operation\update_course
 * @covers \local_moodlia\operation\move_course
 * @covers \local_moodlia\operation\delete_course
 * @covers \local_moodlia\operation\set_course_publish_state
 * @covers \local_moodlia\operation\delete_section
 * @covers \local_moodlia\operation\duplicate_module
 * @covers \local_moodlia\operation\move_module
 * @covers \local_moodlia\operation\delete_module
 * @covers \local_moodlia\operation\set_plugin_enabled
 */
final class admin_course_group_operations_test extends advanced_testcase {
    /**
     * Course categories are created, renamed, hidden, and deleted.
     */
    public function test_course_category_lifecycle(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        create_course_category::execute('Lab category');
        $categoryid = (int) $DB->get_field('course_categories', 'id', ['name' => 'Lab category'], MUST_EXIST);
        update_course_category::execute($categoryid, 'Renamed category', false);
        $this->assertSame('Renamed category', $DB->get_field('course_categories', 'name', ['id' => $categoryid]));
        $this->assertSame(0, (int) $DB->get_field('course_categories', 'visible', ['id' => $categoryid]));

        delete_course_category::execute($categoryid);
        $this->assertFalse($DB->record_exists('course_categories', ['id' => $categoryid]));
    }

    /**
     * Course calendar events are created, updated, and deleted.
     */
    public function test_calendar_event_lifecycle(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        create_calendar_event::execute((int) $course->id, 'Exam', '<p>Room 4</p>', time() + DAYSECS, 3600);
        $eventid = (int) $DB->get_field('event', 'id', ['courseid' => $course->id, 'eventtype' => 'course'], MUST_EXIST);
        update_calendar_event::execute((int) $course->id, $eventid, 'Final exam', null, null, 7200);
        $event = $DB->get_record('event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame('Final exam', $event->name);
        $this->assertSame(7200, (int) $event->timeduration);

        delete_calendar_event::execute((int) $course->id, $eventid);
        $this->assertFalse($DB->record_exists('event', ['id' => $eventid]));
    }

    /**
     * Users are suspended and deleted, and cohorts manage their members.
     */
    public function test_user_and_cohort_lifecycle(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        update_user::execute((int) $user->id, ['suspended' => true]);
        $this->assertSame(1, (int) $DB->get_field('user', 'suspended', ['id' => $user->id]));

        create_cohort::execute('Lab cohort', 'lab-cohort');
        $cohortid = (int) $DB->get_field('cohort', 'id', ['idnumber' => 'lab-cohort'], MUST_EXIST);
        update_cohort::execute($cohortid, ['visible' => false]);
        $this->assertSame(0, (int) $DB->get_field('cohort', 'visible', ['id' => $cohortid]));

        add_cohort_member::execute($cohortid, (int) $user->id);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $user->id]));
        remove_cohort_member::execute($cohortid, (int) $user->id);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $user->id]));

        delete_cohort::execute($cohortid);
        $this->assertFalse($DB->record_exists('cohort', ['id' => $cohortid]));

        delete_user::execute((int) $user->id);
        $this->assertSame(1, (int) $DB->get_field('user', 'deleted', ['id' => $user->id]));
    }

    /**
     * Course roles are assigned and removed, and manual enrolments are removed.
     */
    public function test_course_roles_and_unenrolment(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student', 'manual');
        $context = \context_course::instance($course->id);
        $teacherrole = (int) $DB->get_field('role', 'id', ['shortname' => 'teacher'], MUST_EXIST);

        assign_course_role::execute((int) $course->id, (int) $user->id, 'teacher');
        $this->assertTrue($DB->record_exists('role_assignments', ['contextid' => $context->id, 'userid' => $user->id, 'roleid' => $teacherrole]));
        unassign_course_role::execute((int) $course->id, (int) $user->id, 'teacher');
        $this->assertFalse($DB->record_exists('role_assignments', ['contextid' => $context->id, 'userid' => $user->id, 'roleid' => $teacherrole]));

        unenrol_user::execute((int) $course->id, (int) $user->id);
        $this->assertFalse(is_enrolled($context, $user));
    }

    /**
     * Groupings, grouping membership, group members, and group deletion.
     */
    public function test_groupings_and_group_members(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Team']);

        create_grouping::execute((int) $course->id, 'Cohort', '', 'grouping-1');
        $groupingid = (int) $DB->get_field('groupings', 'id', ['courseid' => $course->id, 'idnumber' => 'grouping-1'], MUST_EXIST);
        update_grouping::execute((int) $course->id, $groupingid, 'Renamed grouping');
        $this->assertSame('Renamed grouping', $DB->get_field('groupings', 'name', ['id' => $groupingid]));

        add_group_to_grouping::execute((int) $course->id, $groupingid, (int) $group->id);
        $this->assertTrue($DB->record_exists('groupings_groups', ['groupingid' => $groupingid, 'groupid' => $group->id]));
        remove_group_from_grouping::execute((int) $course->id, $groupingid, (int) $group->id);
        $this->assertFalse($DB->record_exists('groupings_groups', ['groupingid' => $groupingid, 'groupid' => $group->id]));

        add_group_member::execute((int) $course->id, (int) $group->id, (int) $user->id);
        $this->assertTrue(groups_is_member($group->id, $user->id));
        remove_group_member::execute((int) $course->id, (int) $group->id, (int) $user->id);
        $this->assertFalse(groups_is_member($group->id, $user->id));

        delete_grouping::execute((int) $course->id, $groupingid);
        $this->assertFalse($DB->record_exists('groupings', ['id' => $groupingid]));
        delete_group::execute((int) $course->id, (int) $group->id);
        $this->assertFalse($DB->record_exists('groups', ['id' => $group->id]));
    }

    /**
     * Courses are updated, published, moved, and deleted.
     */
    public function test_course_update_publish_move_and_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['visible' => 0]);
        $category = $this->getDataGenerator()->create_category();

        update_course::execute((int) $course->id, 'Updated full name');
        $this->assertSame('Updated full name', $DB->get_field('course', 'fullname', ['id' => $course->id]));

        set_course_publish_state::execute((int) $course->id, 'published');
        $this->assertSame(1, (int) $DB->get_field('course', 'visible', ['id' => $course->id]));

        move_course::execute((int) $course->id, (int) $category->id);
        $this->assertSame((int) $category->id, (int) $DB->get_field('course', 'category', ['id' => $course->id]));

        delete_course::execute((int) $course->id);
        $this->assertFalse($DB->record_exists('course', ['id' => $course->id]));
    }

    /**
     * Modules are duplicated, moved, and deleted, and empty sections are deleted.
     */
    public function test_module_duplicate_move_delete_and_section_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1]);

        duplicate_module::execute((int) $course->id, (int) $page->cmid);
        $this->assertSame(2, $DB->count_records('course_modules', ['course' => $course->id, 'module' => $DB->get_field('modules', 'id', ['name' => 'page'])]));

        move_module::execute((int) $course->id, (int) $page->cmid, 2);
        $section = (int) $DB->get_field('course_modules', 'section', ['id' => $page->cmid]);
        $this->assertSame(2, (int) $DB->get_field('course_sections', 'section', ['id' => $section]));

        delete_module::execute((int) $course->id, (int) $page->cmid);
        $this->assertFalse($DB->record_exists('course_modules', ['id' => $page->cmid, 'deletioninprogress' => 0]));

        delete_section::execute((int) $course->id, null, 3);
        $this->assertFalse($DB->record_exists('course_sections', ['course' => $course->id, 'section' => 3]));
    }

    /**
     * Plugins that support it can be disabled and enabled again; MoodlIA itself cannot.
     */
    public function test_set_plugin_enabled(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        set_plugin_enabled::execute('block_online_users', false);
        $this->assertSame(0, (int) $DB->get_field('block', 'visible', ['name' => 'online_users']));
        set_plugin_enabled::execute('block_online_users', true);
        $this->assertSame(1, (int) $DB->get_field('block', 'visible', ['name' => 'online_users']));

        $this->expectException(\moodle_exception::class);
        set_plugin_enabled::execute('local_moodlia', false);
    }
}
