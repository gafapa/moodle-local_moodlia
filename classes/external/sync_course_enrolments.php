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
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\course_workflow_tools;
use local_moodlia\operation\sync_course_enrolments as sync_course_enrolments_operation;

/**
 * External API adapter for sync_course_enrolments.
 */
class sync_course_enrolments extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'enrolments' => new external_value(PARAM_RAW, 'JSON array of user_id and role_archetype entries'),
            'unenrol_missing' => new external_value(PARAM_BOOL, 'Unenrol currently enrolled users not present in the desired list', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param string $enrolments Enrolments.
     * @param bool $unenrolmissing Unenrol missing.
     * @return array
     */
    public static function execute(int $courseid, string $enrolments, bool $unenrolmissing = false): array {
        [
            'course_id' => $courseid,
            'enrolments' => $enrolments,
            'unenrol_missing' => $unenrolmissing,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'enrolments' => $enrolments,
            'unenrol_missing' => $unenrolmissing,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('enrol/manual:enrol', $coursecontext);
        if ($unenrolmissing) {
            require_capability('enrol/manual:unenrol', $coursecontext);
        }

        return sync_course_enrolments_operation::execute(
            (int) $courseid,
            course_workflow_tools::decode_array($enrolments, 'enrolments'),
            (bool) $unenrolmissing
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'enrolled_json' => new external_value(PARAM_RAW, 'JSON array with enrolment results'),
            'unenrolled_json' => new external_value(PARAM_RAW, 'JSON array with unenrolment results'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with skipped enrolments or warnings'),
        ]);
    }
}
