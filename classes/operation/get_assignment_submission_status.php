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
 * Get assignment submission status operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns the current assignment submission status.
 */
class get_assignment_submission_status {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $userid = 0): array {
        module_tools::require_module_api();
        assignment_tools::require_assignment_api();

        $course = course_tools::get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, $moduleid);

        return assignment_tools::get_submission_status($course, $cm, $userid);
    }
}
