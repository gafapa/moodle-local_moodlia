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
 * Audit course completion external function.
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
use local_moodlia\operation\audit_course_completion as audit_course_completion_operation;

/**
 * External API adapter for audit_course_completion.
 */
class audit_course_completion extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'include_ok' => new external_value(PARAM_BOOL, 'Include non-issue module rows', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param bool $includeok Include ok.
     * @return array
     */
    public static function execute(int $courseid, bool $includeok = false): array {
        [
            'course_id' => $courseid,
            'include_ok' => $includeok,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'include_ok' => $includeok,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance((int) $courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return audit_course_completion_operation::execute((int) $courseid, (bool) $includeok);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'issue_count' => new external_value(PARAM_INT, 'Number of completion issues'),
            'repairable_count' => new external_value(PARAM_INT, 'Number of automatically repairable issues'),
            'issues_json' => new external_value(PARAM_RAW, 'JSON array with completion issues'),
            'ok_json' => new external_value(PARAM_RAW, 'JSON array with non-issue module rows'),
        ]);
    }
}
