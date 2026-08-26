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
 * Update grade category external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\update_grade_category as update_grade_category_operation;

/**
 * Update grade category implementation.
 */
class update_grade_category extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'category_id' => new external_value(PARAM_INT, 'Grade category id'),
            'name' => new external_value(PARAM_TEXT, 'Grade category name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'aggregation' => new external_value(PARAM_INT, 'Moodle aggregation constant', VALUE_DEFAULT, null, NULL_ALLOWED),
            'hidden' => new external_value(PARAM_BOOL, 'Hidden state', VALUE_DEFAULT, null, NULL_ALLOWED),
            'grade_pass' => new external_value(PARAM_FLOAT, 'Passing category total grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'grade_max' => new external_value(PARAM_FLOAT, 'Maximum category total grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'exclude_empty_grades' => new external_value(PARAM_BOOL, 'Whether ungraded items are excluded from aggregation', VALUE_DEFAULT, null, NULL_ALLOWED),
            'keep_highest' => new external_value(PARAM_INT, 'Number of highest grades kept', VALUE_DEFAULT, null, NULL_ALLOWED),
            'drop_lowest' => new external_value(PARAM_INT, 'Number of lowest grades dropped', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $categoryid Categoryid.
     * @param string|null $name Name.
     * @param int|null $aggregation Aggregation.
     * @param bool|null $hidden Hidden.
     * @param float|null $gradepass Gradepass.
     * @param float|null $grademax Grademax.
     * @param bool|null $excludeemptygrades Excludeemptygrades.
     * @param int|null $keephighest Keephighest.
     * @param int|null $droplowest Droplowest.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $categoryid,
        ?string $name = null,
        ?int $aggregation = null,
        ?bool $hidden = null,
        ?float $gradepass = null,
        ?float $grademax = null,
        ?bool $excludeemptygrades = null,
        ?int $keephighest = null,
        ?int $droplowest = null
    ): array {
        [
            'course_id' => $courseid,
            'category_id' => $categoryid,
            'name' => $categoryname,
            'aggregation' => $categoryaggregation,
            'hidden' => $categoryhidden,
            'grade_pass' => $gradepass,
            'grade_max' => $grademax,
            'exclude_empty_grades' => $excludeemptygrades,
            'keep_highest' => $keephighest,
            'drop_lowest' => $droplowest,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'category_id' => $categoryid,
            'name' => $name,
            'aggregation' => $aggregation,
            'hidden' => $hidden,
            'grade_pass' => $gradepass,
            'grade_max' => $grademax,
            'exclude_empty_grades' => $excludeemptygrades,
            'keep_highest' => $keephighest,
            'drop_lowest' => $droplowest,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:manage', $coursecontext);

        return update_grade_category_operation::execute(
            (int) $courseid,
            (int) $categoryid,
            $categoryname,
            $categoryaggregation === null ? null : (int) $categoryaggregation,
            $categoryhidden,
            $gradepass === null ? null : (float) $gradepass,
            $grademax === null ? null : (float) $grademax,
            $excludeemptygrades,
            $keephighest === null ? null : (int) $keephighest,
            $droplowest === null ? null : (int) $droplowest
        );
    }

    /**
     * Execute returns.
     *
     * @return mixed
     */
    public static function execute_returns() {
        return gradebook_response::category_structure();
    }
}
