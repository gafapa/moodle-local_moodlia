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
 * Get workshop submission assessments external function.
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
use local_moodlia\operation\get_workshop_submission_assessments as get_workshop_submission_assessments_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for get_workshop_submission_assessments.
 */
class get_workshop_submission_assessments extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'submission_id' => new external_value(PARAM_INT, 'Workshop submission id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @return array
     */
    public static function execute(int $course_id, int $module_id, int $submission_id): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'submission_id' => $submissionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'submission_id' => $submission_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:view', $modulecontext);

        return get_workshop_submission_assessments_operation::execute((int) $courseid, (int) $moduleid, (int) $submissionid);
    }

    /**
     * Return a workshop assessment structure.
     *
     * @return external_single_structure
     */
    private static function assessment_structure(): external_single_structure {
        return new external_single_structure([
            'assessment_id' => new external_value(PARAM_INT, 'Assessment id'),
            'workshop_id' => new external_value(PARAM_INT, 'Workshop instance id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'submission_id' => new external_value(PARAM_INT, 'Submission id'),
            'reviewer_id' => new external_value(PARAM_INT, 'Reviewer user id'),
            'weight' => new external_value(PARAM_INT, 'Assessment weight'),
            'grade' => new external_value(PARAM_FLOAT, 'Submission grade assigned by the assessment'),
            'grading_grade' => new external_value(PARAM_FLOAT, 'Assessment grade'),
            'grading_grade_over' => new external_value(PARAM_FLOAT, 'Overridden assessment grade'),
            'grading_grade_over_by' => new external_value(PARAM_INT, 'User id that overrode the assessment grade'),
            'feedback_author' => new external_value(PARAM_RAW, 'Feedback for the submission author'),
            'feedback_author_format' => new external_value(PARAM_ALPHA, 'Feedback author format'),
            'feedback_reviewer' => new external_value(PARAM_RAW, 'Feedback for the reviewer'),
            'feedback_reviewer_format' => new external_value(PARAM_ALPHA, 'Feedback reviewer format'),
            'time_created' => new external_value(PARAM_INT, 'Assessment creation time'),
            'time_modified' => new external_value(PARAM_INT, 'Assessment modification time'),
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
            'submission_id' => new external_value(PARAM_INT, 'Workshop submission id'),
            'count' => new external_value(PARAM_INT, 'Returned assessment count'),
            'assessments' => new external_multiple_structure(self::assessment_structure()),
        ]);
    }
}
