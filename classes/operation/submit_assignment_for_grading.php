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
 * Submit assignment for grading operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Submits the current user's assignment attempt for grading.
 */
class submit_assignment_for_grading {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Assignment course module id.
     * @param bool $acceptsubmissionstatement Whether the submission statement is accepted.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, bool $acceptsubmissionstatement = true): array {
        module_tools::require_module_api();
        assignment_tools::require_assignment_api();

        $course = course_tools::get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, $moduleid);
        $result = \mod_assign_external::submit_for_grading((int) $cm->instance, $acceptsubmissionstatement);

        assignment_tools::fail_on_warnings($result['warnings'] ?? []);

        return assignment_tools::get_submission_status($course, $cm);
    }
}
