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
 * Update manual grade value external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\update_grade_value as update_grade_value_operation;

class update_grade_value extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'item_id' => new external_value(PARAM_INT, 'Grade item id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'grade' => new external_value(PARAM_FLOAT, 'Final grade'),
            'feedback' => new external_value(PARAM_RAW, 'Grade feedback', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    public static function execute(int $course_id, int $item_id, int $user_id, float $grade, ?string $feedback = null): array {
        [
            'course_id' => $courseid,
            'item_id' => $itemid,
            'user_id' => $userid,
            'grade' => $finalgrade,
            'feedback' => $gradefeedback,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'item_id' => $item_id,
            'user_id' => $user_id,
            'grade' => $grade,
            'feedback' => $feedback,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/grade:edit', $coursecontext);

        return update_grade_value_operation::execute((int) $courseid, (int) $itemid, (int) $userid, (float) $finalgrade, $gradefeedback);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'item_id' => new external_value(PARAM_INT, 'Grade item id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'grade' => new external_value(PARAM_FLOAT, 'Final grade'),
            'updated' => new external_value(PARAM_BOOL, 'Whether the grade was updated'),
        ]);
    }
}
