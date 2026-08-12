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
 * Course completion status operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle course completion status for a user.
 */
class get_course_completion_status {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $courseid, int $userid = 0): array {
        global $USER;

        completion_tools::require_completion_api();

        if ($userid < 0) {
            throw new \invalid_parameter_exception('user_id must be zero or a positive integer.');
        }

        $course = course_tools::get_course($courseid);
        $resolveduserid = $userid > 0 ? $userid : (int) $USER->id;
        $completion = new \completion_info($course);

        if (!$completion->is_enabled()) {
            return [
                'course_id' => (int) $course->id,
                'user_id' => $resolveduserid,
                'completed' => false,
                'aggregation' => 0,
                'criteria_count' => 0,
                'completed_criteria_count' => 0,
                'status_json' => completion_tools::json_value([
                    'completed' => false,
                    'aggregation' => 0,
                    'completions' => [],
                    'source_error' => 'completionnotenabled',
                ]),
                'warnings' => [[
                    'item' => 'course',
                    'item_id' => (int) $course->id,
                    'warning_code' => 'completionnotenabled',
                    'message' => 'Course completion is not enabled.',
                ]],
            ];
        }

        try {
            $result = \core_completion_external::get_course_completion_status((int) $course->id, $resolveduserid);
        } catch (\moodle_exception $exception) {
            if (!in_array($exception->errorcode, ['nocriteriaset', 'completionnotenabled'], true)) {
                throw $exception;
            }

            return [
                'course_id' => (int) $course->id,
                'user_id' => $resolveduserid,
                'completed' => false,
                'aggregation' => 0,
                'criteria_count' => 0,
                'completed_criteria_count' => 0,
                'status_json' => completion_tools::json_value([
                    'completed' => false,
                    'aggregation' => 0,
                    'completions' => [],
                    'source_error' => $exception->errorcode,
                ]),
                'warnings' => [[
                    'item' => 'course',
                    'item_id' => (int) $course->id,
                    'warning_code' => $exception->errorcode,
                    'message' => (string) $exception->getMessage(),
                ]],
            ];
        }

        $status = completion_tools::to_array($result['completionstatus'] ?? []);
        $criteria = completion_tools::to_array($status['completions'] ?? []);

        $completedcriteria = 0;
        foreach ($criteria as $criterion) {
            $complete = $criterion['complete'] ?? $criterion['status'] ?? false;
            if ((bool) $complete) {
                $completedcriteria++;
            }
        }

        return [
            'course_id' => (int) $course->id,
            'user_id' => $resolveduserid,
            'completed' => (bool) ($status['completed'] ?? false),
            'aggregation' => (int) ($status['aggregation'] ?? 0),
            'criteria_count' => count($criteria),
            'completed_criteria_count' => $completedcriteria,
            'status_json' => completion_tools::json_value($status),
            'warnings' => completion_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
