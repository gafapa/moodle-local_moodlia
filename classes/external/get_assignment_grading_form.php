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
 * Get assignment grading form external function.
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
use local_moodlia\operation\assignment_tools;
use local_moodlia\operation\get_assignment_grading_form as get_assignment_grading_form_operation;

/**
 * External API adapter for get_assignment_grading_form.
 */
class get_assignment_grading_form extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ]);

        self::require_assignment_context((int) $courseid, (int) $moduleid, false);
        return get_assignment_grading_form_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return self::grading_form_structure();
    }

    /**
     * Require assignment context.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param bool $manageform Manageform.
     * @return void
     */
    public static function require_assignment_context(int $courseid, int $moduleid, bool $manageform): void {
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
        require_capability('mod/assign:grade', $modulecontext);
        if ($manageform) {
            require_capability('moodle/grade:managegradingforms', $modulecontext);
        }
    }

    /**
     * Grading form structure.
     *
     * @return external_single_structure
     */
    public static function grading_form_structure(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'assignment_id' => new external_value(PARAM_INT, 'Assignment instance id'),
            'active_method' => new external_value(PARAM_ALPHANUMEXT, 'Active advanced grading method'),
            'supported' => new external_value(PARAM_BOOL, 'Whether the active method is supported by MoodlIA'),
            'definition_id' => new external_value(PARAM_INT, 'Advanced grading definition id'),
            'name' => new external_value(PARAM_TEXT, 'Definition name'),
            'description' => new external_value(PARAM_RAW, 'Definition description HTML'),
            'status' => new external_value(PARAM_INT, 'Moodle grading definition status'),
            'criteria' => new external_multiple_structure(new external_single_structure([
                'criterion_id' => new external_value(PARAM_INT, 'Criterion id'),
                'sort_order' => new external_value(PARAM_INT, 'Criterion sort order'),
                'shortname' => new external_value(PARAM_TEXT, 'Guide criterion short name'),
                'description' => new external_value(PARAM_RAW, 'Criterion description'),
                'description_markers' => new external_value(PARAM_RAW, 'Guide marker-facing description'),
                'max_score' => new external_value(PARAM_FLOAT, 'Criterion maximum score'),
                'levels' => new external_multiple_structure(new external_single_structure([
                    'level_id' => new external_value(PARAM_INT, 'Rubric level id'),
                    'score' => new external_value(PARAM_FLOAT, 'Rubric level score'),
                    'definition' => new external_value(PARAM_RAW, 'Rubric level definition'),
                ])),
            ])),
            'comments' => new external_multiple_structure(new external_single_structure([
                'comment_id' => new external_value(PARAM_INT, 'Guide reusable comment id'),
                'sort_order' => new external_value(PARAM_INT, 'Comment sort order'),
                'description' => new external_value(PARAM_RAW, 'Comment text'),
            ])),
            'checklist_compatible' => new external_value(PARAM_BOOL, 'Whether the rubric can be used as a binary checklist'),
        ]);
    }
}
