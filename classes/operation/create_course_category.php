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
 * Create course category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course category.
 */
class create_course_category {
    /**
     * Execute the operation.
     *
     * @param string $name Name.
     * @param int $parentid Parentid.
     * @param bool $visible Visible.
     * @param bool $reuseexisting Reuseexisting.
     * @return array
     */
    public static function execute(string $name, int $parentid = 0, bool $visible = true, bool $reuseexisting = false): array {
        course_tools::require_course_api();

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        $parent = $parentid === 0 ? \core_course_category::top() : course_tools::get_category($parentid);

        if ($reuseexisting) {
            foreach ($parent->get_children() as $category) {
                if ((string) $category->name === $name) {
                    $response = course_tools::category_to_response($category);
                    $response['created'] = false;
                    return $response;
                }
            }
        }

        $category = \core_course_category::create([
            'name' => $name,
            'parent' => max(0, $parentid),
            'visible' => $visible ? 1 : 0,
        ]);

        $response = course_tools::category_to_response($category);
        $response['created'] = true;
        return $response;
    }
}
