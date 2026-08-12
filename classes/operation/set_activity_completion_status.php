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
 * Set activity completion status operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Marks a manually completable activity as complete or incomplete for the current user.
 */
class set_activity_completion_status {
    /**
     * Execute the operation.
     *
     * @param int $moduleid Moodle course module id.
     * @param bool $completed Desired completion state.
     * @return array
     */
    public static function execute(int $moduleid, bool $completed): array {
        completion_tools::require_completion_api();
        module_tools::require_module_api();

        if ($moduleid <= 0) {
            throw new \invalid_parameter_exception('module_id must be a positive integer.');
        }

        $cmrecord = get_coursemodule_from_id('', $moduleid, 0, false, MUST_EXIST);
        $course = course_tools::get_course((int) $cmrecord->course);
        $cm = module_tools::get_course_module($course, $moduleid);
        $result = \core_completion_external::update_activity_completion_status_manually((int) $cm->id, $completed);
        $result = completion_tools::to_array($result);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'completed' => (bool) ($result['status'] ?? $completed),
            'warnings' => completion_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
