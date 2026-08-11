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
 * Shared assignment helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle assignment operations.
 */
class assignment_tools {
    /**
     * Load Moodle assignment APIs.
     */
    public static function require_assignment_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/externallib.php');
    }

    /**
     * Verify that a course module belongs to an assignment activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Assignment course module id.
     * @return \cm_info
     */
    public static function get_assignment_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'assign') {
            throw new \invalid_parameter_exception('module_id must reference an assignment activity.');
        }

        return $cm;
    }

    /**
     * Return assignment submission status through Moodle's assignment external API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @param int $userid Moodle user id, or 0 for the current user.
     * @return array
     */
    public static function get_submission_status(\stdClass $course, \cm_info $cm, int $userid = 0): array {
        self::require_assignment_api();

        $status = \mod_assign_external::get_submission_status((int) $cm->instance, $userid, 0);
        return self::status_to_response($course, $cm, $status, $userid);
    }

    /**
     * Return common assignment configuration details.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @return array
     */
    public static function get_assignment_details(\stdClass $course, \cm_info $cm): array {
        self::require_assignment_api();
        self::get_assignment_module($course, (int) $cm->id);

        $context = \context_module::instance($cm->id);
        $assignment = new \assign($context, $cm, $course);
        $instance = $assignment->get_instance();

        return [
            'assignment_id' => (int) $cm->instance,
            'activity' => (string) ($instance->activity ?? ''),
            'activity_format' => (int) ($instance->activityformat ?? FORMAT_HTML),
            'allowsubmissionsfromdate' => (int) ($instance->allowsubmissionsfromdate ?? 0),
            'duedate' => (int) ($instance->duedate ?? 0),
            'cutoffdate' => (int) ($instance->cutoffdate ?? 0),
            'gradingduedate' => (int) ($instance->gradingduedate ?? 0),
            'grade' => (float) ($instance->grade ?? 0),
            'teamsubmission' => (bool) ($instance->teamsubmission ?? false),
            'requireallteammemberssubmit' => (bool) ($instance->requireallteammemberssubmit ?? false),
            'teamsubmissiongroupingid' => (int) ($instance->teamsubmissiongroupingid ?? 0),
            'blindmarking' => (bool) ($instance->blindmarking ?? false),
            'hidegrader' => (bool) ($instance->hidegrader ?? false),
            'markingworkflow' => (bool) ($instance->markingworkflow ?? false),
            'markingallocation' => (bool) ($instance->markingallocation ?? false),
            'requiresubmissionstatement' => (bool) ($instance->requiresubmissionstatement ?? false),
            'submissiondrafts' => (bool) ($instance->submissiondrafts ?? false),
            'maxattempts' => (int) ($instance->maxattempts ?? 1),
            'attemptreopenmethod' => (string) ($instance->attemptreopenmethod ?? ''),
            'submissionattachments' => (bool) ($instance->submissionattachments ?? false),
            'sendnotifications' => (bool) ($instance->sendnotifications ?? false),
            'sendlatenotifications' => (bool) ($instance->sendlatenotifications ?? false),
            'sendstudentnotifications' => (bool) ($instance->sendstudentnotifications ?? false),
            'submission_plugins' => self::plugin_names($assignment->get_submission_plugins()),
            'feedback_plugins' => self::plugin_names($assignment->get_feedback_plugins()),
        ];
    }

    /**
     * Return assignments in a Moodle course through Moodle course and assignment APIs.
     *
     * @param \stdClass $course Moodle course.
     * @return array
     */
    public static function get_course_assignments(\stdClass $course): array {
        self::require_assignment_api();

        $assignments = [];
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_instances_of('assign') as $cm) {
            $modulecontext = \context_module::instance($cm->id);
            if (!$cm->uservisible || !has_capability('mod/assign:view', $modulecontext)) {
                continue;
            }

            $assignments[] = self::assignment_summary_to_response($course, $cm);
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($assignments),
            'assignments' => $assignments,
        ];
    }

    /**
     * Return assignment submissions through Moodle's assignment external API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @param string $status Optional submission status filter.
     * @param int $since Optional modified-since timestamp.
     * @param int $before Optional modified-before timestamp.
     * @return array
     */
    public static function get_submissions(
        \stdClass $course,
        \cm_info $cm,
        string $status = '',
        int $since = 0,
        int $before = 0
    ): array {
        self::require_assignment_api();

        if (!in_array($status, ['', 'new', 'draft', 'submitted', 'reopened'], true)) {
            throw new \invalid_parameter_exception('status must be one of: new, draft, submitted, reopened.');
        }
        if ($since < 0 || $before < 0) {
            throw new \invalid_parameter_exception('since and before must be zero or positive integers.');
        }

        $result = \mod_assign_external::get_submissions([(int) $cm->instance], $status, $since, $before);
        self::fail_on_warnings($result['warnings'] ?? []);

        $submissions = [];
        foreach (($result['assignments'] ?? []) as $assignment) {
            $assignment = self::to_array($assignment);
            foreach (($assignment['submissions'] ?? []) as $submission) {
                $submissions[] = self::submission_to_response($submission);
            }
        }

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'assignment_id' => (int) $cm->instance,
            'submissions' => $submissions,
        ];
    }

    /**
     * Return assignment grades through Moodle's assignment external API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @param int $since Optional modified-since timestamp.
     * @return array
     */
    public static function get_grades(\stdClass $course, \cm_info $cm, int $since = 0): array {
        self::require_assignment_api();

        if ($since < 0) {
            throw new \invalid_parameter_exception('since must be zero or a positive integer.');
        }

        $result = \mod_assign_external::get_grades([(int) $cm->instance], $since);
        self::fail_on_warnings($result['warnings'] ?? []);

        $grades = [];
        foreach (($result['assignments'] ?? []) as $assignment) {
            $assignment = self::to_array($assignment);
            foreach (($assignment['grades'] ?? []) as $grade) {
                $grades[] = self::grade_to_response($grade);
            }
        }

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'assignment_id' => (int) $cm->instance,
            'grades' => $grades,
        ];
    }

    /**
     * Register an assignment view event through Moodle's assignment external API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @param string $view Assignment view name.
     * @return array
     */
    public static function view_assignment(\stdClass $course, \cm_info $cm, string $view): array {
        self::require_assignment_api();

        if (!in_array($view, ['assignment', 'submission_status', 'grading_table'], true)) {
            throw new \invalid_parameter_exception('view must be one of: assignment, submission_status, grading_table.');
        }

        if ($view === 'assignment') {
            $result = \mod_assign_external::view_assign((int) $cm->instance);
        } else if ($view === 'submission_status') {
            $result = \mod_assign_external::view_submission_status((int) $cm->instance);
        } else {
            $result = \mod_assign_external::view_grading_table((int) $cm->instance);
        }

        self::fail_on_warnings($result['warnings'] ?? []);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'assignment_id' => (int) $cm->instance,
            'view' => $view,
            'viewed' => (bool) ($result['status'] ?? true),
        ];
    }

    /**
     * Return a canonical submission status response.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @param mixed $status Raw Moodle status payload.
     * @param int $requesteduserid Requested user id, or 0 for the current user.
     * @return array
     */
    public static function status_to_response(\stdClass $course, \cm_info $cm, $status, int $requesteduserid = 0): array {
        $status = self::to_array($status);
        $lastattempt = self::to_array($status['lastattempt'] ?? []);
        $submission = self::to_array($lastattempt['submission'] ?? []);
        $feedback = self::to_array($status['feedback'] ?? []);
        $gradeinfo = self::to_array($feedback['grade'] ?? []);
        $submissionstatus = (string) ($submission['status'] ?? $lastattempt['submissionstatus'] ?? '');
        $userid = (int) ($submission['userid'] ?? $lastattempt['userid'] ?? $requesteduserid);
        $onlinetext = self::extract_online_text($submission);
        $rawgrade = $gradeinfo['grade'] ?? null;

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'assignment_id' => (int) $cm->instance,
            'user_id' => $userid,
            'submission_id' => (int) ($submission['id'] ?? 0),
            'status' => $submissionstatus,
            'attempt_number' => (int) ($submission['attemptnumber'] ?? $lastattempt['attemptnumber'] ?? 0),
            'can_edit' => (bool) ($lastattempt['caneditowner'] ?? $lastattempt['canedit'] ?? false),
            'submitted' => $submissionstatus === 'submitted',
            'online_text' => $onlinetext,
            'graded' => !empty($gradeinfo) && $rawgrade !== null && (float) $rawgrade >= 0,
            'grade' => $rawgrade === null ? 0.0 : (float) $rawgrade,
            'grader_id' => (int) ($gradeinfo['grader'] ?? 0),
            'grading_status' => (string) ($feedback['gradingstatus'] ?? $lastattempt['gradingstatus'] ?? ''),
            'feedback_comment' => self::extract_feedback_comment($feedback),
        ];
    }

    /**
     * Convert a Moodle assignment submission to the canonical response shape.
     *
     * @param mixed $submission Moodle submission payload.
     * @return array
     */
    private static function submission_to_response($submission): array {
        $submission = self::to_array($submission);

        return [
            'submission_id' => (int) ($submission['id'] ?? 0),
            'assignment_id' => (int) ($submission['assignment'] ?? 0),
            'user_id' => (int) ($submission['userid'] ?? 0),
            'status' => (string) ($submission['status'] ?? ''),
            'attempt_number' => (int) ($submission['attemptnumber'] ?? 0),
            'group_id' => (int) ($submission['groupid'] ?? 0),
            'created' => (int) ($submission['timecreated'] ?? 0),
            'modified' => (int) ($submission['timemodified'] ?? 0),
            'started' => (int) ($submission['timestarted'] ?? 0),
            'grading_status' => (string) ($submission['gradingstatus'] ?? ''),
            'online_text' => self::extract_online_text($submission),
        ];
    }

    /**
     * Convert a Moodle assignment grade to the canonical response shape.
     *
     * @param mixed $grade Moodle grade payload.
     * @return array
     */
    private static function grade_to_response($grade): array {
        $grade = self::to_array($grade);

        return [
            'grade_id' => (int) ($grade['id'] ?? 0),
            'assignment_id' => (int) ($grade['assignment'] ?? 0),
            'user_id' => (int) ($grade['userid'] ?? 0),
            'attempt_number' => (int) ($grade['attemptnumber'] ?? 0),
            'created' => (int) ($grade['timecreated'] ?? 0),
            'modified' => (int) ($grade['timemodified'] ?? 0),
            'grader_id' => (int) ($grade['grader'] ?? 0),
            'grade' => (float) ($grade['grade'] ?? 0),
            'grade_formatted' => (string) ($grade['gradefordisplay'] ?? ''),
        ];
    }

    /**
     * Convert a Moodle assignment module to the canonical response shape.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Assignment course module.
     * @return array
     */
    private static function assignment_summary_to_response(\stdClass $course, \cm_info $cm): array {
        $context = \context_module::instance($cm->id);
        $assignment = new \assign($context, $cm, $course);
        $instance = $assignment->get_instance();

        return [
            'assignment_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'course_id' => (int) $course->id,
            'name' => format_string($cm->name, true, ['context' => $context]),
            'intro' => (string) ($instance->intro ?? ''),
            'intro_format' => (int) ($instance->introformat ?? FORMAT_HTML),
            'activity' => (string) ($instance->activity ?? ''),
            'activity_format' => (int) ($instance->activityformat ?? FORMAT_HTML),
            'allowsubmissionsfromdate' => (int) ($instance->allowsubmissionsfromdate ?? 0),
            'duedate' => (int) ($instance->duedate ?? 0),
            'cutoffdate' => (int) ($instance->cutoffdate ?? 0),
            'gradingduedate' => (int) ($instance->gradingduedate ?? 0),
            'grade' => (float) ($instance->grade ?? 0),
            'teamsubmission' => (bool) ($instance->teamsubmission ?? false),
            'requireallteammemberssubmit' => (bool) ($instance->requireallteammemberssubmit ?? false),
            'teamsubmissiongroupingid' => (int) ($instance->teamsubmissiongroupingid ?? 0),
            'blindmarking' => (bool) ($instance->blindmarking ?? false),
            'hidegrader' => (bool) ($instance->hidegrader ?? false),
            'markingworkflow' => (bool) ($instance->markingworkflow ?? false),
            'markingallocation' => (bool) ($instance->markingallocation ?? false),
            'requiresubmissionstatement' => (bool) ($instance->requiresubmissionstatement ?? false),
            'submissiondrafts' => (bool) ($instance->submissiondrafts ?? false),
            'maxattempts' => (int) ($instance->maxattempts ?? 1),
            'attemptreopenmethod' => (string) ($instance->attemptreopenmethod ?? ''),
            'submissionattachments' => (bool) ($instance->submissionattachments ?? false),
            'sendnotifications' => (bool) ($instance->sendnotifications ?? false),
            'sendlatenotifications' => (bool) ($instance->sendlatenotifications ?? false),
            'sendstudentnotifications' => (bool) ($instance->sendstudentnotifications ?? false),
            'submission_plugins' => self::plugin_names($assignment->get_submission_plugins()),
            'feedback_plugins' => self::plugin_names($assignment->get_feedback_plugins()),
            'visible' => (bool) $cm->visible,
            'url' => $cm->url ? $cm->url->out(false) : '',
        ];
    }

    /**
     * Throw when Moodle returns external API warnings.
     *
     * @param array $warnings Moodle warning payloads.
     */
    public static function fail_on_warnings(array $warnings): void {
        if (empty($warnings)) {
            return;
        }

        $warning = self::to_array(reset($warnings));
        $message = (string) ($warning['message'] ?? $warning['warningcode'] ?? 'Moodle assignment operation returned warnings.');
        throw new \moodle_exception('error', 'local_moodlia', '', null, $message);
    }

    /**
     * Convert objects and nested arrays to arrays.
     *
     * @param mixed $value Value to convert.
     * @return array
     */
    private static function to_array($value): array {
        if (is_array($value)) {
            return array_map(static function($item) {
                return is_object($item) || is_array($item) ? self::to_array($item) : $item;
            }, $value);
        }

        if (is_object($value)) {
            return self::to_array(get_object_vars($value));
        }

        return [];
    }

    /**
     * Return enabled plugin names from Moodle assignment plugin objects.
     *
     * @param array $plugins Assignment plugin objects.
     * @return array
     */
    private static function plugin_names(array $plugins): array {
        $names = [];
        foreach ($plugins as $plugin) {
            if (!method_exists($plugin, 'is_enabled') || !$plugin->is_enabled()) {
                continue;
            }
            if (method_exists($plugin, 'get_type')) {
                $names[] = (string) $plugin->get_type();
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Extract the online text submission field from Moodle plugin data.
     *
     * @param array $submission Submission payload.
     * @return string
     */
    private static function extract_online_text(array $submission): string {
        foreach (($submission['plugins'] ?? []) as $plugin) {
            $plugin = self::to_array($plugin);
            if (($plugin['type'] ?? '') !== 'onlinetext') {
                continue;
            }

            foreach (($plugin['editorfields'] ?? []) as $field) {
                $field = self::to_array($field);
                if (($field['name'] ?? '') === 'onlinetext') {
                    return (string) ($field['text'] ?? '');
                }
            }
        }

        return '';
    }

    /**
     * Extract the assignment feedback comment from Moodle plugin data.
     *
     * @param array $feedback Feedback payload.
     * @return string
     */
    private static function extract_feedback_comment(array $feedback): string {
        foreach (($feedback['plugins'] ?? []) as $plugin) {
            $plugin = self::to_array($plugin);
            if (($plugin['type'] ?? '') !== 'comments') {
                continue;
            }

            foreach (($plugin['editorfields'] ?? []) as $field) {
                $field = self::to_array($field);
                if (in_array(($field['name'] ?? ''), ['comments', 'assignfeedbackcomments'], true)) {
                    return (string) ($field['text'] ?? '');
                }
            }
        }

        return '';
    }
}
