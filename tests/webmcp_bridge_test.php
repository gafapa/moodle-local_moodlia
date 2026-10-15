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
 * WebMCP browser transport tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\webmcp\bridge;

/**
 * Verifies browser transport access, contract reuse, and navigation boundaries.
 *
 * @covers \local_moodlia\webmcp\bridge
 */
#[\PHPUnit\Framework\Attributes\CoversClass(bridge::class)]
/**
 * Webmcp bridge test implementation.
 */
final class webmcp_bridge_test extends advanced_testcase {
    /**
     * An explicitly wrong CSRF token must be rejected before inspecting uploaded files.
     */
    public function test_upload_rejects_an_invalid_sesskey(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $previouspost = $_POST;
        try {
            $_POST['sesskey'] = 'invalid-synthetic-key';
            $this->expectException(\moodle_exception::class);
            bridge::upload();
        } finally {
            $_POST = $previouspost;
        }
    }

    /**
     * A session-authenticated upload still requires an actual multipart file.
     */
    public function test_upload_requires_a_multipart_file(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $previouspost = $_POST;
        $previousfiles = $_FILES;
        try {
            $_POST['sesskey'] = sesskey();
            $_FILES = [];
            $this->expectException(\moodle_exception::class);
            bridge::upload();
        } finally {
            $_POST = $previouspost;
            $_FILES = $previousfiles;
        }
    }

