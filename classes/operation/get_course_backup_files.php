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
 * List native Moodle course backup files operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists .mbz backup files visible to the current user.
 */
class get_course_backup_files {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param bool $includeprivate Includeprivate.
     * @return array
     */
    public static function execute(int $courseid = 0, bool $includeprivate = true): array {
        $result = course_backup_tools::list_backup_files($courseid, $includeprivate);

        return [
            'course_id' => (int) $result['course_id'],
            'count' => (int) $result['count'],
            'files_json' => course_workflow_tools::encode_json($result['files']),
        ];
    }
}
