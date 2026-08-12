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
 * Shared calendar helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for calendar event operations.
 */
class calendar_tools {
    /**
     * Load Moodle calendar APIs.
     */
    public static function require_calendar_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');
    }

    /**
     * Load a course calendar event and verify ownership.
     *
     * @param int $eventid Moodle calendar event id.
     * @param int|null $courseid Expected owning course id.
     * @return \calendar_event
     */
    public static function get_course_event(int $eventid, ?int $courseid = null): \calendar_event {
        self::require_calendar_api();

        if ($eventid <= 0) {
            throw new \invalid_parameter_exception('event_id must be a positive integer.');
        }

        $records = calendar_get_events_by_id([$eventid]);
        if (empty($records[$eventid])) {
            throw new \moodle_exception('invalidevent');
        }

        $event = new \calendar_event($records[$eventid]);
        $properties = $event->properties(false);

        if (($properties->eventtype ?? '') !== 'course') {
            throw new \invalid_parameter_exception('event_id must reference a course calendar event.');
        }

        if ($courseid !== null && (int) $properties->courseid !== $courseid) {
            throw new \invalid_parameter_exception('event_id must belong to the provided course_id.');
        }

        return $event;
    }

    /**
     * Return the canonical calendar event response shape.
     *
     * @param \calendar_event $event Moodle calendar event.
     * @return array
     */
    public static function to_response(\calendar_event $event): array {
        $properties = $event->properties(false);
        $url = new \moodle_url('/calendar/view.php', [
            'view' => 'day',
            'course' => (int) $properties->courseid,
            'time' => (int) $properties->timestart,
        ]);

        return [
            'event_id' => (int) $properties->id,
            'course_id' => (int) $properties->courseid,
            'name' => format_string($properties->name, true, ['context' => \context_course::instance($properties->courseid)]),
            'description' => (string) $properties->description,
            'event_type' => (string) $properties->eventtype,
            'timestart' => (int) $properties->timestart,
            'timeduration' => (int) $properties->timeduration,
            'url' => $url->out(false),
        ];
    }

    /**
     * Normalize a course calendar event payload.
     *
     * @param int $courseid Moodle course id.
     * @param string $name Event name.
     * @param string $description Event description.
     * @param int $timestart Event start timestamp.
     * @param int $timeduration Event duration in seconds.
     * @return \stdClass
     */
    public static function to_event_data(
        int $courseid,
        string $name,
        string $description,
        int $timestart,
        int $timeduration
    ): \stdClass {
        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }
        if ($timestart <= 0) {
            throw new \invalid_parameter_exception('timestart must be a positive Unix timestamp.');
        }
        if ($timeduration < 0) {
            throw new \invalid_parameter_exception('timeduration must not be negative.');
        }

        return (object) [
            'name' => $name,
            'description' => $description,
            'format' => FORMAT_HTML,
            'eventtype' => 'course',
            'courseid' => $courseid,
            'course' => $courseid,
            'groupid' => 0,
            'categoryid' => 0,
            'timestart' => $timestart,
            'timeduration' => $timeduration,
            'visible' => 1,
        ];
    }
}
