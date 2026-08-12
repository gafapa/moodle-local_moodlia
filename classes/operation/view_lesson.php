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
 * View Lesson operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Registers a Moodle Lesson view through Moodle Lesson external APIs.
 */
class view_lesson {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Lesson course module id.
     * @param string $password Optional Lesson password.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $password = ''): array {
        lesson_tools::require_lesson_api();

        $course = course_tools::get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, $moduleid);
        $result = \mod_lesson_external::view_lesson((int) $cm->instance, $password);

        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'viewed' => (bool) ($result['status'] ?? false),
            'warnings' => lesson_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
