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
 * Get workshop grades report external function.
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
use local_moodlia\operation\get_workshop_grades_report as get_workshop_grades_report_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for get_workshop_grades_report.
 */
class get_workshop_grades_report extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'group_id' => new external_value(PARAM_INT, 'Group id or 0', VALUE_DEFAULT, 0),
            'sort_by' => new external_value(PARAM_ALPHA, 'Sort field', VALUE_DEFAULT, 'lastname'),
            'sort_direction' => new external_value(PARAM_ALPHA, 'Sort direction', VALUE_DEFAULT, 'ASC'),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'per_page' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $groupid Groupid.
     * @param string $sortby Sortby.
     * @param string $sortdirection Sortdirection.
     * @param int $page Page.
     * @param int $perpage Perpage.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $groupid = 0,
        string $sortby = 'lastname',
        string $sortdirection = 'ASC',
        int $page = 0,
        int $perpage = 20
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
            'sort_by' => $sortby,
            'sort_direction' => $sortdirection,
            'page' => $page,
            'per_page' => $perpage,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
            'sort_by' => $sortby,
            'sort_direction' => $sortdirection,
            'page' => $page,
            'per_page' => $perpage,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:viewallassessments', $modulecontext);

        return get_workshop_grades_report_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $groupid,
            (string) $sortby,
            (string) $sortdirection,
            (int) $page,
            (int) $perpage
        );
    }

    /**
     * Return a report review structure.
     *
     * @return external_single_structure
     */
    private static function review_structure(): external_single_structure {
        return new external_single_structure([
            'user_id' => new external_value(PARAM_INT, 'Reviewer or author user id'),
            'assessment_id' => new external_value(PARAM_INT, 'Assessment id'),
            'submission_id' => new external_value(PARAM_INT, 'Submission id'),
            'grade' => new external_value(PARAM_FLOAT, 'Submission grade'),
            'grading_grade' => new external_value(PARAM_FLOAT, 'Assessment grade'),
            'grading_grade_over' => new external_value(PARAM_FLOAT, 'Overridden assessment grade'),
            'weight' => new external_value(PARAM_INT, 'Assessment weight'),
        ]);
    }

    /**
     * Return a report grade row structure.
     *
     * @return external_single_structure
     */
    private static function grade_structure(): external_single_structure {
        return new external_single_structure([
            'user_id' => new external_value(PARAM_INT, 'Displayed user id'),
            'submission_id' => new external_value(PARAM_INT, 'Submission id'),
            'submission_title' => new external_value(PARAM_RAW, 'Submission title'),
            'submission_modified' => new external_value(PARAM_INT, 'Submission modification timestamp'),
            'submission_grade' => new external_value(PARAM_FLOAT, 'Aggregated submission grade'),
            'grading_grade' => new external_value(PARAM_FLOAT, 'Computed assessment grade'),
            'submission_grade_over' => new external_value(PARAM_FLOAT, 'Overridden submission grade'),
            'submission_grade_over_by' => new external_value(PARAM_INT, 'Grade override user id'),
            'submission_published' => new external_value(PARAM_BOOL, 'Whether the submission is published'),
            'reviewed_by' => new external_multiple_structure(self::review_structure()),
            'reviewer_of' => new external_multiple_structure(self::review_structure()),
        ]);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'workshop_id' => new external_value(PARAM_INT, 'Workshop instance id'),
            'group_id' => new external_value(PARAM_INT, 'Group id'),
            'sort_by' => new external_value(PARAM_ALPHA, 'Sort field'),
            'sort_direction' => new external_value(PARAM_ALPHA, 'Sort direction'),
            'page' => new external_value(PARAM_INT, 'Page number'),
            'per_page' => new external_value(PARAM_INT, 'Page size'),
            'total_count' => new external_value(PARAM_INT, 'Total report row count'),
            'count' => new external_value(PARAM_INT, 'Returned row count'),
            'grades' => new external_multiple_structure(self::grade_structure()),
        ]);
    }
}
