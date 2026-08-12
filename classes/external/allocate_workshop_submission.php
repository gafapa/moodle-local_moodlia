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
 * Allocate workshop submission external function.
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
use local_moodlia\operation\allocate_workshop_submission as allocate_workshop_submission_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for allocate_workshop_submission.
 */
class allocate_workshop_submission extends external_api {
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
            'reviewer_id' => new external_value(PARAM_INT, 'Reviewer user id, or 0 for the current user', VALUE_DEFAULT, 0),
            'weight' => new external_value(PARAM_INT, 'Assessment weight', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $submissionid Submissionid.
     * @param int $reviewerid Reviewerid.
     * @param int $weight Weight.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $submissionid,
        int $reviewerid = 0,
        int $weight = 1
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'submission_id' => $submissionid,
            'reviewer_id' => $reviewerid,
            'weight' => $assessmentweight,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'submission_id' => $submissionid,
            'reviewer_id' => $reviewerid,
            'weight' => $weight,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:allocate', $modulecontext);

        return allocate_workshop_submission_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $submissionid,
            (int) $reviewerid,
            (int) $assessmentweight
        );
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
            'submission_id' => new external_value(PARAM_INT, 'Submission id'),
            'assessment_id' => new external_value(PARAM_INT, 'Assessment id'),
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
            'created' => new external_value(PARAM_BOOL, 'Whether a new allocation was created'),
        ]);
    }
}
