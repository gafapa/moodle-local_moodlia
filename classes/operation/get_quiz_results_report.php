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
 * Quiz results report operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

use mod_quiz\quiz_settings;

/**
 * Returns a compact attempt and grade report for enrolled users in a quiz.
 */
class get_quiz_results_report {
    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $limit Limit.
     * @param bool $includepreviews Includepreviews.
     * @return array
     */
    public static function execute(int $quizmoduleid, int $limit = 100, bool $includepreviews = true): array {
        if ($limit < 1) {
            throw new \invalid_parameter_exception('limit must be a positive integer.');
        }

        question_tools::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        $quiz = $quizobj->get_quiz();
        $enrolled = get_enrolled_users::execute((int) $course->id);
        $users = array_slice($enrolled['users'], 0, $limit);

        $rows = [];
        $warnings = [];
        $userswithattempts = 0;
        $userswithfinishedattempts = 0;
        $userswithgrades = 0;
        $bestgrades = [];

        foreach ($users as $user) {
            $userid = (int) ($user['user_id'] ?? 0);
            $attempts = [];
            $bestgrade = [
                'has_grade' => false,
                'grade' => 0.0,
                'grade_to_pass' => 0.0,
                'feedback_text' => '',
                'feedback_format' => 0,
            ];

            try {
                $attemptpayload = get_quiz_attempts::execute((int) $cm->id, $userid, 'all', $includepreviews);
                $attempts = is_array($attemptpayload['attempts'] ?? null) ? $attemptpayload['attempts'] : [];
            } catch (\Throwable $exception) {
                $warnings[] = self::warning_for_user($userid, 'quiz_attempts_unavailable', $exception);
            }

            try {
                $bestgrade = get_quiz_user_best_grade::execute((int) $cm->id, $userid);
            } catch (\Throwable $exception) {
                $warnings[] = self::warning_for_user($userid, 'quiz_best_grade_unavailable', $exception);
            }

            $attemptsummary = self::summarise_attempts($attempts);
            $hasgrade = (bool) ($bestgrade['has_grade'] ?? false);
            $grade = (float) ($bestgrade['grade'] ?? 0);

            if ($attemptsummary['attempt_count'] > 0) {
                $userswithattempts++;
            }
            if ($attemptsummary['finished_attempt_count'] > 0) {
                $userswithfinishedattempts++;
            }
            if ($hasgrade) {
                $userswithgrades++;
                $bestgrades[] = $grade;
            }

            $rows[] = [
                'user_id' => $userid,
                'username' => (string) ($user['username'] ?? ''),
                'fullname' => (string) ($user['fullname'] ?? ''),
                'roles' => array_values(array_map('strval', $user['roles'] ?? [])),
                'attempt_count' => $attemptsummary['attempt_count'],
                'finished_attempt_count' => $attemptsummary['finished_attempt_count'],
                'in_progress_attempt_count' => $attemptsummary['in_progress_attempt_count'],
                'preview_attempt_count' => $attemptsummary['preview_attempt_count'],
                'last_attempt_state' => $attemptsummary['last_attempt_state'],
                'last_attempt_time_start' => $attemptsummary['last_attempt_time_start'],
                'last_attempt_time_finish' => $attemptsummary['last_attempt_time_finish'],
                'has_grade' => $hasgrade,
                'best_grade' => $grade,
                'grade_to_pass' => (float) ($bestgrade['grade_to_pass'] ?? 0),
                'grade_percentage' => self::percentage($grade, (float) ($quiz->grade ?? 0)),
                'feedback_text' => (string) ($bestgrade['feedback_text'] ?? ''),
                'feedback_format' => (int) ($bestgrade['feedback_format'] ?? 0),
            ];
        }

        return [
            'course_id' => (int) $course->id,
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'quiz_name' => (string) ($quiz->name ?? ''),
            'requested_limit' => $limit,
            'returned_user_count' => count($rows),
            'total_enrolled_user_count' => count($enrolled['users'] ?? []),
            'quiz_grade_max' => (float) ($quiz->grade ?? 0),
            'users_with_attempts_count' => $userswithattempts,
            'users_with_finished_attempts_count' => $userswithfinishedattempts,
            'users_with_grades_count' => $userswithgrades,
            'average_best_grade' => self::average($bestgrades),
            'average_best_grade_percentage' => self::percentage(self::average($bestgrades), (float) ($quiz->grade ?? 0)),
            'users' => $rows,
            'warnings' => $warnings,
        ];
    }

    /**
     * Summarise quiz attempts for one user.
     *
     * @param array $attempts Attempts.
     * @return array
     */
    private static function summarise_attempts(array $attempts): array {
        $finished = 0;
        $inprogress = 0;
        $previews = 0;
        $last = null;

        foreach ($attempts as $attempt) {
            if ((string) ($attempt['state'] ?? '') === 'finished') {
                $finished++;
            }
            if ((string) ($attempt['state'] ?? '') === 'inprogress') {
                $inprogress++;
            }
            if (!empty($attempt['preview'])) {
                $previews++;
            }
            if ($last === null || (int) ($attempt['time_modified'] ?? 0) >= (int) ($last['time_modified'] ?? 0)) {
                $last = $attempt;
            }
        }

        return [
            'attempt_count' => count($attempts),
            'finished_attempt_count' => $finished,
            'in_progress_attempt_count' => $inprogress,
            'preview_attempt_count' => $previews,
            'last_attempt_state' => (string) ($last['state'] ?? ''),
            'last_attempt_time_start' => (int) ($last['time_start'] ?? 0),
            'last_attempt_time_finish' => (int) ($last['time_finish'] ?? 0),
        ];
    }

    /**
     * Return a percentage with stable precision.
     *
     * @param float $value Value.
     * @param float $maximum Maximum.
     * @return float
     */
    private static function percentage(float $value, float $maximum): float {
        if ($maximum <= 0) {
            return 0.0;
        }

        return round(($value / $maximum) * 100, 5);
    }

    /**
     * Average numeric values.
     *
     * @param array $values Values.
     * @return float
     */
    private static function average(array $values): float {
        if (empty($values)) {
            return 0.0;
        }

        return round(array_sum($values) / count($values), 5);
    }

    /**
     * Build a report warning for one user.
     *
     * @param int $userid Userid.
     * @param string $code Code.
     * @param \Throwable $exception Exception.
     * @return array
     */
    private static function warning_for_user(int $userid, string $code, \Throwable $exception): array {
        $message = trim($exception->getMessage());

        return [
            'user_id' => $userid,
            'warning_code' => $code,
            'message' => $message !== '' ? $message : get_class($exception),
        ];
    }
}
