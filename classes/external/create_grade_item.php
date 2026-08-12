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
 * Create manual grade item external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\create_grade_item as create_grade_item_operation;

class create_grade_item extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Grade item name'),
            'grade_max' => new external_value(PARAM_FLOAT, 'Maximum grade', VALUE_DEFAULT, 100),
            'grade_min' => new external_value(PARAM_FLOAT, 'Minimum grade', VALUE_DEFAULT, 0),
            'grade_pass' => new external_value(PARAM_FLOAT, 'Passing grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'category_id' => new external_value(PARAM_INT, 'Grade category id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'hidden' => new external_value(PARAM_BOOL, 'Hidden state', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    public static function execute(
        int $course_id,
        string $name,
        float $grade_max = 100.0,
        float $grade_min = 0.0,
        ?float $grade_pass = null,
        ?int $category_id = null,
        ?bool $hidden = null
    ): array {
        [
            'course_id' => $courseid,
            'name' => $itemname,
            'grade_max' => $grademax,
            'grade_min' => $grademin,
            'grade_pass' => $gradepass,
            'category_id' => $categoryid,
            'hidden' => $itemhidden,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'name' => $name,
            'grade_max' => $grade_max,
            'grade_min' => $grade_min,
            'grade_pass' => $grade_pass,
            'category_id' => $category_id,
            'hidden' => $hidden,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:manage', $coursecontext);

        return create_grade_item_operation::execute(
            (int) $courseid,
            (string) $itemname,
            (float) $grademax,
            (float) $grademin,
            $gradepass === null ? null : (float) $gradepass,
            $categoryid === null ? null : (int) $categoryid,
            $itemhidden
        );
    }

    public static function execute_returns() {
        return gradebook_response::manual_item_structure();
    }
}
