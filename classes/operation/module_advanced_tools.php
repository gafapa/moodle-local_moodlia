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
 * Shared helpers for advanced Moodle module settings.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Handles module options for lesson, workshop, and LTI activities.
 */
class module_advanced_tools {
    /**
     * Add lesson-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_lesson_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->practice = self::optional_bool($options, 'practice', 0);
        $moduleinfo->modattempts = self::optional_bool($options, 'allow_review', 0);
        $moduleinfo->ongoing = self::optional_bool($options, 'ongoing_score', 0);
        $moduleinfo->progressbar = self::optional_bool($options, 'progress_bar', 0);
        $moduleinfo->displayleft = self::optional_bool($options, 'display_left_menu', 0);
        $moduleinfo->displayleftif = self::optional_int_range($options, 'display_left_if', 0, 0, 100);
        $moduleinfo->slideshow = self::optional_bool($options, 'slideshow', 0);
        $moduleinfo->maxanswers = self::optional_int_range($options, 'max_answers', 4, 2, 20);
        $moduleinfo->feedback = self::optional_bool($options, 'default_feedback', 1);
        $moduleinfo->available = self::optional_int($options, 'available_from', 0);
        $moduleinfo->deadline = self::optional_int($options, 'deadline', 0);
        if ($moduleinfo->available > 0 && $moduleinfo->deadline > 0 && $moduleinfo->deadline < $moduleinfo->available) {
            throw new \invalid_parameter_exception('options.deadline must be greater than options.available_from.');
        }
        $moduleinfo->timelimit = self::optional_int($options, 'time_limit_seconds', 0);
        $moduleinfo->usepassword = self::optional_bool($options, 'use_password', 0);
        $moduleinfo->password = (string) ($options['password'] ?? '');
        if ($moduleinfo->usepassword && $moduleinfo->password === '') {
            throw new \invalid_parameter_exception('options.password is required when options.use_password is true.');
        }
        if (strlen($moduleinfo->password) > 32) {
            throw new \invalid_parameter_exception('options.password must be 32 characters or fewer.');
        }
        $moduleinfo->modattempts = self::optional_bool($options, 'allow_review', $moduleinfo->modattempts);
        $moduleinfo->review = self::optional_bool($options, 'allow_question_retry', 0);
        $moduleinfo->maxattempts = self::optional_int_range($options, 'max_attempts', 5, 0, 10);
        $moduleinfo->nextpagedefault = self::normalise_lesson_next_page((string) ($options['after_correct_answer'] ?? 'normal'));
        $moduleinfo->maxpages = self::optional_int_range($options, 'pages_to_show', 0, 0, 100);
        $moduleinfo->grade = self::optional_int_range($options, 'grade', 100, 0, 100);
        $moduleinfo->custom = self::optional_bool($options, 'custom_scoring', 0);
        $moduleinfo->retake = self::optional_bool($options, 'retakes_allowed', 1);
        $moduleinfo->usemaxgrade = self::optional_bool($options, 'use_max_grade', 0);
        $moduleinfo->minquestions = self::optional_int_range($options, 'minimum_questions', 0, 0, 100);
        $moduleinfo->activitylink = self::optional_int($options, 'activity_link', 0);
        $moduleinfo->allowofflineattempts = self::optional_bool($options, 'allow_offline_attempts', 0);
        $moduleinfo->completionendreached = self::optional_bool($options, 'completion_end_reached', 1);
        $moduleinfo->completiontimespent = self::optional_int($options, 'completion_time_spent_seconds', 0);
        $moduleinfo->dependency = 0;
        $moduleinfo->timespent = 0;
        $moduleinfo->completed = 0;
        $moduleinfo->gradebetterthan = 0;
        $moduleinfo->mediafile = 0;
        $moduleinfo->mediaheight = self::optional_int_range($options, 'media_height', 100, 1, 2000);
        $moduleinfo->mediawidth = self::optional_int_range($options, 'media_width', 650, 1, 2000);
        $moduleinfo->mediaclose = self::optional_bool($options, 'media_close_button', 0);
        $moduleinfo->width = self::optional_int_range($options, 'slideshow_width', 640, 1, 2000);
        $moduleinfo->height = self::optional_int_range($options, 'slideshow_height', 480, 1, 2000);
        $moduleinfo->bgcolor = self::normalise_hex_colour((string) ($options['slideshow_background'] ?? '#FFFFFF'));
    }

    /**
     * Add workshop-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_workshop_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->strategy = self::normalise_workshop_strategy((string) ($options['strategy'] ?? 'accumulative'));
        $moduleinfo->grade = self::optional_number_range($options, 'submission_grade', 80, 0, 100);
        $moduleinfo->gradinggrade = self::optional_number_range($options, 'assessment_grade', 20, 0, 100);
        $moduleinfo->gradedecimals = self::optional_int_range($options, 'grade_decimals', 0, 0, 5);

        $authorinstructions = (string) ($options['submission_instructions'] ?? '');
        $reviewerinstructions = (string) ($options['assessment_instructions'] ?? '');
        $conclusion = (string) ($options['conclusion'] ?? '');
        $moduleinfo->instructauthors = $authorinstructions;
        $moduleinfo->instructauthorsformat = FORMAT_HTML;
        $moduleinfo->instructauthorseditor = [
            'text' => $authorinstructions,
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $moduleinfo->instructreviewers = $reviewerinstructions;
        $moduleinfo->instructreviewersformat = FORMAT_HTML;
        $moduleinfo->instructreviewerseditor = [
            'text' => $reviewerinstructions,
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $moduleinfo->conclusion = $conclusion;
        $moduleinfo->conclusionformat = FORMAT_HTML;
        $moduleinfo->conclusioneditor = [
            'text' => $conclusion,
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];

        $moduleinfo->submissiontypetext = self::normalise_workshop_submission_type(
            (string) ($options['text_submission'] ?? 'available'),
            'text_submission'
        );
        $moduleinfo->submissiontypefile = self::normalise_workshop_submission_type(
            (string) ($options['file_submission'] ?? 'available'),
            'file_submission'
        );
        if ($moduleinfo->submissiontypetext === WORKSHOP_SUBMISSION_TYPE_DISABLED
            && $moduleinfo->submissiontypefile === WORKSHOP_SUBMISSION_TYPE_DISABLED) {
            throw new \invalid_parameter_exception('At least one of options.text_submission or options.file_submission must be available.');
        }

        $moduleinfo->nattachments = self::optional_int_range($options, 'max_submission_attachments', 1, 1, 7);
        $moduleinfo->submissionfiletypes = clean_param((string) ($options['submission_file_types'] ?? ''), PARAM_RAW_TRIMMED);
        $moduleinfo->maxbytes = self::optional_int($options, 'max_file_size', 0);
        $moduleinfo->latesubmissions = self::optional_bool($options, 'late_submissions', 0);

        $moduleinfo->useselfassessment = self::optional_bool($options, 'self_assessment', 0);
        $moduleinfo->useexamples = self::optional_bool($options, 'example_submissions', 0);
        $moduleinfo->examplesmode = self::normalise_workshop_examples_mode((string) ($options['examples_mode'] ?? 'voluntary'));

        $moduleinfo->submissionstart = self::optional_int($options, 'submission_start', 0);
        $moduleinfo->submissionend = self::optional_int($options, 'submission_end', 0);
        if ($moduleinfo->submissionstart > 0 && $moduleinfo->submissionend > 0
            && $moduleinfo->submissionend <= $moduleinfo->submissionstart) {
            throw new \invalid_parameter_exception('options.submission_end must be greater than options.submission_start.');
        }
        $moduleinfo->assessmentstart = self::optional_int($options, 'assessment_start', 0);
        $moduleinfo->assessmentend = self::optional_int($options, 'assessment_end', 0);
        if ($moduleinfo->assessmentstart > 0 && $moduleinfo->assessmentend > 0
            && $moduleinfo->assessmentend <= $moduleinfo->assessmentstart) {
            throw new \invalid_parameter_exception('options.assessment_end must be greater than options.assessment_start.');
        }
        if (max($moduleinfo->submissionstart, $moduleinfo->submissionend) > 0
            && max($moduleinfo->assessmentstart, $moduleinfo->assessmentend) > 0) {
            $submissionend = max($moduleinfo->submissionstart, $moduleinfo->submissionend);
            $assessmentstart = min($moduleinfo->assessmentstart, $moduleinfo->assessmentend);
            if ($assessmentstart === 0) {
                $assessmentstart = max($moduleinfo->assessmentstart, $moduleinfo->assessmentend);
            }
            if ($assessmentstart > 0 && $assessmentstart < $submissionend) {
                throw new \invalid_parameter_exception('Workshop submission and assessment windows must not overlap.');
            }
        }
        $moduleinfo->phaseswitchassessment = self::optional_bool($options, 'switch_to_assessment_after_submission_deadline', 0);

        $moduleinfo->overallfeedbackmode = self::optional_int_range($options, 'overall_feedback_mode', 1, 0, 2);
        $moduleinfo->overallfeedbackfiles = self::optional_int_range($options, 'overall_feedback_files', 0, 0, 7);
        $moduleinfo->overallfeedbackfiletypes = clean_param((string) ($options['overall_feedback_file_types'] ?? ''), PARAM_RAW_TRIMMED);
        $moduleinfo->overallfeedbackmaxbytes = self::optional_int($options, 'overall_feedback_max_file_size', 0);
    }

    /**
     * Add LTI-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_lti_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        if (isset($moduleinfo->introeditor) && is_array($moduleinfo->introeditor)) {
            $moduleinfo->introeditor['text'] = $moduleinfo->intro;
            $moduleinfo->introeditor['format'] = FORMAT_HTML;
        }
        $moduleinfo->typeid = self::optional_int($options, 'type_id', 0);
        $moduleinfo->urlmatchedtypeid = 0;
        $moduleinfo->toolurl = self::normalise_absolute_http_url(
            $options['tool_url'] ?? $options['toolurl'] ?? '',
            'tool_url',
            true
        );
        $moduleinfo->securetoolurl = self::normalise_absolute_http_url(
            $options['secure_tool_url'] ?? $options['securetoolurl'] ?? '',
            'secure_tool_url',
            false
        );

        $acceptgrades = (bool) ($options['accept_grades'] ?? $options['acceptgrades'] ?? false);
        $moduleinfo->instructorchoicesendname = self::normalise_lti_privacy_setting($options, 'send_name', 'sendname', false);
        $moduleinfo->instructorchoicesendemailaddr = self::normalise_lti_privacy_setting($options, 'send_email', 'sendemail', false);
        $moduleinfo->instructorchoiceallowroster = self::normalise_lti_privacy_setting($options, 'allow_roster', 'allowroster', false);
        $moduleinfo->instructorchoiceallowsetting = self::normalise_lti_privacy_setting($options, 'allow_setting', 'allowsetting', false);
        $moduleinfo->instructorchoiceacceptgrades = self::lti_setting_from_bool($acceptgrades);
        $moduleinfo->grade = $acceptgrades ? self::optional_number_range($options, 'grade', 100, 0, 100) : 0;
        $moduleinfo->launchcontainer = self::normalise_lti_launch_container((string) ($options['launch_container'] ?? 'default'));

        $customparameters = (string) ($options['custom_parameters'] ?? $options['customparameters'] ?? '');
        if (strlen($customparameters) > 10000) {
            throw new \invalid_parameter_exception('options.custom_parameters exceeds the maximum allowed length.');
        }
        $moduleinfo->instructorcustomparameters = $customparameters;

        $moduleinfo->resourcekey = clean_param((string) ($options['resource_key'] ?? $options['resourcekey'] ?? ''), PARAM_RAW_TRIMMED);
        $moduleinfo->password = clean_param((string) ($options['shared_secret'] ?? $options['password'] ?? ''), PARAM_RAW_TRIMMED);
        $moduleinfo->debuglaunch = self::optional_bool($options, 'debug_launch', 0);
        $moduleinfo->showtitlelaunch = self::optional_bool($options, 'show_title_launch', 0);
        $moduleinfo->showdescriptionlaunch = self::optional_bool($options, 'show_description_launch', 0);
        $moduleinfo->icon = self::normalise_absolute_http_url($options['icon'] ?? '', 'icon', false);
        $moduleinfo->secureicon = self::normalise_absolute_http_url($options['secure_icon'] ?? $options['secureicon'] ?? '', 'secure_icon', false);
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
     * Return an optional integer constrained to a public range.
     *
     * @param array $options Options.
     * @param string $name Name.
     * @param int $default Default.
     * @param int $minimum Minimum.
     * @param int $maximum Maximum.
     * @return int
     */
    private static function optional_int_range(array $options, string $name, int $default, int $minimum, int $maximum): int {
        $value = self::optional_int($options, $name, $default, $minimum);
        if ($value > $maximum) {
            throw new \invalid_parameter_exception("options.$name must be at most $maximum.");
        }

        return $value;
    }

