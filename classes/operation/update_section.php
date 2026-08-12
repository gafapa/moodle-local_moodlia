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
 * Update section operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle course section through Moodle core APIs.
 */
class update_section {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int|null $sectionid Sectionid.
     * @param int|null $sectionnumber Sectionnumber.
     * @param string|null $name Name.
     * @param string|null $summary Summary.
     * @param bool|null $visible Visible.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?int $sectionid = null,
        ?int $sectionnumber = null,
        ?string $name = null,
        ?string $summary = null,
        ?bool $visible = null
    ): array {
        $course = section_tools::get_course($courseid);
        $section = section_tools::get_section($course, $sectionid, $sectionnumber);

        $data = [];
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty when provided.');
            }
            $data['name'] = $name;
        }

        if ($summary !== null) {
            $data['summary'] = $summary;
            $data['summaryformat'] = FORMAT_PLAIN;
        }

        if ($visible !== null) {
            $data['visible'] = $visible ? 1 : 0;
        }

        if (!$data) {
            throw new \invalid_parameter_exception('At least one of name, summary, or visible is required.');
        }

        course_update_section($course, $section, $data);
        $section = section_tools::reload_section($course, (int) $section->id);

        return section_tools::to_response($course, $section);
    }
}
