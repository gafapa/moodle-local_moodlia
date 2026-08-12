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
 * Shared helpers for Moodle assignment module settings.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Handles module options for assignment activities.
 */
class module_assignment_tools {
    /**
     * Add assignment-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_assign_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->alwaysshowdescription = 1;
        if (!empty($options['activity'])) {
            $moduleinfo->activityeditor = [
                'text' => (string) $options['activity'],
                'format' => FORMAT_HTML,
            ];
        }
        $moduleinfo->submissionattachments = self::optional_bool($options, 'submission_attachments', 0);
        $moduleinfo->submissiondrafts = self::optional_bool($options, 'submission_drafts', 1);
        $moduleinfo->requiresubmissionstatement = self::optional_bool($options, 'require_submission_statement', 0);
        $moduleinfo->sendnotifications = self::optional_bool($options, 'send_notifications', 0);
        $moduleinfo->sendlatenotifications = self::optional_bool($options, 'send_late_notifications', 0);
        $moduleinfo->sendstudentnotifications = self::optional_bool($options, 'send_student_notifications', 0);
        $moduleinfo->allowsubmissionsfromdate = self::optional_int($options, 'allow_submissions_from_date', 0);
        $moduleinfo->duedate = self::optional_int($options, 'due_date', 0);
        $moduleinfo->cutoffdate = self::optional_int($options, 'cutoff_date', 0);
        $moduleinfo->gradingduedate = self::optional_int($options, 'grading_due_date', 0);
        self::validate_assign_dates($moduleinfo);
        $moduleinfo->grade = (float) ($options['grade'] ?? 100);
        $moduleinfo->completionsubmit = 0;
        $moduleinfo->teamsubmission = self::optional_bool($options, 'team_submission', 0);
        $moduleinfo->requireallteammemberssubmit = self::optional_bool($options, 'require_all_team_members_submit', 0);
        $moduleinfo->teamsubmissiongroupingid = self::optional_int($options, 'team_submission_grouping_id', 0);
        $moduleinfo->blindmarking = self::optional_bool($options, 'blind_marking', 0);
        $moduleinfo->hidegrader = self::optional_bool($options, 'hide_grader', 0);
        $moduleinfo->maxattempts = self::normalise_assign_max_attempts($options['max_attempts'] ?? 1);
        $moduleinfo->attemptreopenmethod = self::normalise_assign_reopen_method(
            (string) ($options['attempt_reopen_method'] ?? 'manual')
        );
        $moduleinfo->preventsubmissionnotingroup = self::optional_bool($options, 'prevent_submission_not_in_group', 0);
        $moduleinfo->markingworkflow = self::optional_bool($options, 'marking_workflow', 0);
        $moduleinfo->markingallocation = self::optional_bool($options, 'marking_allocation', 0);
        $moduleinfo->markinganonymous = self::optional_bool($options, 'marking_anonymous', 0);
        $moduleinfo->gradepenalty = self::optional_int($options, 'grade_penalty', 0);
        $moduleinfo->markercount = self::optional_int($options, 'marker_count', 1, 1);

        $moduleinfo->assignsubmission_onlinetext_enabled = array_key_exists('online_text', $options)
            ? (int) (bool) $options['online_text']
            : 1;
        $moduleinfo->assignsubmission_onlinetext_wordlimit = (int) ($options['word_limit'] ?? 0);
        $moduleinfo->assignsubmission_onlinetext_wordlimit_enabled = $moduleinfo->assignsubmission_onlinetext_wordlimit > 0 ? 1 : 0;
        $moduleinfo->assignsubmission_file_enabled = array_key_exists('file_submissions', $options)
            ? (int) (bool) $options['file_submissions']
            : 0;
        $moduleinfo->assignsubmission_file_maxfiles = (int) ($options['max_files'] ?? 1);
        $moduleinfo->assignsubmission_file_maxsizebytes = (int) ($options['max_file_size_bytes'] ?? 0);
        $moduleinfo->assignsubmission_file_filetypes = (string) ($options['file_types'] ?? '');

        $moduleinfo->assignfeedback_comments_enabled = self::optional_bool($options, 'feedback_comments', 1);
        $moduleinfo->assignfeedback_comments_commentinline = self::optional_bool($options, 'feedback_comment_inline', 0);
        $moduleinfo->assignfeedback_offline_enabled = self::optional_bool($options, 'feedback_offline', 0);
        $moduleinfo->assignfeedback_file_enabled = self::optional_bool($options, 'feedback_files', 0);
        $moduleinfo->assignfeedback_editpdf_enabled = self::optional_bool($options, 'feedback_editpdf', 0);
    }

    /**
     * Return an optional boolean module option as an integer.
     *
     * @param array $options Options.
     * @param string $name Name.
     * @param int $default Default.
     * @return int
     */
    private static function optional_bool(array $options, string $name, int $default): int {
        return array_key_exists($name, $options) ? (int) (bool) $options[$name] : $default;
    }

    /**
     * Return an optional positive integer module option.
     *
     * @param array $options Options.
     * @param string $name Name.
     * @param int $default Default.
     * @param int $minimum Minimum.
     * @return int
     */
    private static function optional_int(array $options, string $name, int $default, int $minimum = 0): int {
        $value = array_key_exists($name, $options) ? (int) $options[$name] : $default;
        if ($value < $minimum) {
            throw new \invalid_parameter_exception("options.$name must be at least $minimum.");
        }

        return $value;
    }

    /**
     * Validate assignment date relationships.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     */
    private static function validate_assign_dates(\stdClass $moduleinfo): void {
        if ($moduleinfo->allowsubmissionsfromdate > 0 && $moduleinfo->duedate > 0
            && $moduleinfo->duedate < $moduleinfo->allowsubmissionsfromdate) {
            throw new \invalid_parameter_exception('options.due_date must be greater than options.allow_submissions_from_date.');
        }
        if ($moduleinfo->duedate > 0 && $moduleinfo->cutoffdate > 0 && $moduleinfo->cutoffdate < $moduleinfo->duedate) {
            throw new \invalid_parameter_exception('options.cutoff_date must be greater than options.due_date.');
        }
    }

    /**
     * Validate assignment max attempt settings.
     *
     * @param mixed $value Value.
     * @return int
     */
    private static function normalise_assign_max_attempts($value): int {
        $attempts = (int) $value;
        if ($attempts < -1 || $attempts === 0) {
            throw new \invalid_parameter_exception('options.max_attempts must be -1 for unlimited or a positive integer.');
        }

        return $attempts;
    }

    /**
     * Map public assignment attempt reopen values.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_assign_reopen_method(string $value): string {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'manual' => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
            'automatic' => ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS,
            'untilpass' => ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS,
            'none' => defined('ASSIGN_ATTEMPT_REOPEN_METHOD_NONE') ? ASSIGN_ATTEMPT_REOPEN_METHOD_NONE : 'none',
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.attempt_reopen_method must be one of: manual, automatic, none.');
        }

        return $map[$key];
    }
}
