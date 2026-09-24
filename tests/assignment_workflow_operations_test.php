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
 * Assignment submission, view, and grading write operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use advanced_testcase;
use local_moodlia\operation\grade_assignment_with_checklist;
use local_moodlia\operation\grade_assignment_with_marking_guide;
use local_moodlia\operation\grade_assignment_with_rubric;
use local_moodlia\operation\save_assignment_grade;
use local_moodlia\operation\save_assignment_submission;
use local_moodlia\operation\set_assignment_checklist;
use local_moodlia\operation\set_assignment_marking_guide;
use local_moodlia\operation\submit_assignment_for_grading;
use local_moodlia\operation\view_assignment;
use local_moodlia\operation\view_assignment_grading_table;
use local_moodlia\operation\view_assignment_submission_status;

/**
 * Verifies the assignment submission workflow and simple and advanced grading.
 *
 * @covers \local_moodlia\operation\save_assignment_submission
 * @covers \local_moodlia\operation\submit_assignment_for_grading
 * @covers \local_moodlia\operation\save_assignment_grade
 * @covers \local_moodlia\operation\view_assignment
 * @covers \local_moodlia\operation\view_assignment_submission_status
 * @covers \local_moodlia\operation\view_assignment_grading_table
 * @covers \local_moodlia\operation\set_assignment_checklist
 * @covers \local_moodlia\operation\grade_assignment_with_checklist
 * @covers \local_moodlia\operation\grade_assignment_with_rubric
 * @covers \local_moodlia\operation\set_assignment_marking_guide
 * @covers \local_moodlia\operation\grade_assignment_with_marking_guide
 * @covers \local_moodlia\operation\assignment_grading_tools
 */
final class assignment_workflow_operations_test extends advanced_testcase {
    /**
     * Students save and submit online text, and teachers view and grade it.
     */
    public function test_submission_views_and_simple_grade(): void {
        global $DB;

        $this->resetAfterTest();
        [$course, $assign, $student] = $this->create_assignment();

        $this->setUser($student);
        save_assignment_submission::execute((int) $course->id, (int) $assign->cmid, '<p>My answer</p>');
        $submission = $DB->get_record('assign_submission', ['assignment' => $assign->id, 'userid' => $student->id, 'latest' => 1], '*', MUST_EXIST);
        $this->assertSame(ASSIGN_SUBMISSION_STATUS_DRAFT, $submission->status);
        $this->assertStringContainsString('My answer', $DB->get_field('assignsubmission_onlinetext', 'onlinetext', ['submission' => $submission->id]));
        $this->assertTrue(view_assignment_submission_status::execute((int) $course->id, (int) $assign->cmid)['viewed']);

        submit_assignment_for_grading::execute((int) $course->id, (int) $assign->cmid);
        $this->assertSame(ASSIGN_SUBMISSION_STATUS_SUBMITTED, $DB->get_field('assign_submission', 'status', ['id' => $submission->id]));

        $this->setAdminUser();
        $this->assertTrue(view_assignment::execute((int) $course->id, (int) $assign->cmid)['viewed']);
        $this->assertTrue(view_assignment_grading_table::execute((int) $course->id, (int) $assign->cmid)['viewed']);
        save_assignment_grade::execute((int) $course->id, (int) $assign->cmid, (int) $student->id, 72.5, 'Good work');
        $grade = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $student->id], '*', MUST_EXIST);
        $this->assertEqualsWithDelta(72.5, (float) $grade->grade, 0.001);
        $this->assertStringContainsString('Good work', $DB->get_field('assignfeedback_comments', 'commenttext', ['grade' => $grade->id]));
    }

    /**
     * Checklists are binary rubrics graded by checklist items or rubric levels, and marking guides by score.
     */
    public function test_checklist_rubric_and_marking_guide_grading(): void {
        global $DB;

        $this->resetAfterTest();
        [$course, $assign, $student] = $this->create_assignment();
        $this->setAdminUser();

        $checklist = set_assignment_checklist::execute((int) $course->id, (int) $assign->cmid, 'Checklist', 'Criteria', json_encode([
            'items' => [['description' => 'Has a title'], ['description' => 'Cites sources']],
        ]));
        $this->assertTrue($checklist['checklist_compatible']);
        $criteria = array_values($checklist['criteria']);
        $this->assertCount(2, $criteria);

        grade_assignment_with_checklist::execute((int) $course->id, (int) $assign->cmid, (int) $student->id, json_encode([
            'items' => [
                ['criterion_id' => $criteria[0]['criterion_id'], 'checked' => true],
                ['criterion_id' => $criteria[1]['criterion_id'], 'checked' => false],
            ],
        ]));
        $this->assertEqualsWithDelta(50.0, (float) $this->student_grade($assign->id, $student->id), 0.001);

        $levels = array_map(static fn(array $criterion): array => array_column($criterion['levels'], 'level_id'), $criteria);
        grade_assignment_with_rubric::execute((int) $course->id, (int) $assign->cmid, (int) $student->id, json_encode([
            'criteria' => [
                ['criterion_id' => $criteria[0]['criterion_id'], 'level_id' => end($levels[0])],
                ['criterion_id' => $criteria[1]['criterion_id'], 'level_id' => end($levels[1])],
            ],
        ]));
        $this->assertEqualsWithDelta(100.0, (float) $this->student_grade($assign->id, $student->id), 0.001);

        $guide = set_assignment_marking_guide::execute((int) $course->id, (int) $assign->cmid, 'Guide', 'Marking', json_encode([
            'criteria' => [['shortname' => 'Content', 'description' => 'Content quality', 'max_score' => 10]],
        ]));
        $this->assertSame('guide', $guide['active_method']);
        $guidecriterion = array_values($guide['criteria'])[0];
        grade_assignment_with_marking_guide::execute((int) $course->id, (int) $assign->cmid, (int) $student->id, json_encode([
            'criteria' => [['criterion_id' => $guidecriterion['criterion_id'], 'score' => 7, 'remark' => 'Solid']],
        ]));
        $this->assertEqualsWithDelta(70.0, (float) $this->student_grade($assign->id, $student->id), 0.001);
        $this->assertTrue($DB->record_exists('gradingform_guide_fillings', ['criterionid' => $guidecriterion['criterion_id']]));
    }

    /**
     * Create a course, an online-text assignment, and an enrolled student.
     *
     * @return array
     */
    private function create_assignment(): array {
        global $PAGE;

        // Web service requests set the page URL; mod_assign status renderables read it.
        $PAGE->set_url(new \moodle_url('/'));
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_onlinetext_enabled' => 1,
            'assignfeedback_comments_enabled' => 1,
            'submissiondrafts' => 1,
            'requiresubmissionstatement' => 0,
            'grade' => 100,
        ]);
        return [$course, $assign, $student];
    }

    /**
     * Return the stored assignment grade for a student.
     *
     * @param int $assignid Assignment id.
     * @param int $userid User id.
     * @return float
     */
    private function student_grade(int $assignid, int $userid): float {
        global $DB;

        return (float) $DB->get_field('assign_grades', 'grade', ['assignment' => $assignid, 'userid' => $userid], MUST_EXIST);
    }
}
