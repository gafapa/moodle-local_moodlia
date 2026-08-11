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
 * Get database entries operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Lists Moodle Database activity entries through Moodle external APIs.
 */
class get_data_entries {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Database course module id.
     * @param string $search Search text.
     * @param bool $includecontents Include field contents.
     * @param int $page Page number.
     * @param int $perpage Page size.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $search = '',
        bool $includecontents = true,
        int $page = 0,
        int $perpage = 20
    ): array {
        data_tools::require_data_api();

        $course = course_tools::get_course($courseid);
        $cm = data_tools::get_data_module($course, $moduleid);
        $result = data_tools::get_entries($cm, $search, $includecontents, $page, $perpage);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'data_id' => (int) $cm->instance,
            'count' => $result['count'],
            'entries' => $result['entries'],
        ];
    }
}
