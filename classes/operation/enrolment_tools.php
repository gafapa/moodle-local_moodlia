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
 * Shared enrolment helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for enrolment operations.
 */
class enrolment_tools {
    /**
     * Load Moodle enrolment APIs.
     */
    public static function require_enrolment_api(): void {
        global $CFG;

        require_once($CFG->libdir . '/enrollib.php');
        require_once($CFG->libdir . '/accesslib.php');
    }

    /**
     * Return the manual enrolment plugin.
     *
     * @return \enrol_plugin
     */
    public static function get_manual_plugin(): \enrol_plugin {
        self::require_enrolment_api();

        $plugin = enrol_get_plugin('manual');
        if (!$plugin) {
            throw new \invalid_parameter_exception('Manual enrolment is not available on this Moodle site.');
        }

        return $plugin;
    }

    /**
     * Return an enabled manual enrolment instance for the course.
     *
     * @param \stdClass $course Moodle course.
     * @return \stdClass
     */
    public static function get_manual_instance(\stdClass $course): \stdClass {
        $plugin = self::get_manual_plugin();
        $instance = self::find_manual_instance($course);
        if ($instance) {
            return $instance;
        }

        $plugin->add_default_instance($course);
        $instance = self::find_manual_instance($course);
        if ($instance) {
            return $instance;
        }

        $plugin->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
        $instance = self::find_manual_instance($course);
        if (!$instance) {
            throw new \moodle_exception('Manual enrolment is not available for this course.');
        }

        return $instance;
    }

    /**
     * Find an enabled manual enrolment instance.
     *
     * @param \stdClass $course Moodle course.
     * @return \stdClass|null
     */
    private static function find_manual_instance(\stdClass $course): ?\stdClass {
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual' && (int) $instance->status === ENROL_INSTANCE_ENABLED) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * Load a Moodle user.
     *
     * @param int $userid Moodle user id.
     * @return \stdClass
     */
    public static function get_user(int $userid): \stdClass {
        if ($userid <= 0) {
            throw new \invalid_parameter_exception('user_id must be a positive integer.');
        }

        return \core_user::get_user($userid, '*', MUST_EXIST);
    }

    /**
     * Resolve a role id from a supported role archetype.
     *
     * @param \context_course $context Moodle course context.
     * @param string $rolearchetype Role archetype.
     * @return int
     */
    public static function resolve_role_id(\context_course $context, string $rolearchetype): int {
        self::require_enrolment_api();

        $rolearchetype = trim($rolearchetype) ?: 'student';
        if (!in_array($rolearchetype, ['student', 'teacher', 'editingteacher'], true)) {
            throw new \invalid_parameter_exception('role_archetype must be one of: student, teacher, editingteacher.');
        }

        $roles = get_archetype_roles($rolearchetype);
        if (!$roles) {
            throw new \invalid_parameter_exception('No Moodle role exists for role_archetype=' . $rolearchetype . '.');
        }

        $role = reset($roles);
        $roleid = (int) $role->id;
        $assignable = get_assignable_roles($context);
        if (!array_key_exists($roleid, $assignable)) {
            throw new \required_capability_exception($context, 'moodle/role:assign', 'nopermissions', '');
        }

        return $roleid;
    }

    /**
     * Return role shortnames assigned to a user in a course context.
     *
     * @param \context_course $context Moodle course context.
     * @param int $userid Moodle user id.
     * @return array
     */
    public static function get_user_role_shortnames(\context_course $context, int $userid): array {
        self::require_enrolment_api();

        $roles = get_user_roles($context, $userid, false);
        $shortnames = [];
        foreach ($roles as $role) {
            if (!empty($role->shortname)) {
                $shortnames[] = (string) $role->shortname;
            }
        }

        return array_values(array_unique($shortnames));
    }

    /**
     * Return the canonical enrolled user response shape.
     *
     * @param \context_course $context Moodle course context.
     * @param \stdClass $user Moodle user.
     * @return array
     */
    public static function user_to_response(\context_course $context, \stdClass $user): array {
        return [
            'user_id' => (int) $user->id,
            'username' => (string) $user->username,
            'fullname' => fullname($user),
            'email' => (string) ($user->email ?? ''),
            'roles' => self::get_user_role_shortnames($context, (int) $user->id),
        ];
    }
}