    /**
     * Canonical object fields reject JSON lists and scalar substitutes.
     */
    public function test_object_arguments_reject_lists_and_scalars(): void {
        $schema = ['properties' => ['options' => ['type' => 'object']]];
        foreach ([[1, 2], 'not-an-object', 7, false] as $invalid) {
            try {
                bridge::normalize_arguments(['options' => $invalid], $schema);
                $this->fail('An invalid object argument was accepted.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertStringContainsString('JSON object', $exception->getMessage());
            }
        }
        $this->assertSame('{}', bridge::normalize_arguments(['options' => []], $schema)['options']);
        $this->assertSame('{}', bridge::normalize_arguments(['options' => new \stdClass()], $schema)['options']);
    }

    /**
     * Required arguments and enum values are checked before native execution.
     */
    public function test_required_enum_and_nullable_arguments(): void {
        $schema = ['required' => ['mode'], 'properties' => [
            'mode' => ['type' => 'string', 'enum' => ['all', 'any']], 'optional' => ['type' => 'string'],
        ]];
        foreach ([[], ['mode' => null], ['mode' => 'invalid']] as $invalid) {
            try {
                bridge::normalize_arguments($invalid, $schema);
                $this->fail('Invalid required or enum input was accepted.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        $this->assertSame(['mode' => 'all'], bridge::normalize_arguments(['mode' => 'all', 'optional' => null], $schema));
    }

    /**
     * All native navigation destinations resolve under Moodle's current wwwroot.
     */
    public function test_course_gradebook_and_assignment_navigation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        foreach (['course' => '/course/view.php', 'gradebook' => '/grade/report/grader/index.php'] as $destination => $path) {
            $result = bridge::execute('moodlia_open_page', ['destination' => $destination, 'course_id' => (int) $course->id]);
            $this->assertSame((new \moodle_url($path, ['id' => $course->id]))->out(false), $result['url']);
        }
        $result = bridge::execute('moodlia_open_page', [
            'destination' => 'assignment_grader', 'course_id' => (int) $course->id, 'module_id' => (int) $assignment->cmid,
        ]);
        $this->assertSame((new \moodle_url('/mod/assign/view.php', ['id' => $assignment->cmid, 'action' => 'grader']))->out(false), $result['url']);
    }

    /**
     * Invalid targets cannot be converted into arbitrary or malformed URLs.
     */
    public function test_navigation_rejects_invalid_identifiers_and_missing_targets(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        foreach ([0, -1, '1', 1.5, true] as $invalid) {
            try {
                bridge::execute('moodlia_open_page', ['destination' => 'course', 'course_id' => $invalid]);
                $this->fail('An invalid navigation identifier was accepted.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        foreach (['course', 'gradebook', 'activity', 'assignment_grader'] as $destination) {
            try {
                bridge::execute('moodlia_open_page', ['destination' => $destination]);
                $this->fail('Navigation without a target was accepted.');
            } catch (\invalid_parameter_exception $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    /**
     * Unknown tools are rejected even for an administrator.
     */
    public function test_unknown_tool_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $this->expectException(\invalid_parameter_exception::class);
        bridge::execute('core_user_delete_users', []);
    }

    /**
     * A real write/read path preserves nested options, booleans, and scalar ID arrays.
     */
    public function test_native_writes_preserve_json_objects_and_integer_arrays(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $_POST['sesskey'] = sesskey();
        try {
            $created = bridge::execute('create_module', [
                'course_id' => (int) $course->id, 'section_number' => 0, 'module_type' => 'page',
                'name' => 'Synthetic WebMCP Page', 'options' => (object) [
                    'content' => '<p>Synthetic Unicode áéíóú.</p>', 'visible' => false, 'completion_tracking' => 'manual',
                ],
            ]);
            $this->assertFalse($created['visible']);
            $this->assertGreaterThan(0, $created['module_id']);
            $criteria = bridge::execute('set_course_completion_criteria', [
                'course_id' => (int) $course->id, 'required_module_ids' => [(int) $created['module_id']],
                'require_all_activities' => false,
            ]);
            $this->assertSame([(int) $created['module_id']], $criteria['required_module_ids']);
        } finally {
            unset($_POST['sesskey']);
        }
    }

    /**
     * Native module visibility and grading permissions also apply to enrolled users.
     */
    public function test_enrolled_student_cannot_open_hidden_activity_or_grading_pages(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $hidden = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'visible' => 0]);
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $roleid = create_role('WebMCP student transport', 'webmcpstudent', '');
        assign_capability('local/moodlia:useapi', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $student->id, \context_system::instance());
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $this->setUser($student);
        foreach ([
            ['destination' => 'activity', 'module_id' => (int) $hidden->cmid],
            ['destination' => 'assignment_grader', 'module_id' => (int) $assignment->cmid],
            ['destination' => 'gradebook', 'course_id' => (int) $course->id],
        ] as $arguments) {
            try {
                bridge::execute('moodlia_open_page', $arguments);
                $this->fail('A student opened a privileged or hidden destination.');
            } catch (\moodle_exception $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    /**
     * Draft uploads keep native repository processing and the signed-in user's ownership.
     */
    public function test_upload_creates_user_owned_draft_with_exact_bytes(): void {
        global $USER, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        require_once($CFG->dirroot . '/repository/lib.php');
        $type = \repository::get_type_by_typename('upload');
        if (!$type) {
            $type = new \repository_type('upload', [], true);
            $type->create();
        }
        $temporary = make_request_directory() . '/fictional.txt';
        $content = 'Fictional file with Unicode: áéíóú.';
        file_put_contents($temporary, $content);
        $previousfiles = $_FILES;
        $previouspost = $_POST;
        try {
            $_POST['sesskey'] = sesskey();
            $_FILES['repo_upload_file'] = [
                'name' => 'fictional.txt', 'tmp_name' => $temporary,
                'type' => 'text/plain', 'size' => strlen($content), 'error' => UPLOAD_ERR_OK,
            ];
            $uploaded = bridge::upload();
            $this->assertGreaterThan(0, $uploaded['draft_item_id']);
            $file = get_file_storage()->get_file(
                \context_user::instance($USER->id)->id, 'user', 'draft', $uploaded['draft_item_id'], '/', $uploaded['filename']
            );
            $this->assertSame($content, $file->get_content());
            $this->assertSame((int) $USER->id, (int) $file->get_userid());
        } finally {
            $_FILES = $previousfiles;
            $_POST = $previouspost;
        }
    }

    /**
     * A logged-in browser cannot bypass CSRF validation for draft uploads.
     */
    public function test_upload_requires_sesskey(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $previouspost = $_POST;
        $previousget = $_GET;
        try {
            unset($_POST['sesskey'], $_GET['sesskey']);
            $this->expectException(\moodle_exception::class);
            bridge::upload();
        } finally {
            $_POST = $previouspost;
            $_GET = $previousget;
        }
    }

    /**
     * The optional feature must be explicitly enabled.
     */
    public function test_disabled_feature_rejects_calls(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 0, 'local_moodlia');
        $this->expectException(\moodle_exception::class);
        bridge::execute('get_current_user', []);
    }

    /**
     * Signing in does not grant the privileged MoodlIA capability.
     */
    public function test_api_capability_is_required(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $this->expectException(\required_capability_exception::class);
        bridge::execute('get_current_user', []);
    }

    /**
     * All canonical tools remain available regardless of the currently open course.
     */
    public function test_complete_catalogue_reuses_existing_operations(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $tools = array_column(bridge::tools(), null, 'name');
        foreach (\local_moodlia\mcp\manifest::tool_names() as $name) {
            $this->assertArrayHasKey($name, $tools);
        }
        $this->assertTrue($tools['get_course_contents']['annotations']['readOnlyHint']);
        $this->assertTrue($tools['create_course']['annotations']['consequentialHint']);
        $this->assertArrayHasKey('moodlia_open_page', $tools);
    }

    /**
     * Object-valued JSON fields and native scalar arrays keep their distinct shapes.
     */
    public function test_native_argument_shapes_are_preserved(): void {
        $schema = ['properties' => [
            'options' => ['type' => 'object'], 'ids' => ['type' => 'array'], 'visible' => ['type' => 'boolean'],
        ]];
        $result = bridge::normalize_arguments(['options' => (object) ['content' => 'Example'], 'ids' => [2, 3], 'visible' => false], $schema);
        $this->assertSame(['content' => 'Example'], json_decode($result['options'], true));
        $this->assertSame([2, 3], $result['ids']);
        $this->assertFalse($result['visible']);
        $this->expectException(\invalid_parameter_exception::class);
        bridge::normalize_arguments(['wstoken' => 'not-allowed'], $schema);
    }

    /**
     * An authorized read can target a course other than the current page's course.
     */
    public function test_read_is_not_restricted_to_current_course(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $first = $this->getDataGenerator()->create_course();
        $second = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', ['course' => $second->id]);
        $PAGE->set_course($first);
        $_POST['sesskey'] = sesskey();
        try {
            $result = bridge::execute('get_course_contents', ['course_id' => (int) $second->id]);
            $this->assertNotEmpty($result);
        } finally {
            unset($_POST['sesskey']);
        }
    }

    /**
     * Navigation resolves the native module URL without arbitrary destination input.
     */
    public function test_navigation_targets_native_moodle_pages(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $result = bridge::execute('moodlia_open_page', ['destination' => 'activity', 'module_id' => (int) $page->cmid]);
        $this->assertSame((new \moodle_url('/mod/page/view.php', ['id' => $page->cmid]))->out(false), $result['url']);
        $this->expectException(\invalid_parameter_exception::class);
        bridge::execute('moodlia_open_page', ['destination' => 'activity', 'module_id' => (int) $page->cmid, 'url' => 'https://example.org']);
    }

    /**
     * A page activity must never be navigated to as an assignment grader.
     */
    public function test_grader_requires_an_assignment(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->expectException(\invalid_parameter_exception::class);
        bridge::execute('moodlia_open_page', ['destination' => 'assignment_grader', 'module_id' => (int) $page->cmid]);
    }

    /**
     * The transport capability does not grant access to another course's contents.
     */
    public function test_operation_permissions_are_not_bypassed(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('WebMCP transport', 'webmcptransport', '');
        $system = \context_system::instance();
        assign_capability('local/moodlia:useapi', CAP_ALLOW, $roleid, $system);
        role_assign($roleid, $user->id, $system);
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $this->setUser($user);
        $_POST['sesskey'] = sesskey();
        try {
            $this->expectException(\moodle_exception::class);
            bridge::execute('get_course_contents', ['course_id' => (int) $course->id]);
        } finally {
            unset($_POST['sesskey']);
        }
    }

    /**
     * Navigation may not substitute a different course for a module's parent course.
     */
    public function test_navigation_rejects_a_mismatched_course(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('webmcp_enabled', 1, 'local_moodlia');
        $first = $this->getDataGenerator()->create_course();
        $second = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $second->id]);
        $this->expectException(\invalid_parameter_exception::class);
        bridge::execute('moodlia_open_page', [
            'destination' => 'activity', 'course_id' => (int) $first->id, 'module_id' => (int) $page->cmid,
        ]);
    }
}
