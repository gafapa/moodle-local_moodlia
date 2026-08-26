<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course total passing-grade external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\set_course_grade_pass as set_course_grade_pass_operation;

/**
 * Sets Moodle's course-total passing grade.
 */
class set_course_grade_pass extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'grade_pass' => new external_value(PARAM_FLOAT, 'Absolute course total passing grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'grade_pass_percent' => new external_value(PARAM_FLOAT, 'Course total passing percentage from 0 to 100', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param float|null $gradepass Gradepass.
     * @param float|null $gradepasspercent Gradepasspercent.
     * @return array
     */
    public static function execute(int $courseid, ?float $gradepass = null, ?float $gradepasspercent = null): array {
        [
            'course_id' => $courseid,
            'grade_pass' => $gradepass,
            'grade_pass_percent' => $gradepasspercent,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'grade_pass' => $gradepass,
            'grade_pass_percent' => $gradepasspercent,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:manage', $coursecontext);

        return set_course_grade_pass_operation::execute(
            (int) $courseid,
            $gradepass === null ? null : (float) $gradepass,
            $gradepasspercent === null ? null : (float) $gradepasspercent
        );
    }

    /**
     * Execute returns.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): \core_external\external_single_structure {
        return gradebook_response::manual_item_structure();
    }
}
