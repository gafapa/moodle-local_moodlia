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
 * Course calendar events operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle course calendar events.
 */
class get_calendar_events {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $timefrom Start timestamp.
     * @param int $timeto End timestamp.
     * @return array
     */
    public static function execute(int $courseid, int $timefrom, int $timeto): array {
        calendar_tools::require_calendar_api();

        course_tools::get_course($courseid);
        if ($timefrom <= 0 || $timeto <= $timefrom) {
            throw new \invalid_parameter_exception('time_to must be greater than time_from.');
        }

        $records = calendar_get_events($timefrom, $timeto, false, false, [$courseid], true, true);
        $events = [];
        foreach ($records as $record) {
            if (($record->eventtype ?? '') === 'course' && (int) ($record->courseid ?? 0) === $courseid) {
                $events[] = calendar_tools::to_response(new \calendar_event($record));
            }
        }

        usort($events, static fn(array $left, array $right): int => $left['timestart'] <=> $right['timestart']);

        return [
            'course_id' => $courseid,
            'events' => $events,
        ];
    }
}
