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
 * Get course assignments external function.
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
use local_moodlia\operation\get_course_assignments as get_course_assignments_operation;

/**
 * External API adapter for get_course_assignments.
 */
class get_course_assignments extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @return array
     */
    public static function execute(int $courseid): array {
        ['course_id' => $courseid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_course_assignments_operation::execute((int) $courseid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'count' => new external_value(PARAM_INT, 'Returned assignment count'),
            'assignments' => new external_multiple_structure(self::assignment_summary_structure()),
        ]);
    }

    /**
     * Return the canonical assignment summary structure.
     *
     * @return external_single_structure
     */
    public static function assignment_summary_structure(): external_single_structure {
        return new external_single_structure([
            'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Assignment activity name'),
            'intro' => new external_value(PARAM_RAW, 'Assignment intro HTML'),
            'intro_format' => new external_value(PARAM_INT, 'Assignment intro format'),
            'activity' => new external_value(PARAM_RAW, 'Assignment activity instructions HTML'),
            'activity_format' => new external_value(PARAM_INT, 'Assignment activity instructions format'),
            'allowsubmissionsfromdate' => new external_value(PARAM_INT, 'Submissions open timestamp'),
            'duedate' => new external_value(PARAM_INT, 'Due timestamp'),
            'cutoffdate' => new external_value(PARAM_INT, 'Cut-off timestamp'),
            'gradingduedate' => new external_value(PARAM_INT, 'Grading due timestamp'),
            'grade' => new external_value(PARAM_FLOAT, 'Maximum assignment grade'),
            'teamsubmission' => new external_value(PARAM_BOOL, 'Whether team submissions are enabled'),
            'requireallteammemberssubmit' => new external_value(PARAM_BOOL, 'Whether all team members must submit'),
            'teamsubmissiongroupingid' => new external_value(PARAM_INT, 'Team submission grouping id'),
            'blindmarking' => new external_value(PARAM_BOOL, 'Whether blind marking is enabled'),
            'hidegrader' => new external_value(PARAM_BOOL, 'Whether grader identity is hidden'),
            'markingworkflow' => new external_value(PARAM_BOOL, 'Whether marking workflow is enabled'),
            'markingallocation' => new external_value(PARAM_BOOL, 'Whether marking allocation is enabled'),
            'requiresubmissionstatement' => new external_value(PARAM_BOOL, 'Whether submission statement acceptance is required'),
            'submissiondrafts' => new external_value(PARAM_BOOL, 'Whether submission drafts are enabled'),
            'maxattempts' => new external_value(PARAM_INT, 'Maximum submission attempts'),
            'attemptreopenmethod' => new external_value(PARAM_TEXT, 'Attempt reopen method'),
            'submissionattachments' => new external_value(PARAM_BOOL, 'Whether submission attachments are enabled'),
            'sendnotifications' => new external_value(PARAM_BOOL, 'Whether grader notifications are enabled'),
            'sendlatenotifications' => new external_value(PARAM_BOOL, 'Whether late submission notifications are enabled'),
            'sendstudentnotifications' => new external_value(PARAM_BOOL, 'Whether student notifications are enabled'),
            'submission_plugins' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Enabled submission plugin name')),
            'feedback_plugins' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Enabled feedback plugin name')),
            'visible' => new external_value(PARAM_BOOL, 'Whether the assignment module is visible'),
            'url' => new external_value(PARAM_URL, 'Assignment view URL'),
        ]);
    }
}
