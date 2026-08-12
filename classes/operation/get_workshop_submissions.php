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
 * Get workshop submissions operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Lists Moodle Workshop submissions through Moodle external APIs.
 */
class get_workshop_submissions {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param int $userid User id or 0.
     * @param int $groupid Group id or 0.
     * @param int $page Page number.
     * @param int $perpage Page size.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $userid = 0,
        int $groupid = 0,
        int $page = 0,
        int $perpage = 20
    ): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $result = \mod_workshop_external::get_submissions(
            (int) $cm->instance,
            max(0, $userid),
            max(0, $groupid),
            max(0, $page),
            max(0, $perpage)
        );
        $submissions = array_map(
            static fn($submission): array => workshop_tools::submission_to_response($cm, $submission),
            $result['submissions'] ?? []
        );

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'count' => count($submissions),
            'submissions' => $submissions,
        ];
    }
}
