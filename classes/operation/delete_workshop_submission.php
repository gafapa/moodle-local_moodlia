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
 * Delete workshop submission operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes a Moodle Workshop submission through Moodle external APIs.
 */
class delete_workshop_submission {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param int $submissionid Workshop submission id.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $submissionid): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        workshop_tools::get_submission($cm, $submissionid);
        $result = \mod_workshop_external::delete_submission($submissionid);
        if (empty($result['status'])) {
            throw new \moodle_exception('nopermissions', 'error', '', 'delete submission');
        }
        rebuild_course_cache($course->id, true);

        return [
            'deleted' => true,
            'id' => $submissionid,
        ];
    }
}
