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
 * Update database entry operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Updates an entry in a Moodle Database activity through Moodle external APIs.
 */
class update_data_entry {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Database course module id.
     * @param int $entryid Database entry id.
     * @param array $values Entry values keyed by field name or field id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $entryid, array $values): array {
        data_tools::require_data_api();

        $course = course_tools::get_course($courseid);
        $cm = data_tools::get_data_module($course, $moduleid);
        data_tools::get_entry($cm, $entryid, false);

        $data = data_tools::values_to_external($cm, $values);
        $result = \mod_data_external::update_entry($entryid, $data);
        if (empty($result['updated'])) {
            throw new \moodle_exception('noaccess', 'data');
        }

        return data_tools::get_entry($cm, $entryid, true);
    }
}
