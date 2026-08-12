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
 * Get workshop user plan operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Reads a Moodle Workshop user plan through Moodle external APIs.
 */
class get_workshop_user_plan {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $userid = 0): array {
        global $USER;

        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $resolveduserid = max(0, $userid);
        $result = \mod_workshop_external::get_user_plan((int) $cm->instance, $resolveduserid);

        return [
            'course_id' => (int) $course->id,
        ] + workshop_tools::user_plan_to_response($cm, $resolveduserid > 0 ? $resolveduserid : (int) $USER->id, $result);
    }
}
