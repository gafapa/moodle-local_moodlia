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
 * Get feedback analysis operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle Feedback aggregated analysis through Moodle APIs.
 */
class get_feedback_analysis {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $groupid Groupid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $groupid = 0): array {
        feedback_tools::require_feedback_api();

        $course = course_tools::get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, $moduleid);
        $result = feedback_tools::get_analysis($cm, $groupid);

        return ['course_id' => (int) $course->id] + $result;
    }
}
