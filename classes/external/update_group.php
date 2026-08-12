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
 * Update group external function.
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
use local_moodlia\operation\update_group as update_group_operation;

/**
 * External API adapter for update_group.
 */
class update_group extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'group_id' => new external_value(PARAM_INT, 'Moodle group id'),
            'name' => new external_value(PARAM_TEXT, 'Group name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'description' => new external_value(PARAM_RAW, 'Group description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'idnumber' => new external_value(PARAM_RAW, 'Group idnumber', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    public static function execute(
        int $course_id,
        int $group_id,
        ?string $name = null,
        ?string $description = null,
        ?string $idnumber = null
    ): array {
        [
            'course_id' => $courseid,
            'group_id' => $groupid,
            'name' => $name,
            'description' => $description,
            'idnumber' => $idnumber,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'group_id' => $group_id,
            'name' => $name,
            'description' => $description,
            'idnumber' => $idnumber,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:managegroups', $coursecontext);

        return update_group_operation::execute((int) $courseid, (int) $groupid, $name, $description, $idnumber);
    }

    public static function execute_returns(): external_single_structure {
        return get_groups::group_structure();
    }
}
