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
 * Course completion criteria write external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_value;
use local_moodlia\operation\set_course_completion_criteria as set_course_completion_criteria_operation;

/**
 * Replaces the global completion criteria for an unlocked Moodle course.
 */
class set_course_completion_criteria extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'required_module_ids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Required Moodle course module id'),
                'Required Moodle course module ids',
                VALUE_DEFAULT,
                []
            ),
            'require_all_activities' => new external_value(
                PARAM_BOOL,
                'Whether every required module must be completed',
                VALUE_DEFAULT,
                true
            ),
            'required_course_grade_percent' => new external_value(
                PARAM_FLOAT,
                'Required course total percentage',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'criteria_aggregation' => new external_value(
                PARAM_ALPHA,
                'Overall completion criteria aggregation: all or any',
                VALUE_DEFAULT,
                'all'
            ),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param array $requiredmoduleids Requiredmoduleids.
     * @param bool $requireallactivities Requireallactivities.
     * @param float|null $requiredcoursegradepercent Requiredcoursegradepercent.
     * @param string $criteriaaggregation Criteriaaggregation.
     * @return array
     */
    public static function execute(
        int $courseid,
        array $requiredmoduleids = [],
        bool $requireallactivities = true,
        ?float $requiredcoursegradepercent = null,
        string $criteriaaggregation = 'all'
    ): array {
        [
            'course_id' => $courseid,
            'required_module_ids' => $requiredmoduleids,
            'require_all_activities' => $requireallactivities,
            'required_course_grade_percent' => $requiredcoursegradepercent,
            'criteria_aggregation' => $criteriaaggregation,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'required_module_ids' => $requiredmoduleids,
            'require_all_activities' => $requireallactivities,
            'required_course_grade_percent' => $requiredcoursegradepercent,
            'criteria_aggregation' => $criteriaaggregation,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);
        require_capability('moodle/grade:manage', $coursecontext);

        return set_course_completion_criteria_operation::execute(
            (int) $courseid,
            array_map('intval', $requiredmoduleids),
            (bool) $requireallactivities,
            $requiredcoursegradepercent === null ? null : (float) $requiredcoursegradepercent,
            (string) $criteriaaggregation
        );
    }

    /**
     * Execute returns.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): \core_external\external_single_structure {
        return course_completion_configuration_response::structure();
    }
}
