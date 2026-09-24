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
 * Question bank, quiz structure, and quiz attempt write operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\add_question_to_quiz;
use local_moodlia\operation\add_random_questions_to_quiz;
use local_moodlia\operation\create_question;
use local_moodlia\operation\create_question_category;
use local_moodlia\operation\delete_question;
use local_moodlia\operation\delete_question_category;
use local_moodlia\operation\export_question_bank_blueprint;
use local_moodlia\operation\import_question_bank_blueprint;
use local_moodlia\operation\move_question;
use local_moodlia\operation\process_quiz_attempt;
use local_moodlia\operation\remove_question_from_quiz;
use local_moodlia\operation\save_quiz_attempt;
use local_moodlia\operation\start_quiz_attempt;
use local_moodlia\operation\update_question;
use local_moodlia\operation\update_question_category;
use local_moodlia\operation\update_quiz_question_slot;
use local_moodlia\operation\view_quiz;
use local_moodlia\operation\view_quiz_attempt;
use local_moodlia\operation\view_quiz_attempt_review;
use local_moodlia\operation\view_quiz_attempt_summary;

/**
 * Verifies question bank maintenance, quiz slots, and a full student quiz attempt.
 *
 * @covers \local_moodlia\operation\create_question
 * @covers \local_moodlia\operation\update_question
 * @covers \local_moodlia\operation\move_question
 * @covers \local_moodlia\operation\delete_question
 * @covers \local_moodlia\operation\update_question_category
 * @covers \local_moodlia\operation\delete_question_category
 * @covers \local_moodlia\operation\import_question_bank_blueprint
 * @covers \local_moodlia\operation\add_question_to_quiz
 * @covers \local_moodlia\operation\add_random_questions_to_quiz
 * @covers \local_moodlia\operation\update_quiz_question_slot
 * @covers \local_moodlia\operation\remove_question_from_quiz
 * @covers \local_moodlia\operation\view_quiz
 * @covers \local_moodlia\operation\start_quiz_attempt
 * @covers \local_moodlia\operation\view_quiz_attempt
 * @covers \local_moodlia\operation\save_quiz_attempt
 * @covers \local_moodlia\operation\view_quiz_attempt_summary
 * @covers \local_moodlia\operation\process_quiz_attempt
 * @covers \local_moodlia\operation\view_quiz_attempt_review
 */
final class question_quiz_operations_test extends advanced_testcase {
    /**
     * Questions and categories are created, edited, moved, deleted, and imported from a blueprint.
     */
    public function test_question_bank_maintenance_and_blueprint_import(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $source = create_question_category::execute((int) $course->id, 'Source', null, null, 'course_shared');
        $target = create_question_category::execute((int) $course->id, 'Target', null, null, 'course_shared');
        $empty = create_question_category::execute((int) $course->id, 'Empty', null, null, 'course_shared');

        $question = create_question::execute((int) $source['category_id'], 'truefalse', 'Sky', 'The sky is blue.', ['correct_answer' => true]);
        $questionid = (int) $question['question_id'];
        $updated = update_question::execute($questionid, 'Sky colour');
        $this->assertSame('Sky colour', $DB->get_field('question', 'name', ['id' => $updated['question_id']]));

        $moved = move_question::execute((int) $course->id, (int) $updated['question_id'], (int) $target['category_id']);
        $this->assertSame((int) $target['category_id'], (int) $moved['target_category_id']);

        $blueprint = export_question_bank_blueprint::execute((int) $course->id, 'course_shared', null, null, (int) $target['category_id']);
        $this->assertSame(1, $blueprint['question_count']);
        $othercourse = $this->getDataGenerator()->create_course();
        $imported = import_question_bank_blueprint::execute((int) $othercourse->id, $blueprint['blueprint_json'], 'course_shared');
        $this->assertSame(1, $imported['created_question_count']);

        delete_question::execute((int) $moved['question_id']);
        $this->assertFalse($DB->record_exists('question', ['id' => $moved['question_id']]));

        update_question_category::execute((int) $empty['category_id'], 'Renamed empty', 'Nothing here');
        $this->assertSame('Renamed empty', $DB->get_field('question_categories', 'name', ['id' => $empty['category_id']]));
        delete_question_category::execute((int) $empty['category_id']);
        $this->assertFalse($DB->record_exists('question_categories', ['id' => $empty['category_id']]));
    }

    /**
     * Quiz slots are added, re-weighted, filled with random questions, and removed.
     */
    public function test_quiz_slots(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $category = create_question_category::execute((int) $course->id, 'Pool', null, null, 'course_shared');
        $first = create_question::execute((int) $category['category_id'], 'truefalse', 'One', 'One is odd.', ['correct_answer' => true]);
        create_question::execute((int) $category['category_id'], 'truefalse', 'Two', 'Two is odd.', ['correct_answer' => false]);

        add_question_to_quiz::execute((int) $quiz->cmid, (int) $first['question_id']);
        add_random_questions_to_quiz::execute((int) $quiz->cmid, (int) $category['category_id'], 1);
        $this->assertSame(2, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));

        update_quiz_question_slot::execute((int) $quiz->cmid, 1, 2.5);
        $this->assertEqualsWithDelta(2.5, (float) $DB->get_field('quiz_slots', 'maxmark', ['quizid' => $quiz->id, 'slot' => 1]), 0.001);

        remove_question_from_quiz::execute((int) $quiz->cmid, 2);
        $this->assertSame(1, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));
    }

    /**
     * A student views a quiz, answers, reviews the summary, submits, and reviews the attempt.
     */
    public function test_student_quiz_attempt(): void {
        global $DB, $PAGE;

        $this->resetAfterTest();
        $PAGE->set_url(new \moodle_url('/'));
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'grade' => 10]);
        $category = create_question_category::execute((int) $course->id, 'Attempt', null, null, 'course_shared');
        $question = create_question::execute((int) $category['category_id'], 'truefalse', 'Earth', 'The earth is round.', ['correct_answer' => true]);
        add_question_to_quiz::execute((int) $quiz->cmid, (int) $question['question_id']);

        $this->setUser($student);
        $this->assertTrue(view_quiz::execute((int) $quiz->cmid)['viewed']);
        $started = start_quiz_attempt::execute((int) $quiz->cmid);
        $attemptid = (int) $started['attempt']['attempt_id'];
        $this->assertGreaterThan(0, $attemptid);
        view_quiz_attempt::execute((int) $quiz->cmid, $attemptid);

        $attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
        $qa = $attemptobj->get_question_attempt(1);
        $data = [
            ['name' => $qa->get_qt_field_name('answer'), 'value' => '1'],
            ['name' => $qa->get_control_field_name('sequencecheck'), 'value' => (string) $qa->get_sequence_check_count()],
        ];
        $this->assertTrue(save_quiz_attempt::execute((int) $quiz->cmid, $attemptid, $data)['saved']);
        view_quiz_attempt_summary::execute((int) $quiz->cmid, $attemptid);

        $processed = process_quiz_attempt::execute((int) $quiz->cmid, $attemptid, [], true);
        $this->assertTrue($processed['finished']);
        $this->assertEqualsWithDelta(1.0, (float) $DB->get_field('quiz_attempts', 'sumgrades', ['id' => $attemptid]), 0.001);

        view_quiz_attempt_review::execute((int) $quiz->cmid, $attemptid);
    }
}
