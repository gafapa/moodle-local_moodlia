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
 * Update feedback item operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates an item in a Moodle Feedback activity through Moodle item APIs.
 */
class update_feedback_item {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $itemid Itemid.
     * @param string|null $name Name.
     * @param string|null $definitionjson Definitionjson.
     * @param int|null $position Position.
     * @param string|null $label Label.
     * @param bool|null $required Required.
     * @param int|null $dependitemid Dependitemid.
     * @param string|null $dependvalue Dependvalue.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $itemid,
        ?string $name = null,
        ?string $definitionjson = null,
        ?int $position = null,
        ?string $label = null,
        ?bool $required = null,
        ?int $dependitemid = null,
        ?string $dependvalue = null
    ): array {
        $course = course_tools::get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, $moduleid);
        $existing = feedback_tools::get_item($cm, $itemid);
        $definition = $definitionjson === null ? [] : feedback_tools::decode_item_definition($definitionjson);

        return feedback_tools::save_item(
            $course,
            $cm,
            (string) $existing['type'],
            $name,
            $definition,
            $position,
            $label,
            $required,
            $dependitemid,
            $dependvalue,
            $existing
        );
    }
}
