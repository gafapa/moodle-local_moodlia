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
 * Get groupings operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle course groupings.
 */
class get_groupings {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @return array
     */
    public static function execute(int $courseid): array {
        $course = course_tools::get_course($courseid);
        group_tools::require_group_api();

        $records = [];
        $groupings = groups_get_all_groupings((int) $course->id);
        foreach ($groupings ?: [] as $grouping) {
            $records[] = group_tools::grouping_to_response($grouping);
        }

        return [
            'course_id' => (int) $course->id,
            'groupings' => $records,
        ];
    }
}
