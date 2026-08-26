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
 * Update grade item operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates safe gradebook settings for Moodle grade items.
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
     * @param float|null $weight Weight.
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
        ?bool $locked = null,
        ?float $weight = null
    ): array {
        $course = course_tools::get_course($courseid);
        $item = gradebook_tools::get_grade_item((int) $course->id, $itemid);

        $moduleowned = ($item->itemtype ?? '') === 'mod';
        if ($moduleowned && ($name !== null || $grademax !== null || $grademin !== null)) {
            throw new \invalid_parameter_exception(
                'Module-owned grade items support grade_pass, category_id, weight, hidden, and locked. Change names and grade ranges through the owning activity.'
            );
        }
        if (($item->itemtype ?? '') === 'course' && ($categoryid !== null || $weight !== null)) {
            throw new \invalid_parameter_exception('The course total cannot be moved or weighted inside another category.');
        }
        if (($item->itemtype ?? '') === 'category' && $categoryid !== null) {
            throw new \invalid_parameter_exception('Use update_grade_category to move or configure category totals.');
        }

        if ($name !== null) {
            if (trim($name) === '') {
                throw new \invalid_parameter_exception('name must not be empty.');
            }
            $item->itemname = trim($name);
        }
        if ($categoryid !== null) {
            gradebook_tools::get_grade_category((int) $course->id, $categoryid);
            $item->set_parent($categoryid);
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
        if ($weight !== null) {
            if ($weight < 0) {
                throw new \invalid_parameter_exception('weight must not be negative.');
            }
            $item->aggregationcoef2 = $weight;
            $item->weightoverride = 1;
        }

        if (($gradepass !== null || $grademin !== null || $grademax !== null)
            && ($item->gradepass < (float) $item->grademin || $item->gradepass > (float) $item->grademax)) {
            throw new \invalid_parameter_exception('grade_pass must be inside the grade item range.');
        }

        $item->update('local_moodlia');

        return gradebook_tools::manual_grade_item_to_response(
            gradebook_tools::get_grade_item((int) $course->id, $itemid)
        );
    }
}
