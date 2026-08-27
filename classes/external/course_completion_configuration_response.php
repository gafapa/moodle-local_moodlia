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
 * Course completion configuration response structure.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Shared response structure for global course completion operations.
 */
class course_completion_configuration_response {
    /**
     * Return the canonical completion configuration structure.
     *
     * @return external_single_structure
     */
    public static function structure(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'course_completion_enabled' => new external_value(PARAM_BOOL, 'Whether course completion tracking is enabled'),
            'criteria_locked' => new external_value(PARAM_BOOL, 'Whether existing completion data locks the criteria'),
            'criteria_aggregation' => new external_value(PARAM_ALPHA, 'Overall criteria aggregation'),
            'activity_aggregation' => new external_value(PARAM_ALPHA, 'Activity criteria aggregation'),
            'required_module_ids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Required course module id')
            ),
            'criteria_count' => new external_value(PARAM_INT, 'Configured completion criteria count'),
            'grade_criterion_enabled' => new external_value(PARAM_BOOL, 'Whether a course-grade criterion is configured'),
            'required_course_grade' => new external_value(PARAM_FLOAT, 'Required absolute course total grade'),
            'required_course_grade_percent' => new external_value(PARAM_FLOAT, 'Required course total percentage'),
            'course_grade_pass' => new external_value(PARAM_FLOAT, 'Course total passing grade'),
            'course_grade_pass_percent' => new external_value(PARAM_FLOAT, 'Course total passing percentage'),
            'course_grade_min' => new external_value(PARAM_FLOAT, 'Course total minimum grade'),
            'course_grade_max' => new external_value(PARAM_FLOAT, 'Course total maximum grade'),
        ]);
    }
}
