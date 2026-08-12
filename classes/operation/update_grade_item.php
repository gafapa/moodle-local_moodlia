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
 * Update manual grade item operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a manual Moodle gradebook item.
 */
class update_grade_item {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $itemid Itemid.
     * @param string|null $name Name.
     * @param float|null $grademax Grademax.
     * @param float|null $grademin Grademin.
     * @param float|null $gradepass Gradepass.
     * @param int|null $categoryid Categoryid.
     * @param bool|null $hidden Hidden.
     * @param bool|null $locked Locked.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $itemid,
        ?string $name = null,
        ?float $grademax = null,
        ?float $grademin = null,
        ?float $gradepass = null,
        ?int $categoryid = null,
        ?bool $hidden = null,
        ?bool $locked = null
    ): array {
        $course = course_tools::get_course($courseid);
        $item = gradebook_tools::get_grade_item((int) $course->id, $itemid);
        gradebook_tools::require_manual_grade_item($item);

        if ($name !== null) {
            if (trim($name) === '') {
                throw new \invalid_parameter_exception('name must not be empty.');
            }
            $item->itemname = trim($name);
        }
        if ($categoryid !== null) {
            gradebook_tools::get_grade_category((int) $course->id, $categoryid);
            $item->categoryid = $categoryid;
        }
        if ($grademin !== null) {
            $item->grademin = $grademin;
        }
        if ($grademax !== null) {
            $item->grademax = $grademax;
        }
        if ((float) $item->grademax <= (float) $item->grademin) {
            throw new \invalid_parameter_exception('grade_max must be greater than grade_min.');
        }
        if ($gradepass !== null) {
            $item->gradepass = $gradepass;
        }
        if ($hidden !== null) {
            $item->hidden = $hidden ? 1 : 0;
        }
        if ($locked !== null) {
            $item->locked = $locked ? time() : 0;
        }

        $item->update('local_moodlia');

        return gradebook_tools::manual_grade_item_to_response(
            gradebook_tools::get_grade_item((int) $course->id, $itemid)
        );
    }
}
