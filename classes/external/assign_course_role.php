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

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\assign_course_role as assign_course_role_operation;

/**
 * Assign course role implementation.
 */
class assign_course_role extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'role_archetype' => new external_value(PARAM_ALPHANUMEXT, 'Role archetype', VALUE_DEFAULT, 'student'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @param string $rolearchetype Role archetype.
     * @return array
     */
    public static function execute(int $courseid, int $userid, string $rolearchetype = 'student'): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'user_id' => $userid,
            'role_archetype' => $rolearchetype,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance((int) $params['course_id']);
        self::validate_context($coursecontext);
        require_capability('moodle/role:assign', $coursecontext);

        return assign_course_role_operation::execute((int) $params['course_id'], (int) $params['user_id'], $params['role_archetype']);
    }

    /**
     * Execute returns.
     *
     * @return mixed
     */
    public static function execute_returns() {
        return admin_response::role_assignment_structure('assigned');
    }
}
