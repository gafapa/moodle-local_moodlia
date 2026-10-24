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
 * Teacher access and personal token qualification (issue #6).
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

/**
 * Entry permissions and personal tokens must not expand course editing rights.
 */
final class teacher_access_test extends \advanced_testcase {
    /**
     * Both adapters retain the course permissions of the individual teacher.
     */
    public function test_teacher_can_edit_only_their_authorized_course(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_course();
        $second = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($first, 'editingteacher');
        $this->getDataGenerator()->create_and_enrol($second, 'editingteacher');
        $ownpage = $this->getDataGenerator()->create_module('page', ['course' => $first->id, 'name' => 'Own page']);
        $otherpage = $this->getDataGenerator()->create_module('page', ['course' => $second->id, 'name' => 'Other page']);
        $this->grant_transport((int) $teacher->id);
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $this->setUser($teacher);
        $this->assertFalse(has_capability('local/moodlia:manageplugins', \context_system::instance()));
        foreach (['external', 'webmcp'] as $transport) {
            $this->update_page($transport, (int) $first->id, (int) $ownpage->cmid, 'Teacher ' . $transport);
            $this->assertSame('Teacher ' . $transport, $DB->get_field('page', 'name', ['id' => $ownpage->id]));
            try {
                $this->update_page($transport, (int) $second->id, (int) $otherpage->cmid, 'Unauthorized');
                $this->fail('A teacher edited another course through ' . $transport);
            } catch (\moodle_exception $exception) {
                $this->assertSame('Other page', $DB->get_field('page', 'name', ['id' => $otherpage->id]));
            }
        }
    }

    /**
     * A course teaching role alone does not opt an account into the API.
     */
    public function test_teacher_requires_explicit_api_entry_permission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        $this->update_page('external', (int) $course->id, (int) $page->cmid, 'Not opted in');
    }

    /**
     * Moodle creates a personal service token only after service authorization.
     */
    public function test_moodle_personal_token_self_service_retains_authorization(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/webservice/lib.php');
        $this->setAdminUser();
        $teacher = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->grant_transport((int) $teacher->id);
        $service = $DB->get_record('external_services', ['shortname' => 'local_moodlia'], '*', MUST_EXIST);
        $DB->set_field('external_services', 'restrictedusers', 1, ['id' => $service->id]);
        $DB->set_field('external_services', 'enabled', 1, ['id' => $service->id]);
        set_config('enablewebservices', 1);
        $this->setUser($teacher);
        $manager = new \webservice();
        $manager->generate_user_ws_tokens($teacher->id);
        $conditions = ['userid' => $teacher->id, 'externalserviceid' => $service->id];
        $this->assertFalse($DB->record_exists('external_tokens', $conditions));
        $DB->insert_record('external_services_users', (object) [
            'externalserviceid' => $service->id, 'userid' => $teacher->id,
            'iprestriction' => '', 'validuntil' => 0, 'timecreated' => time(),
        ]);
        $manager->generate_user_ws_tokens($teacher->id);
        $token = $DB->get_record('external_tokens', $conditions, '*', MUST_EXIST);
        $this->assertEquals($teacher->id, $token->creatorid);
        $manager->generate_user_ws_tokens($teacher->id);
        $this->assertSame(1, $DB->count_records('external_tokens', $conditions));
        $this->assertFalse($DB->record_exists('external_tokens', [
            'userid' => $other->id, 'externalserviceid' => $service->id,
        ]));
    }

    /**
     * Grant transport and personal token access without course management.
     *
     * @param int $userid User receiving the narrowly scoped role.
     */
    private function grant_transport(int $userid): void {
        $context = \context_system::instance();
        $roleid = create_role('MoodlIA transport', 'moodliateachertransport', '');
        foreach (['local/moodlia:useapi', 'webservice/rest:use', 'moodle/webservice:createtoken'] as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }
        role_assign($roleid, $userid, $context);
    }

    /**
     * Update through the actual external or browser adapter.
     *
     * @param string $transport Adapter to exercise.
     * @param int $courseid Parent course.
     * @param int $moduleid Activity being edited.
     * @param string $name Replacement activity name.
     */
    private function update_page(string $transport, int $courseid, int $moduleid, string $name): void {
        if ($transport === 'webmcp') {
            \local_moodlia\webmcp\bridge::execute('update_module', [
                'course_id' => $courseid, 'module_id' => $moduleid, 'name' => $name,
            ]);
        } else {
            \local_moodlia\external\update_module::execute($courseid, $moduleid, $name);
        }
    }
}
