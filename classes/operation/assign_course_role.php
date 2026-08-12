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
 * MoodlIA plugin implementation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Assign a supported role archetype in a course context.
 */
class assign_course_role {
    public static function execute(int $courseid, int $userid, string $rolearchetype = 'student'): array {
        admin_tools::require_role_api();

        $course = course_tools::get_course($courseid);
        $context = \context_course::instance((int) $course->id);
        $user = admin_tools::get_user($userid);
        $roleid = admin_tools::resolve_course_role_id($context, $rolearchetype);

        role_assign($roleid, (int) $user->id, $context->id);
        return [
            'course_id' => (int) $course->id,
            'user_id' => (int) $user->id,
            'role_id' => $roleid,
            'role_archetype' => trim($rolearchetype) ?: 'student',
            'assigned' => true,
            'roles' => enrolment_tools::get_user_role_shortnames($context, (int) $user->id),
        ];
    }
}
