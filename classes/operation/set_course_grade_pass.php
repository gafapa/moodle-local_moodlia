<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course total passing-grade operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Sets the passing grade on Moodle's course-total grade item.
 */
class set_course_grade_pass {
    /**
     * Execute the operation.
     *
     * Exactly one of gradepass or gradepasspercent must be supplied.
     *
     * @param int $courseid Courseid.
     * @param float|null $gradepass Gradepass.
     * @param float|null $gradepasspercent Gradepasspercent.
     * @return array
     */
    public static function execute(int $courseid, ?float $gradepass = null, ?float $gradepasspercent = null): array {
        if (($gradepass === null) === ($gradepasspercent === null)) {
            throw new \invalid_parameter_exception('Provide exactly one of grade_pass or grade_pass_percent.');
        }

        $course = course_tools::get_course($courseid);
        gradebook_tools::require_gradebook_api();
        $item = \grade_item::fetch_course_item((int) $course->id);

        if ($gradepasspercent !== null) {
            $gradepass = course_completion_tools::grade_from_percentage($item, $gradepasspercent);
        }
        if ($gradepass < (float) $item->grademin || $gradepass > (float) $item->grademax) {
            throw new \invalid_parameter_exception('grade_pass must be inside the course total grade range.');
        }

        $item->gradepass = $gradepass;
        $item->update('local_moodlia');

        return gradebook_tools::grade_item_to_response(
            gradebook_tools::get_grade_item((int) $course->id, (int) $item->id)
        );
    }
}
