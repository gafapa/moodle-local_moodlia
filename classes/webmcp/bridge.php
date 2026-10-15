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
 * WebMCP operation adapter.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\webmcp;

use core_external\external_api;
use local_moodlia\mcp\manifest;

/**
 * Session-authenticated adapter over the existing MoodlIA operation contract.
 */
final class bridge {
    /**
     * Require the optional WebMCP feature and the existing API capability.
     */
    public static function require_access(): void {
        if (!get_config('local_moodlia', 'webmcp_enabled')) {
            throw new \moodle_exception('webmcpdisabled', 'local_moodlia');
        }
        require_login(null, false, null, false, true);
        require_capability('local/moodlia:useapi', \context_system::instance());
    }

    /**
     * Upload one file to a fresh user-owned draft using Moodle's upload repository.
     *
     * @return array
     */
    public static function upload(): array {
        global $CFG, $USER;
        self::require_access();
        require_sesskey();
        require_once($CFG->dirroot . '/repository/lib.php');
        $context = \context_user::instance($USER->id);
        $repositories = \repository::get_instances([
            'type' => 'upload', 'currentcontext' => $context, 'userid' => $USER->id,
        ]);
        if (!$repositories) {
            throw new \moodle_exception('nopermissiontoaccess', 'repository');
        }
        $repository = reset($repositories);
        $repository->check_capability();
        // Fresh drafts prevent collisions or overwriting another operation's files.
        $itemid = file_get_unused_draft_itemid();
        $maximum = get_user_max_upload_file_size($context, $CFG->maxbytes);
        $uploaded = $repository->process_upload('', $maximum, '*', '/', $itemid);
        return ['draft_item_id' => (int) $uploaded['id'], 'filename' => $uploaded['file']];
    }

