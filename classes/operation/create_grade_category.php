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
 * Create grade category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle gradebook category.
 */
class create_grade_category {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param int|null $aggregation Aggregation.
     * @return array
     */
    public static function execute(int $courseid, string $name, ?int $aggregation = null): array {
        gradebook_tools::require_gradebook_api();

        $course = course_tools::get_course($courseid);
        $category = new \grade_category((object) [
            'courseid' => (int) $course->id,
            'fullname' => trim($name),
        ], false);

        if (trim($name) === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        if ($aggregation !== null) {
            $category->aggregation = $aggregation;
        }

        $category->insert('local_moodlia');

        return gradebook_tools::grade_category_to_response(
            gradebook_tools::get_grade_category((int) $course->id, (int) $category->id)
        );
    }
}
