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
 * MoodlIA plugin implementation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Set a course publishing state.
 */
class set_course_publish_state {
    public static function execute(int $courseid, string $publishstate): array {
        $publishstate = course_workflow_tools::normalise_publish_state($publishstate);
        $course = course_tools::get_course($courseid);
        $startdate = (int) ($course->startdate ?? 0);
        $enddate = (int) ($course->enddate ?? 0);
        if ($publishstate === 'archived' && $enddate === 0) {
            $enddate = time();
            if ($startdate > 0 && $enddate <= $startdate) {
                $enddate = $startdate + 1;
            }
        }

        $updated = update_course::execute(
            $courseid,
            null,
            null,
            course_workflow_tools::visible_for_state($publishstate),
            null,
            null,
            null,
            null,
            null,
            null,
            $publishstate === 'archived' ? $enddate : null
        );

        return [
            'course_id' => $courseid,
            'publish_state' => $publishstate,
            'visible' => (bool) $updated['visible'],
            'course_json' => course_workflow_tools::encode_json($updated),
        ];
    }
}
