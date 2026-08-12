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
 * Get course Lessons operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists Lesson activities in a Moodle course through Moodle Lesson external APIs.
 */
class get_course_lessons {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @return array
     */
    public static function execute(int $courseid): array {
        lesson_tools::require_lesson_api();

        $course = course_tools::get_course($courseid);
        $result = \mod_lesson_external::get_lessons_by_courses([(int) $course->id]);

        return lesson_tools::course_lessons_to_response($course, $result);
    }
}
