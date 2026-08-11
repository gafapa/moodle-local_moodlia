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
 * Quiz attempt and interaction helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

/**
 * Isolates quiz attempts, access information, views, and result formatting.
 */
class question_quiz_attempt_tools extends question_tools {
    /**
     * Start a quiz attempt or preview for the current user.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param bool $forcenew Force a new attempt when Moodle permits it.
     * @return array
     */
    public static function start_quiz_attempt(int $quizmoduleid, bool $forcenew = false): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::start_attempt((int) $quizobj->get_quizid(), [], $forcenew);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt' => self::quiz_attempt_to_response($result['attempt'] ?? []),
        ];
    }

    /**
     * Return quiz attempts for a user.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $userid Moodle user id, or 0 for current user.
     * @param string $status Attempt status: all, finished, or unfinished.
     * @param bool $includepreviews Include preview attempts.
     * @return array
     */
    public static function get_quiz_attempts(
        int $quizmoduleid,
        int $userid = 0,
        string $status = 'all',
        bool $includepreviews = true
    ): array {
        self::require_quiz_api();

        if (!in_array($status, ['all', 'finished', 'unfinished'], true)) {
            throw new \invalid_parameter_exception('status must be one of: all, finished, unfinished.');
        }
        if ($userid < 0) {
            throw new \invalid_parameter_exception('user_id must be zero or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::get_user_quiz_attempts(
            (int) $quizobj->get_quizid(),
            $userid,
            $status,
            $includepreviews
        );
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        $attempts = [];
        foreach (($result['attempts'] ?? []) as $attempt) {
            $attempts[] = self::quiz_attempt_to_response($attempt);
        }

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'user_id' => $userid,
            'attempts' => $attempts,
        ];
    }

    /**
     * Return Moodle quizzes in selected courses.
     *
     * @param array $courseids Moodle course ids.
     * @return array
     */
    public static function get_course_quizzes(array $courseids = []): array {
        self::require_quiz_api();

        $cleanids = [];
        foreach ($courseids as $courseid) {
            $courseid = (int) $courseid;
            if ($courseid <= 0) {
                throw new \invalid_parameter_exception('course_ids must contain positive integers.');
            }
            $cleanids[] = $courseid;
        }

        $result = \mod_quiz_external::get_quizzes_by_courses($cleanids);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        $quizzes = [];
        foreach (($result['quizzes'] ?? []) as $quiz) {
            $quizzes[] = self::quiz_summary_to_response($quiz);
        }

        return [
            'course_ids' => array_values($cleanids),
            'count' => count($quizzes),
            'quizzes' => $quizzes,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return access information for a quiz attempt.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id, or 0 for the current user's last attempt.
     * @return array
     */
    public static function get_quiz_attempt_access_information(int $quizmoduleid, int $attemptid = 0): array {
        self::require_quiz_api();

        if ($attemptid < 0) {
            throw new \invalid_parameter_exception('attempt_id must be zero or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        if ($attemptid > 0) {
            self::validate_quiz_attempt_module($quizobj, $attemptid);
        }

        $result = \mod_quiz_external::get_attempt_access_information((int) $quizobj->get_quizid(), $attemptid);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'end_time' => (int) ($result['endtime'] ?? 0),
            'is_finished' => (bool) ($result['isfinished'] ?? false),
            'is_preflight_check_required' => (bool) ($result['ispreflightcheckrequired'] ?? false),
            'prevent_new_attempt_reasons' => array_values(array_map('strval', $result['preventnewattemptreasons'] ?? [])),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return rendered data for one page of a quiz attempt.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param int $page Attempt page number.
     * @param array $preflightdata Preflight data pairs.
     * @return array
     */
    public static function get_quiz_attempt_data(
        int $quizmoduleid,
        int $attemptid,
        int $page = 0,
        array $preflightdata = []
    ): array {
        self::require_quiz_api();

        if ($page < 0) {
            throw new \invalid_parameter_exception('page must be zero or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::get_attempt_data($attemptid, $page, $preflightdata);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);
        $attempt = self::quiz_attempt_to_response($result['attempt'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt' => $attempt,
            'page' => $page,
            'next_page' => (int) ($result['nextpage'] ?? -1),
            'messages' => array_values(array_map('strval', $result['messages'] ?? [])),
            'questions' => self::quiz_attempt_questions_to_response($result['questions'] ?? []),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return the pre-submit summary for a quiz attempt.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param array $preflightdata Preflight data pairs.
     * @return array
     */
    public static function get_quiz_attempt_summary(int $quizmoduleid, int $attemptid, array $preflightdata = []): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::get_attempt_summary($attemptid, $preflightdata);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'total_unanswered' => (int) ($result['totalunanswered'] ?? 0),
            'questions' => self::quiz_attempt_questions_to_response($result['questions'] ?? []),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Save current responses for a quiz attempt without finishing it.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param array $data Attempt response name/value pairs.
     * @param array $preflightdata Preflight data pairs.
     * @return array
     */
    public static function save_quiz_attempt(
        int $quizmoduleid,
        int $attemptid,
        array $data = [],
        array $preflightdata = []
    ): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::save_attempt($attemptid, $data, $preflightdata);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'saved' => (bool) ($result['status'] ?? true),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Process responses for a quiz attempt and optionally finish it.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param array $data Attempt response name/value pairs.
     * @param bool $finishattempt Whether to finish the attempt.
     * @param bool $timeup Whether processing is due to timer expiry.
     * @param array $preflightdata Preflight data pairs.
     * @return array
     */
    public static function process_quiz_attempt(
        int $quizmoduleid,
        int $attemptid,
        array $data = [],
        bool $finishattempt = false,
        bool $timeup = false,
        array $preflightdata = []
    ): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::process_attempt($attemptid, $data, $finishattempt, $timeup, $preflightdata);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);
        $state = (string) ($result['state'] ?? '');

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'state' => $state,
            'finished' => $state === 'finished',
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return review data for a finished quiz attempt.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param int $page Review page number, or -1 for all pages.
     * @return array
     */
    public static function get_quiz_attempt_review(int $quizmoduleid, int $attemptid, int $page = -1): array {
        self::require_quiz_api();

        if ($page < -1) {
            throw new \invalid_parameter_exception('page must be -1, zero, or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::get_attempt_review($attemptid, $page);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt' => self::quiz_attempt_to_response($result['attempt'] ?? []),
            'grade' => (string) ($result['grade'] ?? ''),
            'page' => $page,
            'additional_data' => self::quiz_attempt_additional_data_to_response($result['additionaldata'] ?? []),
            'questions' => self::quiz_attempt_questions_to_response($result['questions'] ?? []),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Register a quiz attempt review view.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @return array
     */
    public static function view_quiz_attempt_review(int $quizmoduleid, int $attemptid): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::view_attempt_review($attemptid);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'viewed' => (bool) ($result['status'] ?? true),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Register a quiz attempt page view.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param int $page Attempt page number.
     * @param array $preflightdata Preflight data pairs.
     * @return array
     */
    public static function view_quiz_attempt(
        int $quizmoduleid,
        int $attemptid,
        int $page = 0,
        array $preflightdata = []
    ): array {
        self::require_quiz_api();

        if ($page < 0) {
            throw new \invalid_parameter_exception('page must be zero or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::view_attempt($attemptid, $page, $preflightdata);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'page' => $page,
            'viewed' => (bool) ($result['status'] ?? true),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Register a quiz attempt summary view.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $attemptid Attempt id.
     * @param array $preflightdata Preflight data pairs.
     * @return array
     */
    public static function view_quiz_attempt_summary(int $quizmoduleid, int $attemptid, array $preflightdata = []): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        self::validate_quiz_attempt_module($quizobj, $attemptid);

        $result = \mod_quiz_external::view_attempt_summary($attemptid, $preflightdata);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'attempt_id' => $attemptid,
            'viewed' => (bool) ($result['status'] ?? true),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return access information for a quiz.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @return array
     */
    public static function get_quiz_access_information(int $quizmoduleid): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::get_quiz_access_information((int) $quizobj->get_quizid());
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'can_attempt' => (bool) ($result['canattempt'] ?? false),
            'can_manage' => (bool) ($result['canmanage'] ?? false),
            'can_preview' => (bool) ($result['canpreview'] ?? false),
            'can_review_my_attempts' => (bool) ($result['canreviewmyattempts'] ?? false),
            'can_view_reports' => (bool) ($result['canviewreports'] ?? false),
            'access_rules' => array_values(array_map('strval', $result['accessrules'] ?? [])),
            'active_rule_names' => array_values(array_map('strval', $result['activerulenames'] ?? [])),
            'prevent_access_reasons' => array_values(array_map('strval', $result['preventaccessreasons'] ?? [])),
        ];
    }

    /**
     * Return combined quiz review option visibility.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $userid Moodle user id, or 0 for current user.
     * @return array
     */
    public static function get_quiz_combined_review_options(int $quizmoduleid, int $userid = 0): array {
        self::require_quiz_api();

        if ($userid < 0) {
            throw new \invalid_parameter_exception('user_id must be zero or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::get_combined_review_options((int) $quizobj->get_quizid(), $userid);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'user_id' => $userid,
            'some_options' => self::quiz_review_options_to_response($result['someoptions'] ?? []),
            'all_options' => self::quiz_review_options_to_response($result['alloptions'] ?? []),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Register a quiz view event and completion progress.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @return array
     */
    public static function view_quiz(int $quizmoduleid): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::view_quiz((int) $quizobj->get_quizid());
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'viewed' => (bool) ($result['status'] ?? true),
        ];
    }

    /**
     * Return the best grade for a quiz user.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $userid Moodle user id, or 0 for current user.
     * @return array
     */
    public static function get_quiz_user_best_grade(int $quizmoduleid, int $userid = 0): array {
        self::require_quiz_api();

        if ($userid < 0) {
            throw new \invalid_parameter_exception('user_id must be zero or a positive integer.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::get_user_best_grade((int) $quizobj->get_quizid(), $userid);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);
        $feedback = (array) ($result['feedback'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'user_id' => $userid,
            'has_grade' => (bool) ($result['hasgrade'] ?? false),
            'grade' => (float) ($result['grade'] ?? 0),
            'grade_to_pass' => (float) ($result['gradetopass'] ?? 0),
            'feedback_text' => (string) ($feedback['feedbacktext'] ?? ''),
            'feedback_format' => (int) ($feedback['feedbackformat'] ?? 0),
        ];
    }

    /**
     * Return quiz overall feedback for a grade value.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param float $grade Grade value.
     * @return array
     */
    public static function get_quiz_feedback_for_grade(int $quizmoduleid, float $grade): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::get_quiz_feedback_for_grade((int) $quizobj->get_quizid(), $grade);
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'grade' => $grade,
            'feedback_text' => (string) ($result['feedbacktext'] ?? ''),
            'feedback_format' => (int) ($result['feedbacktextformat'] ?? 0),
        ];
    }

    /**
     * Return question types required by a quiz.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @return array
     */
    public static function get_quiz_required_question_types(int $quizmoduleid): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        $result = \mod_quiz_external::get_quiz_required_qtypes((int) $quizobj->get_quizid());
        self::fail_on_quiz_warnings($result['warnings'] ?? []);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'question_types' => array_values(array_map('strval', $result['questiontypes'] ?? [])),
        ];
    }

    /**
     * Convert a Moodle quiz attempt to the canonical response shape.
     *
     * @param mixed $attempt Moodle attempt payload.
     * @return array
     */
    private static function quiz_attempt_to_response($attempt): array {
        $attempt = self::value_to_array($attempt);
        $sumgrades = $attempt['sumgrades'] ?? null;

        return [
            'attempt_id' => (int) ($attempt['id'] ?? 0),
            'quiz_id' => (int) ($attempt['quiz'] ?? 0),
            'user_id' => (int) ($attempt['userid'] ?? 0),
            'attempt_number' => (int) ($attempt['attempt'] ?? 0),
            'unique_id' => (int) ($attempt['uniqueid'] ?? 0),
            'state' => (string) ($attempt['state'] ?? ''),
            'preview' => (bool) ($attempt['preview'] ?? false),
            'time_start' => (int) ($attempt['timestart'] ?? 0),
            'time_finish' => (int) ($attempt['timefinish'] ?? 0),
            'time_modified' => (int) ($attempt['timemodified'] ?? 0),
            'sum_grades' => $sumgrades === null ? 0.0 : (float) $sumgrades,
        ];
    }

    /**
     * Convert Moodle quiz summary payload to the canonical response shape.
     *
     * @param mixed $quiz Moodle quiz payload.
     * @return array
     */
    private static function quiz_summary_to_response($quiz): array {
        $quiz = self::value_to_array($quiz);

        return [
            'quiz_id' => (int) ($quiz['id'] ?? 0),
            'course_id' => (int) ($quiz['course'] ?? 0),
            'quiz_module_id' => (int) ($quiz['coursemodule'] ?? 0),
            'name' => format_string((string) ($quiz['name'] ?? '')),
            'intro' => (string) ($quiz['intro'] ?? ''),
            'intro_format' => (int) ($quiz['introformat'] ?? 0),
            'time_open' => (int) ($quiz['timeopen'] ?? 0),
            'time_close' => (int) ($quiz['timeclose'] ?? 0),
            'time_limit' => (int) ($quiz['timelimit'] ?? 0),
            'attempts_allowed' => (int) ($quiz['attempts'] ?? 0),
            'grade' => (float) ($quiz['grade'] ?? 0),
            'sum_grades' => (float) ($quiz['sumgrades'] ?? 0),
            'preferred_behaviour' => (string) ($quiz['preferredbehaviour'] ?? ''),
            'questions_per_page' => (int) ($quiz['questionsperpage'] ?? 0),
            'navigation_method' => (string) ($quiz['navmethod'] ?? ''),
            'has_feedback' => (bool) ($quiz['hasfeedback'] ?? false),
            'visible' => (bool) ($quiz['visible'] ?? true),
            'url' => (new \moodle_url('/mod/quiz/view.php', ['id' => (int) ($quiz['coursemodule'] ?? 0)]))->out(false),
        ];
    }

    /**
     * Convert Moodle quiz review options into name/value rows.
     *
     * @param mixed $options Moodle review options payload.
     * @return array
     */
    private static function quiz_review_options_to_response($options): array {
        $options = self::value_to_array($options);
        ksort($options);

        $responses = [];
        foreach ($options as $name => $value) {
            $responses[] = [
                'name' => (string) $name,
                'value' => (bool) $value,
            ];
        }

        return $responses;
    }

    /**
     * Decode preflight data JSON into Moodle external name/value pairs.
     *
     * @param string $json JSON array string.
     * @return array
     */
    public static function decode_preflight_data(string $json): array {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('preflight_data must be a JSON array of name/value objects.');
        }

        $pairs = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !array_key_exists('name', $item) || !array_key_exists('value', $item)) {
                throw new \invalid_parameter_exception('preflight_data items must include name and value.');
            }
            $pairs[] = [
                'name' => clean_param((string) $item['name'], PARAM_ALPHANUMEXT),
                'value' => (string) $item['value'],
            ];
        }

        return $pairs;
    }

    /**
     * Decode attempt response JSON into Moodle external name/value pairs.
     *
     * @param string $json JSON array string.
     * @return array
     */
    public static function decode_quiz_attempt_data(string $json): array {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('data must be a JSON array of name/value objects.');
        }

        $pairs = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !array_key_exists('name', $item) || !array_key_exists('value', $item)) {
                throw new \invalid_parameter_exception('data items must include name and value.');
            }
            $pairs[] = [
                'name' => clean_param((string) $item['name'], PARAM_RAW),
                'value' => (string) $item['value'],
            ];
        }

        return $pairs;
    }

    /**
     * Decode an integer id list from JSON, comma-separated text, or one scalar id.
     *
     * @param string $json JSON array string, comma-separated ids, or one id.
     * @return array
     */
    public static function decode_id_list(string $json): array {
        $json = trim($json);
        if ($json === '' || $json === '[]') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $rawids = $decoded;
        } else if (preg_match('/^[0-9]+(?:\s*,\s*[0-9]+)*$/', $json)) {
            $rawids = preg_split('/\s*,\s*/', $json);
        } else {
            throw new \invalid_parameter_exception('course_ids must be a JSON array, comma-separated list, or single positive integer.');
        }

        $ids = [];
        foreach ($rawids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                throw new \invalid_parameter_exception('course_ids must contain positive integers.');
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Validate that an attempt belongs to the selected quiz module.
     *
     * @param quiz_settings $quizobj Quiz settings object.
     * @param int $attemptid Attempt id.
     */
    private static function validate_quiz_attempt_module(quiz_settings $quizobj, int $attemptid): void {
        if ($attemptid <= 0) {
            throw new \invalid_parameter_exception('attempt_id must be a positive integer.');
        }

        $attemptobj = quiz_attempt::create($attemptid);
        if ((int) $attemptobj->get_quizid() !== (int) $quizobj->get_quizid()) {
            throw new \invalid_parameter_exception('attempt_id must reference an attempt in the selected quiz module.');
        }
    }

    /**
     * Convert Moodle attempt questions into canonical response rows.
     *
     * @param array $questions Moodle question payloads.
     * @return array
     */
    private static function quiz_attempt_questions_to_response(array $questions): array {
        $responses = [];
        foreach ($questions as $question) {
            $question = self::value_to_array($question);
            $responses[] = [
                'slot' => (int) ($question['slot'] ?? 0),
                'question_type' => (string) ($question['type'] ?? ''),
                'page' => (int) ($question['page'] ?? 0),
                'question_number' => (string) ($question['questionnumber'] ?? ''),
                'html' => (string) ($question['html'] ?? ''),
                'flagged' => (bool) ($question['flagged'] ?? false),
                'sequence_check' => (int) ($question['sequencecheck'] ?? 0),
                'last_action_time' => (int) ($question['lastactiontime'] ?? 0),
                'has_autosaved_step' => (bool) ($question['hasautosavedstep'] ?? false),
                'state' => (string) ($question['state'] ?? ''),
                'state_class' => (string) ($question['stateclass'] ?? ''),
                'status' => (string) ($question['status'] ?? ''),
                'blocked_by_previous' => (bool) ($question['blockedbyprevious'] ?? false),
                'mark' => (string) ($question['mark'] ?? ''),
                'max_mark' => (float) ($question['maxmark'] ?? 0),
                'settings' => (string) ($question['settings'] ?? ''),
                'response_file_area_count' => count((array) ($question['responsefileareas'] ?? [])),
            ];
        }

        return $responses;
    }

    /**
     * Convert Moodle attempt review additional data into canonical rows.
     *
     * @param array $additionaldata Moodle additional data payloads.
     * @return array
     */
    private static function quiz_attempt_additional_data_to_response(array $additionaldata): array {
        $responses = [];
        foreach ($additionaldata as $item) {
            $item = self::value_to_array($item);
            $responses[] = [
                'id' => (string) ($item['id'] ?? ''),
                'title' => (string) ($item['title'] ?? ''),
                'content' => (string) ($item['content'] ?? ''),
            ];
        }

        return $responses;
    }

    /**
     * Normalize Moodle warning payloads.
     *
     * @param array $warnings Moodle warning payloads.
     * @return array
     */
    private static function warnings_to_response(array $warnings): array {
        $mapped = [];
        foreach ($warnings as $warning) {
            $warning = self::value_to_array($warning);
            $mapped[] = [
                'item' => (string) ($warning['item'] ?? ''),
                'item_id' => (int) ($warning['itemid'] ?? 0),
                'warning_code' => (string) ($warning['warningcode'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $mapped;
    }

    /**
     * Throw when Moodle returns quiz external API warnings.
     *
     * @param array $warnings Moodle warning payloads.
     */
    private static function fail_on_quiz_warnings(array $warnings): void {
        if (empty($warnings)) {
            return;
        }

        $warning = self::value_to_array(reset($warnings));
        $message = (string) ($warning['message'] ?? $warning['warningcode'] ?? 'Moodle quiz operation returned warnings.');
        throw new \moodle_exception('error', 'local_moodlia', '', null, $message);
    }

    /**
     * Convert objects and nested arrays to arrays.
     *
     * @param mixed $value Value to convert.
     * @return array
     */
    private static function value_to_array($value): array {
        if (is_array($value)) {
            return array_map(static function($item) {
                return is_object($item) || is_array($item) ? self::value_to_array($item) : $item;
            }, $value);
        }

        if (is_object($value)) {
            return self::value_to_array(get_object_vars($value));
        }

        return [];
    }
}
