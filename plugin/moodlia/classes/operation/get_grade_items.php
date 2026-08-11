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
 * Grade items operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Returns Moodle gradebook items for a course.
 */
class get_grade_items {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @return array
     */
    public static function execute(int $courseid): array {
        gradebook_tools::require_gradebook_api();

        $course = course_tools::get_course($courseid);
        $result = \core_grades\external\get_gradeitems::execute((int) $course->id);
        $warnings = $result['warnings'] ?? [];
        gradebook_tools::fail_on_warnings($warnings);

        $items = [];
        foreach (($result['gradeItems'] ?? []) as $item) {
            $items[] = gradebook_tools::grade_item_to_response($item);
        }

        return [
            'course_id' => (int) $course->id,
            'items' => $items,
        ];
    }
}
