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
 * Create group external function.
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
use local_moodlia\operation\create_group as create_group_operation;

/**
 * External API adapter for create_group.
 */
class create_group extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Group name'),
            'description' => new external_value(PARAM_RAW, 'Group description', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_RAW, 'Group idnumber', VALUE_DEFAULT, ''),
            'description_format' => new external_value(
                PARAM_ALPHANUMEXT,
                'Description format: html, plain, markdown, or moodle',
                VALUE_DEFAULT,
                'html'
            ),
            'visibility' => new external_value(PARAM_ALPHA, 'Visibility: all, members, own, or none', VALUE_DEFAULT, 'all'),
            'participation' => new external_value(
                PARAM_BOOL,
                'Whether the group is available for activity participation',
                VALUE_DEFAULT,
                true
            ),
            'enrolment_key' => new external_value(PARAM_RAW, 'Optional group self-enrolment key', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $idnumber Idnumber.
     * @param string $descriptionformat Descriptionformat.
     * @param string $visibility Visibility.
     * @param bool $participation Participation.
     * @param string $enrolmentkey Enrolmentkey.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        string $description = '',
        string $idnumber = '',
        string $descriptionformat = 'html',
        string $visibility = 'all',
        bool $participation = true,
        string $enrolmentkey = ''
    ): array {
        [
            'course_id' => $courseid,
            'name' => $name,
            'description' => $description,
            'idnumber' => $idnumber,
            'description_format' => $descriptionformat,
            'visibility' => $visibility,
            'participation' => $participation,
            'enrolment_key' => $enrolmentkey,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'name' => $name,
            'description' => $description,
            'idnumber' => $idnumber,
            'description_format' => $descriptionformat,
            'visibility' => $visibility,
            'participation' => $participation,
            'enrolment_key' => $enrolmentkey,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:managegroups', $coursecontext);

        return create_group_operation::execute(
            (int) $courseid,
            $name,
            $description,
            $idnumber,
            $descriptionformat,
            $visibility,
            (bool) $participation,
            $enrolmentkey
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_groups::group_structure();
    }
}
