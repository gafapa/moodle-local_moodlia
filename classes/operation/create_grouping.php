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
 * Create grouping operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course grouping.
 */
class create_grouping {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param string $name Grouping name.
     * @param string $description Grouping description.
     * @param string $idnumber Optional grouping idnumber.
     * @return array
     */
    public static function execute(int $courseid, string $name, string $description = '', string $idnumber = ''): array {
        $course = course_tools::get_course($courseid);
        group_tools::require_group_api();

        $data = (object) [
            'courseid' => (int) $course->id,
            'name' => trim($name),
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'idnumber' => trim($idnumber),
        ];

        if ($data->name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        $groupingid = groups_create_grouping($data);
        $grouping = group_tools::get_grouping((int) $course->id, (int) $groupingid);

        return group_tools::grouping_to_response($grouping);
    }
}
