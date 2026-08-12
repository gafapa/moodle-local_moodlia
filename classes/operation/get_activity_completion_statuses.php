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
 * Activity completion statuses operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle activity completion statuses for a user in a course.
 */
class get_activity_completion_statuses {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $userid Moodle user id, or 0 for the current user.
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
        try {
            $result = \core_completion_external::get_activities_completion_status((int) $course->id, $resolveduserid);
        } catch (\coding_exception $exception) {
            return self::fallback_statuses($course, $resolveduserid, $exception->getMessage());
        }

        $statuses = [];

        foreach (($result['statuses'] ?? []) as $status) {
            $status = completion_tools::to_array($status);
            $statuses[] = self::external_status_to_response($status);
        }

        return [
            'course_id' => (int) $course->id,
            'user_id' => $resolveduserid,
            'statuses' => $statuses,
            'warnings' => completion_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical status item from Moodle external output.
     *
     * @param array $status Moodle completion status.
     * @return array
     */
    private static function external_status_to_response(array $status): array {
        return [
            'module_id' => (int) ($status['cmid'] ?? 0),
            'module_type' => (string) ($status['modname'] ?? ''),
            'instance_id' => (int) ($status['instance'] ?? 0),
            'state' => (int) ($status['state'] ?? 0),
            'time_completed' => (int) ($status['timecompleted'] ?? 0),
            'tracking' => (int) ($status['tracking'] ?? 0),
            'override_by' => (int) ($status['overrideby'] ?? 0),
            'value_used' => (bool) ($status['valueused'] ?? false),
            'has_completion' => (bool) ($status['hascompletion'] ?? false),
            'is_automatic' => (bool) ($status['isautomatic'] ?? false),
            'is_tracked_user' => (bool) ($status['istrackeduser'] ?? false),
            'user_visible' => (bool) ($status['uservisible'] ?? false),
            'details_json' => completion_tools::json_value($status['details'] ?? []),
        ];
    }

    /**
     * Build completion statuses through Moodle core completion APIs when an activity breaks the external formatter.
     *
     * @param \stdClass $course Moodle course.
     * @param int $userid Moodle user id.
     * @param string $reason Fallback reason.
     * @return array
     */
    private static function fallback_statuses(\stdClass $course, int $userid, string $reason): array {
        $completion = new \completion_info($course);
        $modinfo = get_fast_modinfo($course, $userid);
        $statuses = [];

        foreach ($modinfo->get_cms() as $cm) {
            $tracking = (int) ($cm->completion ?? COMPLETION_TRACKING_NONE);
            if ($tracking === COMPLETION_TRACKING_NONE) {
                continue;
            }

            $data = $completion->get_data($cm, false, $userid);
            $state = (int) ($data->completionstate ?? COMPLETION_INCOMPLETE);

            $statuses[] = [
                'module_id' => (int) $cm->id,
                'module_type' => (string) $cm->modname,
                'instance_id' => (int) $cm->instance,
                'state' => $state,
                'time_completed' => (int) ($data->timemodified ?? 0),
                'tracking' => $tracking,
                'override_by' => (int) ($data->overrideby ?? 0),
                'value_used' => $state !== COMPLETION_INCOMPLETE,
                'has_completion' => true,
                'is_automatic' => $tracking === COMPLETION_TRACKING_AUTOMATIC,
                'is_tracked_user' => true,
                'user_visible' => (bool) $cm->uservisible,
                'details_json' => '[]',
            ];
        }

        return [
            'course_id' => (int) $course->id,
            'user_id' => $userid,
            'statuses' => $statuses,
            'warnings' => [[
                'item' => 'completion',
                'item_id' => (int) $course->id,
                'warning_code' => 'completion_external_fallback',
                'message' => $reason,
            ]],
        ];
    }
}
