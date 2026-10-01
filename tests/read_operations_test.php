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
 * Read operation compatibility and serialization tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use core_external\external_api;
use local_moodlia\operation\add_folder_file;
use local_moodlia\operation\add_question_to_quiz;
use local_moodlia\operation\create_forum_discussion;
use local_moodlia\operation\create_glossary_entry;
use local_moodlia\operation\create_question;
use local_moodlia\operation\create_question_category;
use local_moodlia\operation\set_workshop_grading_form;

/**
 * Exercises read adapters with real Moodle objects and validates their transport schema.
 */
final class read_operations_test extends \advanced_testcase {
    /**
     * Every read without an attempt lifecycle serializes on the supported core branches.
     */
    public function test_read_operations_serialize_real_fixtures(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $modules = [];
        foreach (['assign', 'book', 'choice', 'data', 'feedback', 'folder', 'forum', 'glossary',
                'lesson', 'resource', 'wiki', 'workshop', 'quiz'] as $type) {
            $modules[$type] = $this->getDataGenerator()->create_module($type, ['course' => $course->id]);
        }
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group->id, $student->id);
        $discussion = create_forum_discussion::execute(
            (int) $course->id, (int) $modules['forum']->cmid, 'Read fixture', '<p>Discussion body</p>'
        );
        $entry = create_glossary_entry::execute(
            (int) $course->id, (int) $modules['glossary']->cmid, 'Fixture', '<p>Definition</p>'
        );
        $category = create_question_category::execute((int) $course->id, 'Read fixture questions');
        $question = create_question::execute(
            (int) $category['category_id'], 'truefalse', 'Read fixture question', 'True?', ['correct_answer' => true]
        );
        add_question_to_quiz::execute((int) $modules['quiz']->cmid, (int) $question['question_id']);
        add_folder_file::execute(
            (int) $course->id, (int) $modules['folder']->cmid, 'fixture.txt', base64_encode('Fixture bytes')
        );
        set_workshop_grading_form::execute(
            (int) $course->id,
            (int) $modules['workshop']->cmid,
            'accumulative',
            json_encode(['dimensions' => [['description' => 'Clarity', 'grade' => 10, 'weight' => 1]]])
        );
        $workshopgenerator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');
        $submissionid = $workshopgenerator->create_submission($modules['workshop']->id, $student->id);
        $assessmentid = $workshopgenerator->create_assessment($submissionid, get_admin()->id);
        $DB->set_field('workshop', 'phase', 30, ['id' => $modules['workshop']->id]);
        $values = [
            'course_id' => (int) $course->id,
            'course_ids' => [(int) $course->id],
            'user_id' => (int) $student->id,
            'group_id' => (int) $group->id,
            'category_id' => (int) $category['category_id'],
            'quiz_module_id' => (int) $modules['quiz']->cmid,
            'choice_module_id' => (int) $modules['choice']->cmid,
            'discussion_id' => (int) $discussion['discussion_id'],
            'entry_id' => (int) $entry['entry_id'],
            'submission_id' => (int) $submissionid,
            'assessment_id' => (int) $assessmentid,
            'author_id' => (int) get_admin()->id,
            'time_from' => 0,
            'time_to' => time(),
            'term' => 'Fixture',
            'query' => 'Fixture',
            'grade' => 50.0,
        ];
        $operations = [
            'get_current_user',
            'get_moodlia_status',
            'get_sync_capabilities',
            'check_plugin_updates',
            'get_courses',
            'get_course_categories',
            'get_course_contents',
            'get_course_details',
            'get_calendar_events',
            'get_enrolled_users',
            'get_grade_items',
            'get_user_grades',
            'get_course_completion_criteria',
            'get_course_completion_status',
            'get_activity_completion_statuses',
            'get_grade_categories',
            'get_course_progress_report',
            'get_user_details',
            'get_groupings',
            'get_group_members',
            'audit_course',
            'get_course_backup_files',
            'audit_course_completion',
            'get_course_books',
            'get_book_chapters',
            'get_lesson_access_information',
            'get_lesson_details',
            'get_course_lessons',
            'get_lesson_pages',
            'get_lesson_user_grade',
            'get_lesson_user_timers',
            'get_lesson_possible_jumps',
            'get_lesson_attempts_overview',
            'get_data_fields',
            'get_data_entries',
            'get_workshop_submissions',
            'get_workshop_user_plan',
            'get_workshop_grades',
            'get_workshop_grades_report',
            'get_workshop_reviewer_assessments',
            'get_workshop_submission_assessments',
            'get_workshop_assessment_form_definition',
            'get_workshop_grading_form',
            'get_course_glossaries',
            'get_glossary_entry',
            'get_glossary_entries_by_letter',
            'get_glossary_entries_by_category',
            'get_glossary_entries_by_author',
            'get_glossary_entries_by_author_id',
            'get_glossary_entries_by_date',
            'get_glossary_entries_by_term',
            'get_glossary_categories',
            'get_glossary_authors',
            'search_glossary_entries',
            'get_glossary_entries_to_approve',
            'get_wiki_pages',
            'get_wiki_subwikis',
            'get_wiki_files',
            'get_choice_options',
            'get_course_choices',
            'get_course_feedbacks',
            'get_feedback_access_information',
            'get_feedback_items',
            'get_feedback_page_items',
            'get_feedback_analysis',
            'get_feedback_finished_responses',
            'get_choice_results',
            'get_course_forums',
            'get_forum_discussions',
            'get_forum_discussion_posts',
            'get_course_assignments',
            'get_assignment_submission_status',
            'get_assignment_grading_form',
            'get_assignment_submissions',
            'get_assignment_grades',
            'get_folder_files',
            'download_folder_file',
            'get_resource_files',
            'download_resource_file',
            'get_question_banks',
            'get_question_categories',
            'get_questions',
            'get_quiz_questions',
            'get_course_quizzes',
            'get_quiz_attempts',
            'get_quiz_results_report',
            'get_quiz_attempt_access_information',
            'get_quiz_access_information',
            'get_quiz_combined_review_options',
            'get_quiz_user_best_grade',
            'get_quiz_feedback_for_grade',
            'get_quiz_required_question_types',
        ];
        $results = [];
        foreach ($operations as $name) {
            $classname = '\\local_moodlia\\external\\' . $name;
            $arguments = [];
            foreach ($classname::execute_parameters()->keys as $key => $description) {
                if ($key === 'module_id') {
                    $type = $this->module_type_for_read($name, $modules);
                    $arguments[] = (int) $modules[$type]->cmid;
                } else if (array_key_exists($key, $values)) {
                    $arguments[] = $values[$key];
                } else {
                    $arguments[] = $description->default;
                }
            }
            try {
                $result = $classname::execute(...$arguments);
                $results[$name] = external_api::clean_returnvalue($classname::execute_returns(), $result);
            } catch (\Throwable $error) {
                $this->fail($name . ': ' . get_class($error) . ': ' . $error->getMessage());
            }
            $this->assertIsArray($results[$name], $name);
            if (isset($results[$name]['course_id'])) {
                $this->assertSame((int) $course->id, (int) $results[$name]['course_id'], $name);
            }
        }
        $this->assertSame((int) get_admin()->id, (int) $results['get_current_user']['id']);
        $this->assertSame((int) $student->id, (int) $results['get_user_details']['id']);
        $this->assertCount(1, $results['get_resource_files']['files']);
        $this->assertSame('fixture.txt', $results['get_folder_files']['files'][0]['filename']);
        $this->assertCount(1, $results['get_questions']['questions']);
    }

    /**
     * Match a read family to its fixture activity.
     *
     * @param string $name Name.
     * @param array $modules Modules.
     * @return string
     */
    private function module_type_for_read(string $name, array $modules): string {
        foreach (array_keys($modules) as $type) {
            if (strpos($name, $type === 'assign' ? 'assignment' : $type) !== false) {
                return $type;
            }
        }
        throw new \coding_exception('No fixture module for ' . $name);
    }
}
