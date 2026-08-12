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
 * Create course calendar event external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\create_calendar_event as create_calendar_event_operation;

/**
 * External API adapter for create_calendar_event.
 */
class create_calendar_event extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Event name'),
            'timestart' => new external_value(PARAM_INT, 'Event start Unix timestamp'),
            'description' => new external_value(PARAM_RAW, 'Event description', VALUE_DEFAULT, ''),
            'timeduration' => new external_value(PARAM_INT, 'Event duration in seconds', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param int $timestart Timestart.
     * @param string $description Description.
     * @param int $timeduration Timeduration.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        int $timestart,
        string $description = '',
        int $timeduration = 0
    ): array {
        [
            'course_id' => $courseid,
            'name' => $name,
            'description' => $description,
            'timestart' => $timestart,
            'timeduration' => $timeduration,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'name' => $name,
            'timestart' => $timestart,
            'description' => $description,
            'timeduration' => $timeduration,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/calendar:manageentries', $coursecontext);

        return create_calendar_event_operation::execute(
            (int) $courseid,
            $name,
            $description,
            (int) $timestart,
            (int) $timeduration
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_calendar_events::event_structure();
    }
}
