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
 * Update course calendar event external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\update_calendar_event as update_calendar_event_operation;

/**
 * External API adapter for update_calendar_event.
 */
class update_calendar_event extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'event_id' => new external_value(PARAM_INT, 'Moodle calendar event id'),
            'name' => new external_value(PARAM_TEXT, 'Event name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'description' => new external_value(PARAM_RAW, 'Event description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'timestart' => new external_value(PARAM_INT, 'Event start Unix timestamp', VALUE_DEFAULT, null, NULL_ALLOWED),
            'timeduration' => new external_value(PARAM_INT, 'Event duration in seconds', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    public static function execute(
        int $course_id,
        int $event_id,
        ?string $name = null,
        ?string $description = null,
        ?int $timestart = null,
        ?int $timeduration = null
    ): array {
        [
            'course_id' => $courseid,
            'event_id' => $eventid,
            'name' => $name,
            'description' => $description,
            'timestart' => $timestart,
            'timeduration' => $timeduration,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'event_id' => $event_id,
            'name' => $name,
            'description' => $description,
            'timestart' => $timestart,
            'timeduration' => $timeduration,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/calendar:manageentries', $coursecontext);

        return update_calendar_event_operation::execute(
            (int) $courseid,
            (int) $eventid,
            $name,
            $description,
            $timestart === null ? null : (int) $timestart,
            $timeduration === null ? null : (int) $timeduration
        );
    }

    public static function execute_returns(): external_single_structure {
        return get_calendar_events::event_structure();
    }
}
