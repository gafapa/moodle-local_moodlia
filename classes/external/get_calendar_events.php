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
 * Course calendar events external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_calendar_events as get_calendar_events_operation;

/**
 * External API adapter for get_calendar_events.
 */
class get_calendar_events extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'time_from' => new external_value(PARAM_INT, 'Start Unix timestamp'),
            'time_to' => new external_value(PARAM_INT, 'End Unix timestamp'),
        ]);
    }

    public static function execute(int $course_id, int $time_from, int $time_to): array {
        [
            'course_id' => $courseid,
            'time_from' => $timefrom,
            'time_to' => $timeto,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'time_from' => $time_from,
            'time_to' => $time_to,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_calendar_events_operation::execute((int) $courseid, (int) $timefrom, (int) $timeto);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'events' => new external_multiple_structure(self::event_structure()),
        ]);
    }

    public static function event_structure(): external_single_structure {
        return new external_single_structure([
            'event_id' => new external_value(PARAM_INT, 'Moodle calendar event id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Event name'),
            'description' => new external_value(PARAM_RAW, 'Event description'),
            'event_type' => new external_value(PARAM_TEXT, 'Calendar event type'),
            'timestart' => new external_value(PARAM_INT, 'Event start timestamp'),
            'timeduration' => new external_value(PARAM_INT, 'Event duration in seconds'),
            'url' => new external_value(PARAM_URL, 'Calendar day URL'),
        ]);
    }
}
