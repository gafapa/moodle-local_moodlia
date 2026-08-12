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
 * Get workshop grades external function.
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
use local_moodlia\operation\get_workshop_grades as get_workshop_grades_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for get_workshop_grades.
 */
class get_workshop_grades extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'user_id' => new external_value(PARAM_INT, 'User id or 0 for the current user', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $userid = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:view', $modulecontext);

        return get_workshop_grades_operation::execute((int) $courseid, (int) $moduleid, (int) $userid);
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
            'user_id' => new external_value(PARAM_INT, 'User id'),
            'submission_raw_grade' => new external_value(PARAM_FLOAT, 'Submission raw grade'),
            'submission_grade' => new external_value(PARAM_RAW, 'Submission display grade'),
            'submission_grade_hidden' => new external_value(PARAM_BOOL, 'Whether the submission grade is hidden'),
            'assessment_raw_grade' => new external_value(PARAM_FLOAT, 'Assessment raw grade'),
            'assessment_grade' => new external_value(PARAM_RAW, 'Assessment display grade'),
            'assessment_grade_hidden' => new external_value(PARAM_BOOL, 'Whether the assessment grade is hidden'),
        ]);
    }
}
