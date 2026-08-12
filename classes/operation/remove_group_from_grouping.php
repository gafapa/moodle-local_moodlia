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
 * Remove group from grouping operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Removes a Moodle course group from a grouping.
 */
class remove_group_from_grouping {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $groupingid Groupingid.
     * @param int $groupid Groupid.
     * @return array
     */
    public static function execute(int $courseid, int $groupingid, int $groupid): array {
        $course = course_tools::get_course($courseid);
        $grouping = group_tools::get_grouping((int) $course->id, $groupingid);
        $group = group_tools::get_group((int) $course->id, $groupid);

        groups_unassign_grouping((int) $grouping->id, (int) $group->id);

        return [
            'course_id' => (int) $course->id,
            'grouping_id' => (int) $grouping->id,
            'group_id' => (int) $group->id,
            'removed' => true,
        ];
    }
}
