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
 * Manual user enrolment operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Enrol a user in a course with Moodle manual enrolment.
 */
class enrol_user {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $userid Userid.
     * @param string $rolearchetype Rolearchetype.
     * @return array
     */
    public static function execute(int $courseid, int $userid, string $rolearchetype = 'student'): array {
        $course = course_tools::get_course($courseid);
        $context = \context_course::instance($course->id);
        $user = enrolment_tools::get_user($userid);
        $roleid = enrolment_tools::resolve_role_id($context, $rolearchetype);
        $instance = enrolment_tools::get_manual_instance($course);
        $plugin = enrolment_tools::get_manual_plugin();

        $plugin->enrol_user($instance, (int) $user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);

        return [
            'course_id' => (int) $course->id,
            'user_id' => (int) $user->id,
            'role_id' => $roleid,
            'role_archetype' => $rolearchetype,
            'enrolled' => true,
            'user' => enrolment_tools::user_to_response($context, $user),
        ];
    }
}
