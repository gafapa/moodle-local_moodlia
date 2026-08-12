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
 * Course progress report operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns a compact progress and grade report for enrolled users in a course.
 */
class get_course_progress_report {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $limit Maximum users to include.
     * @return array
     */
    public static function execute(int $courseid, int $limit = 100): array {
        if ($limit < 1) {
            throw new \invalid_parameter_exception('limit must be a positive integer.');
        }

        $course = course_tools::get_course($courseid);
        $enrolled = get_enrolled_users::execute((int) $course->id);
        $reportwarnings = [];
        $gradeitems = ['items' => []];
        try {
            $gradeitems = get_grade_items::execute((int) $course->id);
        } catch (\Throwable $exception) {
            $reportwarnings[] = [
                'user_id' => 0,
                'warning_code' => 'gradebook_unavailable',
                'message' => self::safe_warning_message($exception),
            ];
        }
        $coursegradeitems = is_array($gradeitems['items'] ?? null) ? $gradeitems['items'] : [];
        $users = array_slice($enrolled['users'], 0, $limit);

        $rows = [];
        $warnings = $reportwarnings;
        $completedusers = 0;
        $percentages = [];

        foreach ($users as $user) {
            $userid = (int) ($user['user_id'] ?? 0);
            $completion = get_course_completion_status::execute((int) $course->id, $userid);
            $activitycompletion = get_activity_completion_statuses::execute((int) $course->id, $userid);
            $gradewarnings = [];
            $grades = ['items' => []];
            if (!empty($coursegradeitems)) {
                try {
                    $grades = get_user_grades::execute((int) $course->id, $userid);
                } catch (\Throwable $exception) {
                    $gradewarnings[] = [
                        'user_id' => $userid,
                        'warning_code' => 'user_grades_unavailable',
                        'message' => self::safe_warning_message($exception),
                    ];
                }
            }
            $usergradeitems = is_array($grades['items'] ?? null) ? $grades['items'] : [];
            $activitystatuses = is_array($activitycompletion['statuses'] ?? null) ? $activitycompletion['statuses'] : [];
            $completionwarnings = is_array($completion['warnings'] ?? null) ? $completion['warnings'] : [];
            $activitywarnings = is_array($activitycompletion['warnings'] ?? null) ? $activitycompletion['warnings'] : [];
            $gradesummary = self::summarise_grades($usergradeitems);
            $activitysummary = self::summarise_activity_completion($activitystatuses);
            $userwarnings = array_merge(
                $gradewarnings,
                self::warnings_for_user($userid, $completionwarnings, $activitywarnings)
            );

            if ((bool) ($completion['completed'] ?? false)) {
                $completedusers++;
            }
            if ($gradesummary['graded_item_count'] > 0) {
                $percentages[] = $gradesummary['grade_percentage'];
            }

            $warnings = array_merge($warnings, $userwarnings);
            $rows[] = [
                'user_id' => $userid,
                'username' => (string) ($user['username'] ?? ''),
                'fullname' => (string) ($user['fullname'] ?? ''),
                'roles' => array_values(array_map('strval', $user['roles'] ?? [])),
                'course_completed' => (bool) ($completion['completed'] ?? false),
                'course_criteria_count' => (int) ($completion['criteria_count'] ?? 0),
                'completed_course_criteria_count' => (int) ($completion['completed_criteria_count'] ?? 0),
                'tracked_activity_count' => $activitysummary['tracked_activity_count'],
                'completed_activity_count' => $activitysummary['completed_activity_count'],
                'grade_item_count' => $gradesummary['grade_item_count'],
                'graded_item_count' => $gradesummary['graded_item_count'],
                'grade_points' => $gradesummary['grade_points'],
                'grade_max' => $gradesummary['grade_max'],
                'grade_percentage' => $gradesummary['grade_percentage'],
                'grade_percentage_formatted' => $gradesummary['grade_percentage_formatted'],
                'warnings_count' => count($userwarnings),
            ];
        }

        return [
            'course_id' => (int) $course->id,
            'requested_limit' => $limit,
            'returned_user_count' => count($rows),
            'total_enrolled_user_count' => count($enrolled['users'] ?? []),
            'grade_item_count' => count($coursegradeitems),
            'tracked_activity_count' => self::maximum_tracked_activity_count($rows),
            'completed_user_count' => $completedusers,
            'average_grade_percentage' => self::average($percentages),
            'users' => $rows,
            'warnings' => $warnings,
        ];
    }

    /**
     * Summarise Moodle grade items for one user.
     *
     * @param array $items User grade items.
     * @return array
     */
    private static function summarise_grades(array $items): array {
        $gradable = array_values(array_filter($items, static function(array $item): bool {
            return ($item['item_type'] ?? '') === 'mod'
                && empty($item['hidden'])
                && (float) ($item['grade_max'] ?? 0) > 0;
        }));

        if (empty($gradable)) {
            $gradable = array_values(array_filter($items, static function(array $item): bool {
                return empty($item['hidden']) && (float) ($item['grade_max'] ?? 0) > 0;
            }));
        }

        $points = 0.0;
        $maximum = 0.0;
        foreach ($gradable as $item) {
            $points += (float) ($item['grade_raw'] ?? 0);
            $maximum += (float) ($item['grade_max'] ?? 0);
        }

        $percentage = $maximum > 0 ? round(($points / $maximum) * 100, 5) : 0.0;

        return [
            'grade_item_count' => count($items),
            'graded_item_count' => count($gradable),
            'grade_points' => round($points, 5),
            'grade_max' => round($maximum, 5),
            'grade_percentage' => $percentage,
            'grade_percentage_formatted' => $maximum > 0 ? format_float($percentage, 2) . '%' : '',
        ];
    }

    /**
     * Summarise activity completion statuses for one user.
     *
     * @param array $statuses Moodle activity completion statuses.
     * @return array
     */
    private static function summarise_activity_completion(array $statuses): array {
        $tracked = 0;
        $completed = 0;

        foreach ($statuses as $status) {
            if (empty($status['has_completion'])) {
                continue;
            }
            $tracked++;
            if ((int) ($status['state'] ?? 0) > COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        return [
            'tracked_activity_count' => $tracked,
            'completed_activity_count' => $completed,
        ];
    }

    /**
     * Attach Moodle warnings to a user row.
     *
     * @param int $userid Moodle user id.
     * @param array $completionwarnings Course completion warnings.
     * @param array $activitywarnings Activity completion warnings.
     * @return array
     */
    private static function warnings_for_user(int $userid, array $completionwarnings, array $activitywarnings): array {
        $items = [];
        foreach (array_merge($completionwarnings, $activitywarnings) as $warning) {
            $items[] = [
                'user_id' => $userid,
                'warning_code' => (string) ($warning['warning_code'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Return the largest tracked activity count across user rows.
     *
     * @param array $rows Report user rows.
     * @return int
     */
    private static function maximum_tracked_activity_count(array $rows): int {
        $maximum = 0;
        foreach ($rows as $row) {
            $maximum = max($maximum, (int) ($row['tracked_activity_count'] ?? 0));
        }

        return $maximum;
    }

    /**
     * Average numeric values.
     *
     * @param array $values Numeric values.
     * @return float
     */
    private static function average(array $values): float {
        if (empty($values)) {
            return 0.0;
        }

        return round(array_sum($values) / count($values), 5);
    }

    /**
     * Return a safe warning message from an exception.
     *
     * @param \Throwable $exception Source exception.
     * @return string
     */
    private static function safe_warning_message(\Throwable $exception): string {
        $message = trim($exception->getMessage());

        return $message !== '' ? $message : get_class($exception);
    }
}
