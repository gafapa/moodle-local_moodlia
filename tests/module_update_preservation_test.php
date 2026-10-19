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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Preservation tests for common activity updates.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\submit_choice_response;
use local_moodlia\operation\update_module;
use local_moodlia\operation\upload_folder_file;

/**
 * Ensures completion changes preserve unrelated activity data and learner responses.
 */
final class module_update_preservation_test extends \advanced_testcase {
    /**
     * Quiz introduction edits retain settings, question slots, attempts, feedback and files.
     */
    public function test_quiz_introduction_update_preserves_quiz_state(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id, 'intro' => 'Before', 'introformat' => FORMAT_MARKDOWN,
            'password' => 'retained-password', 'timelimit' => 900, 'grade' => 10,
        ]);
        $category = \local_moodlia\operation\create_question_category::execute(
            (int) $course->id,
            'Introduction test',
            null,
            null,
            'course_shared'
        );
        $question = \local_moodlia\operation\create_question::execute(
            (int) $category['category_id'],
            'truefalse',
            'Example',
            'A statement.',
            ['correct_answer' => true]
        );
        \local_moodlia\operation\add_question_to_quiz::execute((int) $quiz->cmid, (int) $question['question_id']);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $attempt = $quizgenerator->create_attempt($quiz->id, $student->id);
        $quizgenerator->submit_responses($attempt->id, [1 => get_string('true', 'qtype_truefalse')], false, true);
        $this->setAdminUser();
        $quizsettings = \mod_quiz\quiz_settings::create((int) $quiz->id);
        quiz_prepare_and_start_new_attempt($quizsettings, 1, null);
        $DB->insert_record('quiz_feedback', (object) [
            'quizid' => $quiz->id, 'feedbacktext' => 'Retained feedback', 'feedbacktextformat' => FORMAT_HTML,
            'mingrade' => 0, 'maxgrade' => 11,
        ]);
        $context = \context_module::instance((int) $quiz->cmid);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_quiz', 'filearea' => 'intro',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'diagram.svg',
        ], 'Retained diagram bytes');
        $before = $DB->get_record('quiz', ['id' => $quiz->id], '*', MUST_EXIST);
        $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id]);
        $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
        $this->assertCount(2, $attempts);
        $previewflags = array_values(array_unique(array_map(static fn($attempt) => (int) $attempt->preview, $attempts)));
        sort($previewflags);
        $this->assertSame([0, 1], $previewflags);
        $grades = $DB->get_records('quiz_grades', ['quiz' => $quiz->id]);
        $this->assertNotEmpty($grades);
        $feedback = $DB->get_records('quiz_feedback', ['quizid' => $quiz->id]);
        $sink = $this->redirectEvents();

        $result = \local_moodlia\external\update_module::execute((int) $course->id, (int) $quiz->cmid, null, null, json_encode([
            'intro' => '**Read the instructions**',
        ]));

        $after = $DB->get_record('quiz', ['id' => $quiz->id], '*', MUST_EXIST);
        $this->assertSame('**Read the instructions**', $after->intro);
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $after->introformat);
        unset($before->intro, $before->timemodified, $after->intro, $after->timemodified);
        $this->assertEquals($before, $after);
        $this->assertSame((int) $quiz->cmid, $result['module_id']);
        $this->assertSame((int) $quiz->id, $result['instance_id']);
        $this->assertEquals($slots, $DB->get_records('quiz_slots', ['quizid' => $quiz->id]));
        $this->assertEquals($attempts, $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]));
        $this->assertEquals($grades, $DB->get_records('quiz_grades', ['quiz' => $quiz->id]));
        $this->assertEquals($feedback, $DB->get_records('quiz_feedback', ['quizid' => $quiz->id]));
        $this->assertSame('Retained diagram bytes', get_file_storage()->get_file_by_id($file->get_id())->get_content());
        $events = array_filter($sink->get_events(), static fn($event) => $event instanceof \core\event\course_module_updated);
        $this->assertCount(1, $events);
        $sink->close();
    }

    /**
     * Explicit formats and deliberate clearing do not require recreating a quiz.
     */
    public function test_quiz_introduction_formats_and_clearing(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        foreach (['html', 'plain', 'markdown', 'moodle'] as $format) {
            update_module::execute((int) $course->id, (int) $quiz->cmid, null, null, [
                'intro' => 'New introduction', 'intro_format' => $format,
            ]);
            $this->assertSame(
                \local_moodlia\operation\text_format_tools::to_constant($format),
                (int) $DB->get_field('quiz', 'introformat', ['id' => $quiz->id])
            );
        }
        update_module::execute((int) $course->id, (int) $quiz->cmid, null, null, ['intro' => '']);
        $this->assertSame('', $DB->get_field('quiz', 'intro', ['id' => $quiz->id]));
    }

    /**
     * Invalid introduction input is rejected before a simultaneous rename.
     */
    public function test_invalid_quiz_introduction_does_not_rename(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'name' => 'Original']);
        $invalidoptions = [
            ['intro_format' => 'html'],
            ['intro' => null],
            ['intro' => []],
            ['intro' => 'New', 'intro_format' => 'invalid'],
            ['intro' => 'New', 'intro_format' => []],
        ];
        foreach ($invalidoptions as $options) {
            try {
                update_module::execute((int) $course->id, (int) $quiz->cmid, 'Unwanted rename', null, $options);
                $this->fail('Invalid introduction input was accepted.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertSame('Original', $DB->get_field('quiz', 'name', ['id' => $quiz->id]));
            }
        }
    }

    /**
     * Introduction updates require authorized module editors.
     */
    public function test_quiz_introduction_requires_module_edit_permission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        update_module::execute((int) $course->id, (int) $quiz->cmid, null, null, ['intro' => 'Unauthorized']);
    }

    /**
     * The quiz-only introduction option does not change other activity types.
     */
    public function test_introduction_option_rejects_non_quiz_modules(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->expectException(\invalid_parameter_exception::class);
        update_module::execute((int) $course->id, (int) $page->cmid, null, null, ['intro' => 'Unsupported']);
    }

    /**
     * Choice options retain their identities, limits, and submitted answers.
     */
    public function test_choice_completion_update_keeps_options_and_responses(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $choice = $this->getDataGenerator()->create_module('choice', [
            'course' => $course->id,
            'option' => ['First', 'Second'],
            'limit' => [2, 3],
            'limitanswers' => 1,
        ]);
        $before = $DB->get_records('choice_options', ['choiceid' => $choice->id], 'id', 'id,text,maxanswers');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        submit_choice_response::execute((int) $course->id, (int) $choice->cmid, json_encode([(int) reset($before)->id]));
        $answers = $DB->get_records('choice_answers', ['choiceid' => $choice->id]);
        $this->setAdminUser();

        update_module::execute((int) $course->id, (int) $choice->cmid, null, null, [
            'completion_tracking' => 'manual',
            'reset_completion_states' => true,
        ]);

        $this->assertEquals($before, $DB->get_records('choice_options', ['choiceid' => $choice->id], 'id', 'id,text,maxanswers'));
        $this->assertEquals($answers, $DB->get_records('choice_answers', ['choiceid' => $choice->id]));
    }

    /**
     * Assignment submission and feedback plugin settings survive completion updates.
     */
    public function test_assignment_completion_update_keeps_plugin_settings(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 3,
            'assignsubmission_file_maxsizebytes' => 1048576,
            'assignsubmission_file_filetypes' => '',
            'assignsubmission_onlinetext_enabled' => 1,
            'assignfeedback_comments_enabled' => 1,
        ]);
        $before = $DB->get_records('assign_plugin_config', ['assignment' => $assignment->id], 'id');
        $this->assertNotEmpty($before);

        update_module::execute((int) $course->id, (int) $assignment->cmid, null, null, [
            'completion_tracking' => 'manual',
            'reset_completion_states' => true,
        ]);

        $this->assertEquals($before, $DB->get_records('assign_plugin_config', ['assignment' => $assignment->id], 'id'));
    }

    /**
     * URL dynamic parameters and Folder file bytes survive completion changes.
     */
    public function test_completion_update_keeps_url_parameters_and_folder_files(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);
        $parameters = serialize(['course' => 'courseid', 'language' => 'lang']);
        $DB->set_field('url', 'parameters', $parameters, ['id' => $url->id]);
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $course->id]);
        upload_folder_file::execute((int) $course->id, (int) $folder->cmid, 'retained.txt', base64_encode('Retained bytes'));
        foreach ([$url, $folder] as $module) {
            update_module::execute((int) $course->id, (int) $module->cmid, null, null, [
                'completion_tracking' => 'manual',
                'reset_completion_states' => true,
            ]);
        }

        $this->assertSame($parameters, $DB->get_field('url', 'parameters', ['id' => $url->id]));
        $context = \context_module::instance((int) $folder->cmid);
        $file = get_file_storage()->get_file($context->id, 'mod_folder', 'content', 0, '/', 'retained.txt');
        $this->assertNotFalse($file);
        $this->assertSame('Retained bytes', $file->get_content());
    }

    /**
     * Lesson media and active rating windows survive common completion changes.
     */
    public function test_completion_update_keeps_lesson_media_and_rating_windows(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $lesson = $this->getDataGenerator()->create_module('lesson', ['course' => $course->id]);
        $context = \context_module::instance((int) $lesson->cmid);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_lesson', 'filearea' => 'mediafile',
            'itemid' => 0, 'filepath' => '/', 'filename' => 'retained.mp3',
        ], 'Retained lesson media');
        $DB->set_field('lesson', 'mediafile', '/retained.mp3', ['id' => $lesson->id]);
        update_module::execute((int) $course->id, (int) $lesson->cmid, null, null, [
            'completion_tracking' => 'manual', 'reset_completion_states' => true,
        ]);
        $file = get_file_storage()->get_file($context->id, 'mod_lesson', 'mediafile', 0, '/', 'retained.mp3');
        $this->assertNotFalse($file);
        $this->assertSame('Retained lesson media', $file->get_content());
        $this->assertSame('/retained.mp3', $DB->get_field('lesson', 'mediafile', ['id' => $lesson->id]));

        foreach (['forum', 'glossary', 'data'] as $type) {
            $module = $this->getDataGenerator()->create_module($type, [
                'course' => $course->id, 'assessed' => 1, 'scale' => 100, 'ratingtime' => 1,
                'assesstimestart' => 1700000000, 'assesstimefinish' => 1800000000,
            ]);
            update_module::execute((int) $course->id, (int) $module->cmid, null, null, [
                'completion_tracking' => 'manual', 'reset_completion_states' => true,
            ]);
            $record = $DB->get_record($type, ['id' => $module->id], '*', MUST_EXIST);
            $this->assertSame(1, (int) $record->assessed, $type);
            $this->assertSame(1700000000, (int) $record->assesstimestart, $type);
            $this->assertSame(1800000000, (int) $record->assesstimefinish, $type);
        }
    }

    /**
     * Workshop submission and assessment grades keep their distinct categories.
     */
    public function test_workshop_completion_update_keeps_grade_categories(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $workshop = $this->getDataGenerator()->create_module('workshop', ['course' => $course->id]);
        $parent = \grade_category::fetch_course_category((int) $course->id);
        foreach (['Submission grades', 'Assessment grades'] as $number => $name) {
            $category = new \grade_category(['courseid' => $course->id, 'parent' => $parent->id, 'fullname' => $name], false);
            $category->insert();
            $DB->set_field('grade_items', 'categoryid', $category->id, [
                'courseid' => $course->id, 'itemmodule' => 'workshop',
                'iteminstance' => $workshop->id, 'itemnumber' => $number,
            ]);
        }
        $conditions = ['courseid' => $course->id, 'itemmodule' => 'workshop', 'iteminstance' => $workshop->id];
        $before = $DB->get_records('grade_items', $conditions, 'id', 'id,itemnumber,categoryid');
        $this->assertCount(2, $before);
        $this->assertCount(2, array_unique(array_column($before, 'categoryid')));
        update_module::execute((int) $course->id, (int) $workshop->cmid, null, null, [
            'completion_tracking' => 'manual', 'reset_completion_states' => true,
        ]);
        $this->assertEquals($before, $DB->get_records('grade_items', $conditions, 'id', 'id,itemnumber,categoryid'));
    }
}
