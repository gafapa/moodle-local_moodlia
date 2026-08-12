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
 * Create manual grade item operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a manual Moodle gradebook item.
 */
class create_grade_item {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param string $name Grade item name.
     * @param float $grademax Maximum grade.
     * @param float $grademin Minimum grade.
     * @param float|null $gradepass Optional passing grade.
     * @param int|null $categoryid Optional grade category id.
     * @param bool|null $hidden Optional hidden state.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        float $grademax = 100.0,
        float $grademin = 0.0,
        ?float $gradepass = null,
        ?int $categoryid = null,
        ?bool $hidden = null
    ): array {
        gradebook_tools::require_gradebook_api();

        $course = course_tools::get_course($courseid);
        if (trim($name) === '') {
            throw new \invalid_parameter_exception('name is required.');
        }
        if ($grademax <= $grademin) {
            throw new \invalid_parameter_exception('grade_max must be greater than grade_min.');
        }
        if ($categoryid !== null) {
            gradebook_tools::get_grade_category((int) $course->id, $categoryid);
        }

        $item = new \grade_item((object) [
            'courseid' => (int) $course->id,
            'categoryid' => $categoryid,
            'itemtype' => 'manual',
            'itemname' => trim($name),
            'grademin' => $grademin,
            'grademax' => $grademax,
            'gradepass' => $gradepass ?? 0,
            'hidden' => $hidden === null ? 0 : ($hidden ? 1 : 0),
        ], false);
        $item->insert('local_moodlia');

        return gradebook_tools::manual_grade_item_to_response(
            gradebook_tools::get_grade_item((int) $course->id, (int) $item->id)
        );
    }
}
