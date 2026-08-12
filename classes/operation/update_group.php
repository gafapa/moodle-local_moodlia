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
 * Update group operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Updates a Moodle course group.
 */
class update_group {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $groupid Moodle group id.
     * @param string|null $name Group name.
     * @param string|null $description Group description.
     * @param string|null $idnumber Optional group idnumber.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $groupid,
        ?string $name = null,
        ?string $description = null,
        ?string $idnumber = null
    ): array {
        $course = course_tools::get_course($courseid);
        $group = group_tools::get_group((int) $course->id, $groupid);

        $data = (object) [
            'id' => (int) $group->id,
            'courseid' => (int) $course->id,
            'name' => $name !== null ? trim($name) : $group->name,
            'description' => $description !== null ? $description : (string) ($group->description ?? ''),
            'descriptionformat' => FORMAT_HTML,
            'idnumber' => $idnumber !== null ? trim($idnumber) : (string) ($group->idnumber ?? ''),
        ];

        if ($data->name === '') {
            throw new \invalid_parameter_exception('name must not be empty.');
        }

        groups_update_group($data);

        return group_tools::to_response(group_tools::get_group((int) $course->id, (int) $group->id));
    }
}
