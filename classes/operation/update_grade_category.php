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
 * Update grade category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle gradebook category.
 */
class update_grade_category {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $categoryid Categoryid.
     * @param string|null $name Name.
     * @param int|null $aggregation Aggregation.
     * @param bool|null $hidden Hidden.
     * @param float|null $gradepass Gradepass.
     * @param float|null $grademax Grademax.
     * @param bool|null $excludeemptygrades Excludeemptygrades.
     * @param int|null $keephighest Keephighest.
     * @param int|null $droplowest Droplowest.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $categoryid,
        ?string $name = null,
        ?int $aggregation = null,
        ?bool $hidden = null,
        ?float $gradepass = null,
        ?float $grademax = null,
        ?bool $excludeemptygrades = null,
        ?int $keephighest = null,
        ?int $droplowest = null
    ): array {
        $course = course_tools::get_course($courseid);
        $category = gradebook_tools::get_grade_category((int) $course->id, $categoryid);
        $totalitem = $category->get_grade_item();
        $effectivemax = $grademax ?? (float) $totalitem->grademax;
        $effectivepass = $gradepass ?? (float) $totalitem->gradepass;

        if ($effectivemax <= (float) $totalitem->grademin) {
            throw new \invalid_parameter_exception('grade_max must be greater than grade_min.');
        }
        if ($effectivepass < (float) $totalitem->grademin || $effectivepass > $effectivemax) {
            throw new \invalid_parameter_exception('grade_pass must be inside the category total grade range.');
        }

        if ($name !== null) {
            if (trim($name) === '') {
                throw new \invalid_parameter_exception('name must not be empty.');
            }
            $category->fullname = trim($name);
        }
        if ($aggregation !== null) {
            $category->aggregation = $aggregation;
        }
        if ($hidden !== null) {
            $category->hidden = $hidden ? 1 : 0;
        }
        if ($excludeemptygrades !== null) {
            $category->aggregateonlygraded = $excludeemptygrades ? 1 : 0;
        }
        if ($keephighest !== null) {
            if ($keephighest < 0) {
                throw new \invalid_parameter_exception('keep_highest must not be negative.');
            }
            $category->keephigh = $keephighest;
        }
        if ($droplowest !== null) {
            if ($droplowest < 0) {
                throw new \invalid_parameter_exception('drop_lowest must not be negative.');
            }
            $category->droplow = $droplowest;
        }

        $category->update('local_moodlia');

        if ($grademax !== null) {
            $totalitem->grademax = $grademax;
        }
        if ($gradepass !== null) {
            $totalitem->gradepass = $gradepass;
        }
        $totalitem->update('local_moodlia');

        return gradebook_tools::grade_category_to_response(
            gradebook_tools::get_grade_category((int) $course->id, $categoryid)
        );
    }
}