    /**
     * Return an optional numeric module option constrained to a public range.
     *
     * @param array $options Options.
     * @param string $name Name.
     * @param float $default Default.
     * @param float $minimum Minimum.
     * @param float $maximum Maximum.
     * @return float
     */
    private static function optional_number_range(
        array $options,
        string $name,
        float $default,
        float $minimum,
        float $maximum
    ): float {
        $value = array_key_exists($name, $options) ? (float) $options[$name] : $default;
        if ($value < $minimum || $value > $maximum) {
            throw new \invalid_parameter_exception("options.$name must be between $minimum and $maximum.");
        }

        return $value;
    }

    /**
     * Return a validated absolute HTTP(S) URL.
     *
     * @param mixed $value Value.
     * @param string $option Option.
     * @param bool $required Required.
     * @return string
     */
    private static function normalise_absolute_http_url($value, string $option, bool $required): string {
        $url = trim((string) $value);
        if ($url === '') {
            if ($required) {
                throw new \invalid_parameter_exception("options.$option is required.");
            }
            return '';
        }

        $url = clean_param($url, PARAM_URL);
        if (!preg_match('#^https?://[^\s]+$#i', $url)) {
            throw new \invalid_parameter_exception("options.$option must be an absolute http or https URL.");
        }

        return $url;
    }

