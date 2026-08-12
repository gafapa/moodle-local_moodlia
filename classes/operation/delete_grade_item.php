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
 * Delete manual grade item operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Deletes a manual Moodle gradebook item.
 */
class delete_grade_item {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $itemid Grade item id.
     * @return array
     */
    public static function execute(int $courseid, int $itemid): array {
        $course = course_tools::get_course($courseid);
        $item = gradebook_tools::get_grade_item((int) $course->id, $itemid);
        gradebook_tools::require_manual_grade_item($item);
        $item->delete('local_moodlia');

        return [
            'course_id' => (int) $course->id,
            'item_id' => $itemid,
            'deleted' => true,
        ];
    }
}
