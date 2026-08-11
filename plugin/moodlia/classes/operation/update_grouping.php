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
 * Update grouping operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Updates a Moodle course grouping.
 */
class update_grouping {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $groupingid Moodle grouping id.
     * @param string|null $name Grouping name.
     * @param string|null $description Grouping description.
     * @param string|null $idnumber Optional grouping idnumber.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $groupingid,
        ?string $name = null,
        ?string $description = null,
        ?string $idnumber = null
    ): array {
        $course = course_tools::get_course($courseid);
        $grouping = group_tools::get_grouping((int) $course->id, $groupingid);

        $data = (object) [
            'id' => (int) $grouping->id,
            'courseid' => (int) $course->id,
            'name' => $name !== null ? trim($name) : $grouping->name,
            'description' => $description !== null ? $description : (string) ($grouping->description ?? ''),
            'descriptionformat' => FORMAT_HTML,
            'idnumber' => $idnumber !== null ? trim($idnumber) : (string) ($grouping->idnumber ?? ''),
        ];

        if ($data->name === '') {
            throw new \invalid_parameter_exception('name must not be empty.');
        }

        groups_update_grouping($data);

        return group_tools::grouping_to_response(group_tools::get_grouping((int) $course->id, (int) $grouping->id));
    }
}