    /**
     * Map public lesson navigation values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_lesson_next_page(string $value): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'normal' => 0,
            'unseen' => defined('LESSON_UNSEENPAGE') ? LESSON_UNSEENPAGE : 1,
            'unseen_page' => defined('LESSON_UNSEENPAGE') ? LESSON_UNSEENPAGE : 1,
            'unanswered' => defined('LESSON_UNANSWEREDPAGE') ? LESSON_UNANSWEREDPAGE : 2,
            'unanswered_page' => defined('LESSON_UNANSWEREDPAGE') ? LESSON_UNANSWEREDPAGE : 2,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.after_correct_answer must be one of: normal, unseen_page, unanswered_page.');
        }

        return $map[$key];
    }

    /**
     * Validate a public HTML hex colour.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_hex_colour(string $value): string {
        $colour = trim($value);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colour)) {
            throw new \invalid_parameter_exception('options.slideshow_background must be a #RRGGBB colour.');
        }

        return strtoupper($colour);
    }

    /**
     * Map a public workshop grading strategy value to an installed strategy.
     *
     * @param string $value Value.
     * @return string
     */
    private static function normalise_workshop_strategy(string $value): string {
        $strategy = clean_param($value, PARAM_PLUGIN);
        if ($strategy === '') {
            $strategy = 'accumulative';
        }
        $strategies = \workshop::available_strategies_list();
        if (!array_key_exists($strategy, $strategies)) {
            throw new \invalid_parameter_exception(
                'options.strategy must reference an installed workshop grading strategy.'
            );
        }

        return $strategy;
    }

