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
 * Update course category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle course category.
 */
class update_course_category {
    /**
     * Execute the operation.
     *
     * @param int $categoryid Categoryid.
     * @param string|null $name Name.
     * @param bool|null $visible Visible.
     * @return array
     */
    public static function execute(int $categoryid, ?string $name = null, ?bool $visible = null): array {
        $category = course_tools::get_category($categoryid);
        $data = [
            'id' => (int) $category->id,
        ];

        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name must not be empty.');
            }
            $data['name'] = $name;
        }

        if ($visible !== null) {
            $data['visible'] = $visible ? 1 : 0;
        }

        if (count($data) > 1) {
            $category->update($data);
            $category = course_tools::get_category($categoryid);
        }

        return course_tools::category_to_response($category);
    }
}
