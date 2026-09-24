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
     * @param int $courseid Courseid.
     * @param int $groupid Groupid.
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
     * @param int $courseid Courseid.
     * @param int $groupingid Groupingid.
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
     * @param \stdClass $group Group.
     * @return array
     */
    public static function to_response(\stdClass $group): array {
        self::require_group_api();

        return [
            'group_id' => (int) $group->id,
            'course_id' => (int) $group->courseid,
            'name' => format_string($group->name, true, ['context' => \context_course::instance($group->courseid)]),
            'description' => (string) ($group->description ?? ''),
            'description_format' => text_format_tools::to_name((int) ($group->descriptionformat ?? FORMAT_HTML)),
            'idnumber' => (string) ($group->idnumber ?? ''),
            'visibility' => self::visibility_to_name((int) ($group->visibility ?? GROUPS_VISIBILITY_ALL)),
            'participation' => (bool) ($group->participation ?? true),
            'has_enrolment_key' => trim((string) ($group->enrolmentkey ?? '')) !== '',
        ];
    }

    /**
     * Convert a public group visibility name to its Moodle constant.
     *
     * @param string $visibility Visibility.
     * @return int
     */
    public static function visibility_to_constant(string $visibility): int {
        self::require_group_api();

        $map = [
            'all' => GROUPS_VISIBILITY_ALL,
            'members' => GROUPS_VISIBILITY_MEMBERS,
            'own' => GROUPS_VISIBILITY_OWN,
            'none' => GROUPS_VISIBILITY_NONE,
        ];
        $key = strtolower(trim($visibility));
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('visibility must be one of: all, members, own, none.');
        }

        return $map[$key];
    }

    /**
     * Convert a Moodle group visibility constant to its public name.
     *
     * @param int $visibility Visibility.
     * @return string
     */
    public static function visibility_to_name(int $visibility): string {
        self::require_group_api();

        switch ($visibility) {
            case GROUPS_VISIBILITY_MEMBERS:
                return 'members';
            case GROUPS_VISIBILITY_OWN:
                return 'own';
            case GROUPS_VISIBILITY_NONE:
                return 'none';
            default:
                return 'all';
        }
    }

    /**
     * Resolve activity participation for a visibility; Moodle only allows it for visible groups.
     *
     * @param int $visibility Visibility.
     * @param bool $participation Participation.
     * @return int
     */
    public static function participation_for(int $visibility, bool $participation): int {
        self::require_group_api();

        if (!in_array($visibility, [GROUPS_VISIBILITY_ALL, GROUPS_VISIBILITY_MEMBERS], true)) {
            return 0;
        }

        return $participation ? 1 : 0;
    }

    /**
     * Require that a group enrolment key is not used by another group in the course.
     *
     * @param int $courseid Courseid.
     * @param string $enrolmentkey Enrolmentkey.
     * @param int $exceptgroupid Exceptgroupid.
     */
    public static function require_unique_enrolment_key(int $courseid, string $enrolmentkey, int $exceptgroupid = 0): void {
        global $DB;

        if ($enrolmentkey === '') {
            return;
        }
        $params = ['courseid' => $courseid, 'enrolmentkey' => $enrolmentkey, 'id' => $exceptgroupid];
        if ($DB->record_exists_select('groups', 'courseid = :courseid AND enrolmentkey = :enrolmentkey AND id <> :id', $params)) {
            throw new \invalid_parameter_exception('enrolment_key is already used by another group in this course.');
        }
    }

    /**
     * Return the canonical grouping response shape.
     *
     * @param \stdClass $grouping Grouping.
     * @return array
     */
    public static function grouping_to_response(\stdClass $grouping): array {
        self::require_group_api();
        $groups = groups_get_all_groups((int) $grouping->courseid, 0, (int) $grouping->id, 'g.id');

        return [
            'grouping_id' => (int) $grouping->id,
            'course_id' => (int) $grouping->courseid,
            'name' => format_string($grouping->name, true, ['context' => \context_course::instance($grouping->courseid)]),
            'description' => (string) ($grouping->description ?? ''),
            'idnumber' => (string) ($grouping->idnumber ?? ''),
            'group_ids' => array_values(array_map('intval', array_keys($groups ?: []))),
        ];
    }

    /**
     * Return the canonical group member response shape.
     *
     * @param \stdClass $user User.
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
     * @param int $groupid Groupid.
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
