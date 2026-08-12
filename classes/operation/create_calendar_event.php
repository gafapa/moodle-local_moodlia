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
 * Create course calendar event operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course calendar event.
 */
class create_calendar_event {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param string $description Description.
     * @param int $timestart Timestart.
     * @param int $timeduration Timeduration.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        string $description,
        int $timestart,
        int $timeduration = 0
    ): array {
        calendar_tools::require_calendar_api();
        course_tools::get_course($courseid);

        $data = calendar_tools::to_event_data($courseid, $name, $description, $timestart, $timeduration);
        $event = \calendar_event::create($data, true);

        if (!$event) {
            throw new \moodle_exception('eventerror', 'calendar');
        }

        return calendar_tools::to_response($event);
    }
}
