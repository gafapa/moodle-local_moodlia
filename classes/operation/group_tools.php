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
 * Shared group helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for group operations.
 */
class group_tools {
    /**
     * Load Moodle group APIs.
     */
    public static function require_group_api(): void {
        global $CFG;

        require_once($CFG->libdir . '/grouplib.php');
        require_once($CFG->dirroot . '/group/lib.php');
    }

    /**
     * Load a course group and verify that it belongs to the expected course.
     *
     * @param int $courseid Moodle course id.
     * @param int $groupid Moodle group id.
     * @return \stdClass
     */
    public static function get_group(int $courseid, int $groupid): \stdClass {
        self::require_group_api();

        if ($groupid <= 0) {
            throw new \invalid_parameter_exception('group_id must be a positive integer.');
        }

        $group = groups_get_group($groupid, '*', MUST_EXIST);
        if ((int) $group->courseid !== $courseid) {
            throw new \invalid_parameter_exception('group_id must belong to course_id.');
        }

        return $group;
    }

    /**
     * Load a course grouping and verify that it belongs to the expected course.
     *
     * @param int $courseid Moodle course id.
     * @param int $groupingid Moodle grouping id.
     * @return \stdClass
     */
    public static function get_grouping(int $courseid, int $groupingid): \stdClass {
        self::require_group_api();

        if ($groupingid <= 0) {
            throw new \invalid_parameter_exception('grouping_id must be a positive integer.');
        }

        $grouping = groups_get_grouping($groupingid, '*', MUST_EXIST);
        if ((int) $grouping->courseid !== $courseid) {
            throw new \invalid_parameter_exception('grouping_id must belong to course_id.');
        }

        return $grouping;
    }

    /**
     * Return the canonical group response shape.
     *
     * @param \stdClass $group Moodle group.
     * @return array
     */
    public static function to_response(\stdClass $group): array {
        return [
            'group_id' => (int) $group->id,
            'course_id' => (int) $group->courseid,
            'name' => format_string($group->name, true, ['context' => \context_course::instance($group->courseid)]),
            'description' => (string) ($group->description ?? ''),
            'idnumber' => (string) ($group->idnumber ?? ''),
        ];
    }

    /**
     * Return the canonical grouping response shape.
     *
     * @param \stdClass $grouping Moodle grouping.
     * @return array
     */
    public static function grouping_to_response(\stdClass $grouping): array {
        return [
            'grouping_id' => (int) $grouping->id,
            'course_id' => (int) $grouping->courseid,
            'name' => format_string($grouping->name, true, ['context' => \context_course::instance($grouping->courseid)]),
            'description' => (string) ($grouping->description ?? ''),
            'idnumber' => (string) ($grouping->idnumber ?? ''),
        ];
    }

    /**
     * Return the canonical group member response shape.
     *
     * @param \stdClass $user Moodle user.
     * @return array
     */
    public static function member_to_response(\stdClass $user): array {
        return [
            'user_id' => (int) $user->id,
            'username' => (string) ($user->username ?? ''),
            'fullname' => fullname($user),
            'email' => (string) ($user->email ?? ''),
        ];
    }

    /**
     * Return members of a group.
     *
     * @param int $groupid Moodle group id.
     * @return array
     */
    public static function get_members(int $groupid): array {
        self::require_group_api();

        $users = groups_get_members($groupid, 'u.id,u.username,u.firstname,u.lastname,u.email');
        $records = [];
        foreach ($users as $user) {
            $records[] = self::member_to_response($user);
        }

        return $records;
    }
}
