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
        submit_choice_response::execute((int) $course->id, (int) $choice->cmid, [(int) reset($before)->id]);
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
}