    /**
     * Publish the full operation catalogue, independently of the current page.
     *
     * @return array
     */
    public static function tools(): array {
        $functions = [];
        require(__DIR__ . '/../../db/services.php');
        $tools = [];
        foreach (manifest::tools() as $tool) {
            $definition = $functions['local_moodlia_' . $tool['name']] ?? [];
            if (empty($definition['ajax'])) {
                continue;
            }
            $tool['annotations'] = [
                'readOnlyHint' => ($definition['type'] ?? 'write') === 'read',
                'consequentialHint' => ($definition['type'] ?? 'write') !== 'read',
                'untrustedContentHint' => true,
            ];
            if (empty($tool['inputSchema']['properties'])) {
                $tool['inputSchema']['properties'] = new \stdClass();
            }
            $tools[] = $tool;
        }
        $tools[] = [
            'name' => 'moodlia_open_page',
            'description' => 'Show a Moodle course, activity, assignment grader, or gradebook in the current browser tab. '
                . 'Use this to visibly show the user what you are working on. Unsaved edits should be preserved first.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'destination' => ['type' => 'string', 'enum' => ['course', 'activity', 'assignment_grader', 'gradebook']],
                    'course_id' => ['type' => 'integer', 'minimum' => 1],
                    'module_id' => ['type' => 'integer', 'minimum' => 1],
                ],
                'required' => ['destination'],
                'additionalProperties' => false,
            ],
            'annotations' => ['readOnlyHint' => false, 'consequentialHint' => false, 'untrustedContentHint' => false],
        ];
        return $tools;
    }

    /**
     * Normalize JSON-object fields without flattening Moodle array parameters.
     *
     * @param array $arguments Arguments.
     * @param array $schema Schema.
     * @return array
     */
    public static function normalize_arguments(array $arguments, array $schema): array {
        $properties = (array) ($schema['properties'] ?? []);
        foreach ($schema['required'] ?? [] as $name) {
            if (!array_key_exists($name, $arguments) || $arguments[$name] === null) {
                throw new \invalid_parameter_exception('Missing tool argument: ' . $name);
            }
        }
        $normalized = [];
        foreach ($arguments as $name => $value) {
            if (!array_key_exists($name, $properties)) {
                throw new \invalid_parameter_exception('Unknown tool argument: ' . $name);
            }
            if ($value === null) {
                continue;
            }
            if (isset($properties[$name]['enum']) && !in_array($value, $properties[$name]['enum'], true)) {
                throw new \invalid_parameter_exception('Unsupported value for ' . $name);
            }
            if (($properties[$name]['type'] ?? '') === 'object') {
                if (
                    (!is_array($value) && !is_object($value)) ||
                    (is_array($value) && $value !== [] && array_is_list($value))
                ) {
                    throw new \invalid_parameter_exception('Expected a JSON object for ' . $name);
                }
                $value = json_encode($value === [] ? new \stdClass() : $value, JSON_THROW_ON_ERROR);
            }
            $normalized[$name] = $value;
        }
        return $normalized;
    }

    /**
     * Execute only a known operation through Moodle's external API validator.
     *
     * @param string $name Name.
     * @param array $arguments Arguments.
     * @return mixed
     */
    public static function execute(string $name, array $arguments) {
        self::require_access();
        foreach (self::tools() as $tool) {
            if ($tool['name'] !== $name) {
                continue;
            }
            $arguments = self::normalize_arguments($arguments, $tool['inputSchema']);
            if ($name === 'moodlia_open_page') {
                return self::navigation_target($arguments);
            }
            $result = external_api::call_external_function('local_moodlia_' . $name, $arguments, true);
            if (!empty($result['error'])) {
                throw new \moodle_exception('webmcpoperationfailed', 'local_moodlia', '', $result['exception']->message ?? '');
            }
            return $result['data'];
        }
        throw new \invalid_parameter_exception('Unknown MoodlIA WebMCP tool.');
    }

    /**
     * Resolve a Moodle-owned destination after checking its native access rules.
     *
     * @param array $arguments Arguments.
     * @return array
     */
    private static function navigation_target(array $arguments): array {
        $destination = $arguments['destination'] ?? '';
        foreach (['course_id', 'module_id'] as $name) {
            if (isset($arguments[$name]) && (!is_int($arguments[$name]) || $arguments[$name] < 1)) {
                throw new \invalid_parameter_exception('Expected a positive integer for ' . $name);
            }
        }
        $courseid = (int) ($arguments['course_id'] ?? 0);
        $moduleid = (int) ($arguments['module_id'] ?? 0);
        if (in_array($destination, ['activity', 'assignment_grader'], true) && $moduleid > 0) {
            $cm = get_coursemodule_from_id('', $moduleid, 0, false, MUST_EXIST);
            if ($courseid && $courseid !== (int) $cm->course) {
                throw new \invalid_parameter_exception('The activity does not belong to the requested course.');
            }
            $course = get_course($cm->course);
            require_login($course, false, $cm, false, true);
            external_api::validate_context(\context_module::instance($cm->id));
            $modinfo = get_fast_modinfo($course);
            if (!$modinfo->get_cm($cm->id)->uservisible) {
                throw new \moodle_exception('nopermissions', 'error', '', 'View this activity');
            }
            if ($destination === 'assignment_grader') {
                if ($cm->modname !== 'assign') {
                    throw new \invalid_parameter_exception('The grading page requires an assignment activity.');
                }
                require_capability('mod/assign:grade', \context_module::instance($cm->id));
            }
            $params = ['id' => $cm->id];
            if ($destination === 'assignment_grader') {
                $params['action'] = 'grader';
            }
            $url = new \moodle_url('/mod/' . $cm->modname . '/view.php', $params);
        } else if (in_array($destination, ['course', 'gradebook'], true) && $courseid > 0) {
            $course = get_course($courseid);
            require_login($course, false, null, false, true);
            $context = \context_course::instance($courseid);
            external_api::validate_context($context);
            require_capability('moodle/course:view', $context);
            if ($destination === 'gradebook') {
                require_capability('moodle/grade:viewall', $context);
            }
            $url = new \moodle_url($destination === 'course' ? '/course/view.php' : '/grade/report/grader/index.php', [
                'id' => $courseid,
            ]);
        } else {
            throw new \invalid_parameter_exception('Choose a supported destination and supply its course or module id.');
        }
        return ['url' => $url->out(false), 'destination' => $destination];
    }
}
