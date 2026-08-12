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
 * Update manual grade value operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a manual gradebook value for a user.
 */
class update_grade_value {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $itemid Itemid.
     * @param int $userid Userid.
     * @param float $grade Grade.
     * @param string|null $feedback Feedback.
     * @return array
     */
    public static function execute(int $courseid, int $itemid, int $userid, float $grade, ?string $feedback = null): array {
        $course = course_tools::get_course($courseid);
        $item = gradebook_tools::get_grade_item((int) $course->id, $itemid);
        gradebook_tools::require_manual_grade_item($item);
        admin_tools::get_user($userid);

        if ($grade < (float) $item->grademin || $grade > (float) $item->grademax) {
            throw new \invalid_parameter_exception('grade must be inside the grade item range.');
        }

        $item->update_final_grade($userid, $grade, 'local_moodlia', $feedback ?? '', FORMAT_HTML);

        return [
            'course_id' => (int) $course->id,
            'item_id' => $itemid,
            'user_id' => $userid,
            'grade' => $grade,
            'updated' => true,
        ];
    }
}
