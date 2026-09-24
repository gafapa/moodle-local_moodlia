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
 * Gradebook and completion write operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');

use advanced_testcase;
use local_moodlia\operation\create_grade_category;
use local_moodlia\operation\create_grade_item;
use local_moodlia\operation\delete_grade_category;
use local_moodlia\operation\delete_grade_item;
use local_moodlia\operation\set_activity_completion_status;
use local_moodlia\operation\set_course_completion_criteria;
use local_moodlia\operation\set_course_grade_pass;
use local_moodlia\operation\update_grade_category;
use local_moodlia\operation\update_grade_item;
use local_moodlia\operation\update_grade_value;

/**
 * Verifies gradebook structure, grade values, and completion write operations.
 *
 * @covers \local_moodlia\operation\create_grade_category
 * @covers \local_moodlia\operation\update_grade_category
 * @covers \local_moodlia\operation\delete_grade_category
 * @covers \local_moodlia\operation\create_grade_item
 * @covers \local_moodlia\operation\update_grade_item
 * @covers \local_moodlia\operation\delete_grade_item
 * @covers \local_moodlia\operation\update_grade_value
 * @covers \local_moodlia\operation\set_course_grade_pass
 * @covers \local_moodlia\operation\set_course_completion_criteria
 * @covers \local_moodlia\operation\set_activity_completion_status
 */
final class gradebook_completion_operations_test extends advanced_testcase {
    /**
     * Grade categories and manual grade items are created, updated, graded, and deleted.
     */
    public function test_gradebook_structure_and_values(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $category = create_grade_category::execute((int) $course->id, 'Practicals');
        $categoryid = (int) $category['category_id'];
        update_grade_category::execute((int) $course->id, $categoryid, 'Labs', null, null, null, null, true);
        $this->assertSame('Labs', $DB->get_field('grade_categories', 'fullname', ['id' => $categoryid]));
        $this->assertSame(1, (int) $DB->get_field('grade_categories', 'aggregateonlygraded', ['id' => $categoryid]));

        $item = create_grade_item::execute((int) $course->id, 'Lab 1', 10.0, 0.0, 5.0, $categoryid);
        $itemid = (int) $item['item_id'];
        $this->assertSame($categoryid, (int) $DB->get_field('grade_items', 'categoryid', ['id' => $itemid]));
        update_grade_item::execute((int) $course->id, $itemid, 'Lab one', 20.0);
        $stored = $DB->get_record('grade_items', ['id' => $itemid], '*', MUST_EXIST);
        $this->assertSame('Lab one', $stored->itemname);
        $this->assertEqualsWithDelta(20.0, (float) $stored->grademax, 0.001);

        update_grade_value::execute((int) $course->id, $itemid, (int) $student->id, 15.0, 'Well done');
        $grade = $DB->get_record('grade_grades', ['itemid' => $itemid, 'userid' => $student->id], '*', MUST_EXIST);
        $this->assertEqualsWithDelta(15.0, (float) $grade->finalgrade, 0.001);
        $this->assertSame('Well done', $grade->feedback);

        delete_grade_item::execute((int) $course->id, $itemid);
        $this->assertFalse($DB->record_exists('grade_items', ['id' => $itemid]));
        delete_grade_category::execute((int) $course->id, $categoryid);
        $this->assertFalse($DB->record_exists('grade_categories', ['id' => $categoryid]));
    }

    /**
     * Course pass grades and course completion criteria are configured.
     */
    public function test_course_pass_grade_and_completion_criteria(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);

        set_course_grade_pass::execute((int) $course->id, null, 50.0);
        $courseitem = \grade_item::fetch_course_item((int) $course->id);
        $this->assertEqualsWithDelta((float) $courseitem->grademax / 2, (float) $courseitem->gradepass, 0.001);

        set_course_completion_criteria::execute((int) $course->id, [(int) $page->cmid], true, 60.0);
        $this->assertTrue($DB->record_exists('course_completion_criteria', [
            'course' => $course->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
            'moduleinstance' => $page->cmid,
        ]));
        $this->assertTrue($DB->record_exists('course_completion_criteria', [
            'course' => $course->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE,
        ]));

        $this->expectException(\invalid_parameter_exception::class);
        set_course_completion_criteria::execute((int) $course->id, [(int) $page->cmid], true, null, 'sometimes');
    }

    /**
     * Students mark manual activity completion on and off.
     */
    public function test_activity_completion_status(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $this->setUser($student);

        $result = set_activity_completion_status::execute((int) $page->cmid, true);
        $this->assertTrue($result['completed']);
        $this->assertSame(COMPLETION_COMPLETE, (int) $DB->get_field('course_modules_completion', 'completionstate', [
            'coursemoduleid' => $page->cmid,
            'userid' => $student->id,
        ]));

        set_activity_completion_status::execute((int) $page->cmid, false);
        $this->assertSame(COMPLETION_INCOMPLETE, (int) $DB->get_field('course_modules_completion', 'completionstate', [
            'coursemoduleid' => $page->cmid,
            'userid' => $student->id,
        ]));
    }
}