    /**
     * Map public workshop submission type values to Moodle constants.
     *
     * @param string $value Value.
     * @param string $option Option.
     * @return int
     */
    private static function normalise_workshop_submission_type(string $value, string $option): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'disabled' => WORKSHOP_SUBMISSION_TYPE_DISABLED,
            'none' => WORKSHOP_SUBMISSION_TYPE_DISABLED,
            'available' => WORKSHOP_SUBMISSION_TYPE_AVAILABLE,
            'optional' => WORKSHOP_SUBMISSION_TYPE_AVAILABLE,
            'required' => WORKSHOP_SUBMISSION_TYPE_REQUIRED,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception("options.$option must be one of: disabled, available, required.");
        }

        return $map[$key];
    }

    /**
     * Map public workshop example-mode values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_workshop_examples_mode(string $value): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'voluntary' => \workshop::EXAMPLES_VOLUNTARY,
            'before_submission' => \workshop::EXAMPLES_BEFORE_SUBMISSION,
            'beforesubmission' => \workshop::EXAMPLES_BEFORE_SUBMISSION,
            'before_assessment' => \workshop::EXAMPLES_BEFORE_ASSESSMENT,
            'beforeassessment' => \workshop::EXAMPLES_BEFORE_ASSESSMENT,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception(
                'options.examples_mode must be one of: voluntary, before_submission, before_assessment.'
            );
        }

        return $map[$key];
    }

    /**
     * Map a public LTI privacy boolean to Moodle's setting values.
     *
     * @param array $options Options.
     * @param string $preferred Preferred.
     * @param string $legacy Legacy.
     * @param bool $default Default.
     * @return int
     */
    private static function normalise_lti_privacy_setting(
        array $options,
        string $preferred,
        string $legacy,
        bool $default
    ): int {
        $value = $options[$preferred] ?? $options[$legacy] ?? $default;

        return self::lti_setting_from_bool((bool) $value);
    }

    /**
     * Return Moodle's LTI setting value for a public boolean.
     *
     * @param bool $enabled Enabled.
     * @return int
     */
    private static function lti_setting_from_bool(bool $enabled): int {
        if ($enabled) {
            return defined('LTI_SETTING_ALWAYS') ? LTI_SETTING_ALWAYS : 1;
        }

        return defined('LTI_SETTING_NEVER') ? LTI_SETTING_NEVER : 0;
    }

    /**
     * Map a public LTI launch container value to Moodle constants.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_lti_launch_container(string $value): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'default' => defined('LTI_LAUNCH_CONTAINER_DEFAULT') ? LTI_LAUNCH_CONTAINER_DEFAULT : 1,
            'embed' => defined('LTI_LAUNCH_CONTAINER_EMBED') ? LTI_LAUNCH_CONTAINER_EMBED : 2,
            'embed_no_blocks' => defined('LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS') ? LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS : 3,
            'embednoblocks' => defined('LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS') ? LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS : 3,
            'new_window' => defined('LTI_LAUNCH_CONTAINER_WINDOW') ? LTI_LAUNCH_CONTAINER_WINDOW : 4,
            'newwindow' => defined('LTI_LAUNCH_CONTAINER_WINDOW') ? LTI_LAUNCH_CONTAINER_WINDOW : 4,
            'existing_window' => defined('LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW') ? LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW : 5,
            'existingwindow' => defined('LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW') ? LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW : 5,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception(
                'options.launch_container must be one of: default, embed, embed_no_blocks, new_window, existing_window.'
            );
        }

        return $map[$key];
    }
}
