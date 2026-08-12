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
 * Download resource file operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns metadata for a file in a Moodle file resource through Moodle File API.
 */
class download_resource_file {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $fileid Fileid.
     * @param string $path Path.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $fileid = 0, string $path = ''): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_file_tools::get_resource_module($course, $moduleid);
        $file = module_file_tools::get_resource_file($cm, $fileid > 0 ? $fileid : null, $path !== '' ? $path : null);

        return module_file_tools::resource_file_to_response($cm, $file);
    }
}
