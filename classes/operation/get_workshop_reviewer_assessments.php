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
 * Get workshop reviewer assessments operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Lists Moodle Workshop assessments assigned to a reviewer through Moodle external APIs.
 */
class get_workshop_reviewer_assessments {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param int $userid User id or 0 for the current user.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $userid = 0): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $userid = max(0, $userid);
        $result = \mod_workshop_external::get_reviewer_assessments((int) $cm->instance, $userid);
        $assessments = workshop_tools::assessments_to_response($cm, $result['assessments'] ?? []);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'user_id' => $userid,
            'count' => count($assessments),
            'assessments' => $assessments,
        ];
    }
}
