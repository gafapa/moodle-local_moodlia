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
 * Create group operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course group.
 */
class create_group {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $idnumber Idnumber.
     * @param string $descriptionformat Descriptionformat.
     * @param string $visibility Visibility.
     * @param bool $participation Participation.
     * @param string $enrolmentkey Enrolmentkey.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        string $description = '',
        string $idnumber = '',
        string $descriptionformat = 'html',
        string $visibility = 'all',
        bool $participation = true,
        string $enrolmentkey = ''
    ): array {
        $course = course_tools::get_course($courseid);
        group_tools::require_group_api();

        $visibilityconstant = group_tools::visibility_to_constant($visibility);
        $data = (object) [
            'courseid' => (int) $course->id,
            'name' => trim($name),
            'description' => $description,
            'descriptionformat' => text_format_tools::to_constant($descriptionformat, 'description_format'),
            'idnumber' => trim($idnumber),
            'visibility' => $visibilityconstant,
            'participation' => group_tools::participation_for($visibilityconstant, $participation),
            'enrolmentkey' => trim($enrolmentkey),
        ];

        if ($data->name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }
        group_tools::require_unique_enrolment_key((int) $course->id, $data->enrolmentkey);

        $groupid = groups_create_group($data);
        $group = group_tools::get_group((int) $course->id, (int) $groupid);

        return group_tools::to_response($group);
    }
}
