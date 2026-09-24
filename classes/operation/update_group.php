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

/**
 * Updates a Moodle course group.
 */
class update_group {
    /**
     * Execute the operation. Null arguments keep the stored value.
     *
     * @param int $courseid Courseid.
     * @param int $groupid Groupid.
     * @param string|null $name Name.
     * @param string|null $description Description.
     * @param string|null $idnumber Idnumber.
     * @param string|null $descriptionformat Descriptionformat.
     * @param string|null $visibility Visibility.
     * @param bool|null $participation Participation.
     * @param string|null $enrolmentkey Enrolmentkey.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $groupid,
        ?string $name = null,
        ?string $description = null,
        ?string $idnumber = null,
        ?string $descriptionformat = null,
        ?string $visibility = null,
        ?bool $participation = null,
        ?string $enrolmentkey = null
    ): array {
        $course = course_tools::get_course($courseid);
        $group = group_tools::get_group((int) $course->id, $groupid);

        $visibilityconstant = $visibility === null
            ? (int) ($group->visibility ?? GROUPS_VISIBILITY_ALL)
            : group_tools::visibility_to_constant($visibility);
        $visibilitychanged = $visibilityconstant !== (int) ($group->visibility ?? GROUPS_VISIBILITY_ALL);
        if ($visibilitychanged && groups_get_members((int) $group->id, 'u.id')) {
            throw new \invalid_parameter_exception('visibility cannot change while the group has members.');
        }
        $requestedparticipation = $participation ?? (bool) ($group->participation ?? true);

        $data = (object) [
            'id' => (int) $group->id,
            'courseid' => (int) $course->id,
            'name' => $name !== null ? trim($name) : $group->name,
            'description' => $description !== null ? $description : (string) ($group->description ?? ''),
            'descriptionformat' => $descriptionformat !== null
                ? text_format_tools::to_constant($descriptionformat, 'description_format')
                : (int) ($group->descriptionformat ?? FORMAT_HTML),
            'idnumber' => $idnumber !== null ? trim($idnumber) : (string) ($group->idnumber ?? ''),
            'visibility' => $visibilityconstant,
            'participation' => group_tools::participation_for($visibilityconstant, $requestedparticipation),
            'enrolmentkey' => $enrolmentkey !== null ? trim($enrolmentkey) : (string) ($group->enrolmentkey ?? ''),
        ];

        if ($data->name === '') {
            throw new \invalid_parameter_exception('name must not be empty.');
        }
        group_tools::require_unique_enrolment_key((int) $course->id, $data->enrolmentkey, (int) $group->id);

        groups_update_group($data);

        return group_tools::to_response(group_tools::get_group((int) $course->id, (int) $group->id));
    }
}
