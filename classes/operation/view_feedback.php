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
 * View feedback operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Registers a Moodle Feedback view through Moodle Feedback external APIs.
 */
class view_feedback {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Feedback course module id.
     * @param bool $moduleviewed Mark module viewed for completion tracking.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, bool $moduleviewed = false): array {
        feedback_tools::require_feedback_api();

        $course = course_tools::get_course($courseid);
        $cm = feedback_tools::get_feedback_module($course, $moduleid);
        $result = \mod_feedback_external::view_feedback((int) $cm->instance, $moduleviewed, 0);

        return [
            'feedback_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'viewed' => (bool) ($result['status'] ?? false),
            'warnings' => feedback_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
