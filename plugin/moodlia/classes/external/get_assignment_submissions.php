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
 * Get assignment submissions external function.
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
use local_moodlia\operation\assignment_tools;
use local_moodlia\operation\get_assignment_submissions as get_assignment_submissions_operation;

/**
 * External API adapter for get_assignment_submissions.
 */
class get_assignment_submissions extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'status' => new external_value(PARAM_ALPHA, 'Optional submission status filter', VALUE_DEFAULT, ''),
            'since' => new external_value(PARAM_INT, 'Only submissions modified at or after this timestamp', VALUE_DEFAULT, 0),
            'before' => new external_value(PARAM_INT, 'Only submissions modified at or before this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(
        int $course_id,
        int $module_id,
        string $status = '',
        int $since = 0,
        int $before = 0
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'status' => $submissionstatus,
            'since' => $modifiedsince,
            'before' => $modifiedbefore,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'status' => $status,
            'since' => $since,
            'before' => $before,
        ]);

        self::require_assignment_context((int) $courseid, (int) $moduleid, 'mod/assign:grade');

        return get_assignment_submissions_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (string) $submissionstatus,
            (int) $modifiedsince,
            (int) $modifiedbefore
        );
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
            'submissions' => new external_multiple_structure(new external_single_structure([
                'submission_id' => new external_value(PARAM_INT, 'Submission id'),
                'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
                'user_id' => new external_value(PARAM_INT, 'Submission user id'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Submission status'),
                'attempt_number' => new external_value(PARAM_INT, 'Attempt number'),
                'group_id' => new external_value(PARAM_INT, 'Group id'),
                'created' => new external_value(PARAM_INT, 'Creation timestamp'),
                'modified' => new external_value(PARAM_INT, 'Modified timestamp'),
                'started' => new external_value(PARAM_INT, 'Started timestamp'),
                'grading_status' => new external_value(PARAM_ALPHANUMEXT, 'Grading status'),
                'online_text' => new external_value(PARAM_RAW, 'Submitted online text HTML'),
            ])),
        ]);
    }

    public static function require_assignment_context(int $courseid, int $moduleid, string $capability): void {
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $course = get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability($capability, $modulecontext);
    }
}
