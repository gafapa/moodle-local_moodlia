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
 * Create section operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course section through Moodle core APIs.
 */
class create_section {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param string $summary Summary.
     * @param int $position Position.
     * @param bool $visible Visible.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        string $summary = '',
        int $position = 0,
        bool $visible = true
    ): array {
        $course = section_tools::get_course($courseid);

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }
        if ($position < 0) {
            throw new \invalid_parameter_exception('position must be zero or a positive integer.');
        }

        $targetposition = $position === 0 ? count(get_fast_modinfo($course)->get_section_info_all()) : $position;
        $section = course_create_section($course, $targetposition);
        course_update_section($course, $section, [
            'name' => $name,
            'summary' => $summary,
            'summaryformat' => FORMAT_PLAIN,
            'visible' => $visible ? 1 : 0,
        ]);

        $section = section_tools::reload_section($course, (int) $section->id);

        return section_tools::to_response($course, $section);
    }
}
