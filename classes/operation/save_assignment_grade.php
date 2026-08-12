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
 * Save assignment grade operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Saves a Moodle assignment grade and feedback comment.
 */
class save_assignment_grade {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Assignment course module id.
     * @param int $userid Student user id.
     * @param float $grade Assignment grade.
     * @param string $feedbackcomment Feedback comment HTML.
     * @param int $attemptnumber Attempt number, or -1 for latest.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $userid,
        float $grade,
        string $feedbackcomment = '',
        int $attemptnumber = -1
    ): array {
        module_tools::require_module_api();
        assignment_tools::require_assignment_api();

        if ($userid <= 0) {
            throw new \invalid_parameter_exception('user_id must be a positive integer.');
        }
        if ($grade < 0) {
            throw new \invalid_parameter_exception('grade must be greater than or equal to zero.');
        }

        $course = course_tools::get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, $moduleid);

        \mod_assign_external::save_grade(
            (int) $cm->instance,
            $userid,
            $grade,
            $attemptnumber,
            false,
            'released',
            false,
            [
                'assignfeedbackcomments_editor' => [
                    'text' => $feedbackcomment,
                    'format' => FORMAT_HTML,
                ],
            ],
            []
        );

        return assignment_tools::get_submission_status($course, $cm, $userid);
    }
}
