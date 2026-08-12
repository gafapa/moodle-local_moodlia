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
 * Create grade category external function.
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
use local_moodlia\operation\create_grade_category as create_grade_category_operation;

class create_grade_category extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Grade category name'),
            'aggregation' => new external_value(PARAM_INT, 'Moodle aggregation constant', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    public static function execute(int $course_id, string $name, ?int $aggregation = null): array {
        [
            'course_id' => $courseid,
            'name' => $categoryname,
            'aggregation' => $categoryaggregation,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'name' => $name,
            'aggregation' => $aggregation,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:manage', $coursecontext);

        return create_grade_category_operation::execute((int) $courseid, (string) $categoryname, $categoryaggregation === null ? null : (int) $categoryaggregation);
    }

    public static function execute_returns() {
        return gradebook_response::category_structure();
    }
}
