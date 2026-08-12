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
 * Add group member operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Adds a user to a Moodle course group.
 */
class add_group_member {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $groupid Groupid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $courseid, int $groupid, int $userid): array {
        $course = course_tools::get_course($courseid);
        $group = group_tools::get_group((int) $course->id, $groupid);
        $user = enrolment_tools::get_user($userid);

        groups_add_member((int) $group->id, (int) $user->id);

        return [
            'course_id' => (int) $course->id,
            'group_id' => (int) $group->id,
            'user_id' => (int) $user->id,
            'added' => true,
        ];
    }
}
