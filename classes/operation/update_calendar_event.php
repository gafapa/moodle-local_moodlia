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
 * Update course calendar event operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Updates a Moodle course calendar event.
 */
class update_calendar_event {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $eventid Moodle calendar event id.
     * @param string|null $name Event name.
     * @param string|null $description Event description.
     * @param int|null $timestart Event start timestamp.
     * @param int|null $timeduration Event duration in seconds.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $eventid,
        ?string $name = null,
        ?string $description = null,
        ?int $timestart = null,
        ?int $timeduration = null
    ): array {
        $event = calendar_tools::get_course_event($eventid, $courseid);
        $properties = $event->properties(false);

        $data = calendar_tools::to_event_data(
            $courseid,
            $name ?? (string) $properties->name,
            $description ?? (string) $properties->description,
            $timestart ?? (int) $properties->timestart,
            $timeduration ?? (int) $properties->timeduration
        );
        $data->id = $eventid;

        $event->update($data, true);
        return calendar_tools::to_response(calendar_tools::get_course_event($eventid, $courseid));
    }
}
