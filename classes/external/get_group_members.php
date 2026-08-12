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
 * Group members external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_group_members as get_group_members_operation;

/**
 * External API adapter for get_group_members.
 */
class get_group_members extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $groupid Group id.
     * @return array
     */
    public static function execute(int $courseid, int $groupid): array {
        [
            'course_id' => $courseid,
            'group_id' => $groupid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'group_id' => $groupid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:viewparticipants', $coursecontext);

        return get_group_members_operation::execute((int) $courseid, (int) $groupid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id'),
            'members' => new external_multiple_structure(new external_single_structure([
                'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
                'username' => new external_value(PARAM_USERNAME, 'Moodle username'),
                'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                'email' => new external_value(PARAM_RAW, 'User email address'),
            ])),
        ]);
    }
}
