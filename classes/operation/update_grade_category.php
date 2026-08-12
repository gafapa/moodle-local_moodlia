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
     * @param int $courseid Moodle course id.
     * @param int $categoryid Grade category id.
     * @param string|null $name Optional category name.
     * @param int|null $aggregation Optional aggregation constant.
     * @param bool|null $hidden Optional hidden state.
     * @return array
     */
    public static function execute(int $courseid, int $categoryid, ?string $name = null, ?int $aggregation = null, ?bool $hidden = null): array {
        $course = course_tools::get_course($courseid);
        $category = gradebook_tools::get_grade_category((int) $course->id, $categoryid);

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

        $category->update('local_moodlia');

        return gradebook_tools::grade_category_to_response(
            gradebook_tools::get_grade_category((int) $course->id, $categoryid)
        );
    }
}
