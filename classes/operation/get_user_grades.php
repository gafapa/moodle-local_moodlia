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
 * User grades operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle gradebook grades for a user in a course.
 */
class get_user_grades {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $userid Moodle user id, or 0 for current user.
     * @param int $groupid Moodle group id, or 0 for all allowed groups.
     * @return array
     */
    public static function execute(int $courseid, int $userid = 0, int $groupid = 0): array {
        gradebook_tools::require_gradebook_api();

        if ($userid < 0) {
            throw new \invalid_parameter_exception('user_id must be zero or a positive integer.');
        }
        if ($groupid < 0) {
            throw new \invalid_parameter_exception('group_id must be zero or a positive integer.');
        }

        $course = course_tools::get_course($courseid);
        $result = \gradereport_user\external\user::get_grade_items((int) $course->id, $userid, $groupid);
        $warnings = $result['warnings'] ?? [];
        gradebook_tools::fail_on_warnings($warnings);

        $usergrades = $result['usergrades'][0] ?? [];
        $items = [];
        foreach (($usergrades['gradeitems'] ?? []) as $item) {
            $items[] = gradebook_tools::user_grade_item_to_response($item);
        }

        return [
            'course_id' => (int) $course->id,
            'user_id' => (int) ($usergrades['userid'] ?? $userid),
            'user_fullname' => (string) ($usergrades['userfullname'] ?? ''),
            'items' => $items,
        ];
    }
}
