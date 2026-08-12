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
 * Update manual grade item external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\update_grade_item as update_grade_item_operation;

/**
 * Update grade item implementation.
 */
class update_grade_item extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'item_id' => new external_value(PARAM_INT, 'Grade item id'),
            'name' => new external_value(PARAM_TEXT, 'Grade item name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'grade_max' => new external_value(PARAM_FLOAT, 'Maximum grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'grade_min' => new external_value(PARAM_FLOAT, 'Minimum grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'grade_pass' => new external_value(PARAM_FLOAT, 'Passing grade', VALUE_DEFAULT, null, NULL_ALLOWED),
            'category_id' => new external_value(PARAM_INT, 'Grade category id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'hidden' => new external_value(PARAM_BOOL, 'Hidden state', VALUE_DEFAULT, null, NULL_ALLOWED),
            'locked' => new external_value(PARAM_BOOL, 'Locked state', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $itemid Item id.
     * @param string|null $name Name.
     * @param float|null $grademax Grade max.
     * @param float|null $grademin Grade min.
     * @param float|null $gradepass Grade pass.
     * @param int|null $categoryid Category id.
     * @param bool|null $hidden Hidden.
     * @param bool|null $locked Locked.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $itemid,
        ?string $name = null,
        ?float $grademax = null,
        ?float $grademin = null,
        ?float $gradepass = null,
        ?int $categoryid = null,
        ?bool $hidden = null,
        ?bool $locked = null
    ): array {
        [
            'course_id' => $courseid,
            'item_id' => $itemid,
            'name' => $itemname,
            'grade_max' => $grademax,
            'grade_min' => $grademin,
            'grade_pass' => $gradepass,
            'category_id' => $categoryid,
            'hidden' => $itemhidden,
            'locked' => $itemlocked,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'item_id' => $itemid,
            'name' => $name,
            'grade_max' => $grademax,
            'grade_min' => $grademin,
            'grade_pass' => $gradepass,
            'category_id' => $categoryid,
            'hidden' => $hidden,
            'locked' => $locked,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:manage', $coursecontext);

        return update_grade_item_operation::execute(
            (int) $courseid,
            (int) $itemid,
            $itemname,
            $grademax === null ? null : (float) $grademax,
            $grademin === null ? null : (float) $grademin,
            $gradepass === null ? null : (float) $gradepass,
            $categoryid === null ? null : (int) $categoryid,
            $itemhidden,
            $itemlocked
        );
    }

    /**
     * Execute returns.
     *
     * @return mixed
     */
    public static function execute_returns() {
        return gradebook_response::manual_item_structure();
    }
}
