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
 * Visible courses operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns courses visible to the authenticated Moodle user.
 */
class get_courses {
    /**
     * Execute the operation.
     *
     * @param int $limit Maximum number of courses to return.
     * @return array
     */
    public static function execute(int $limit = 100): array {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $limit = max(1, min(200, $limit));
        $courses = \core_course_category::user_top()->get_courses([
            'recursive' => true,
            'summary' => false,
            'limit' => $limit,
        ]);

        $records = [];
        foreach ($courses as $course) {
            $records[] = [
                'id' => (int) $course->id,
                'shortname' => format_string($course->shortname, true, ['context' => \context_course::instance($course->id)]),
                'fullname' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
                'category_id' => (int) $course->category,
                'visible' => (bool) $course->visible,
                'url' => course_get_url($course)->out(false),
            ];
        }

        return [
            'courses' => $records,
        ];
    }
}
