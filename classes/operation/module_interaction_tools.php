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
 * Shared helpers for interactive Moodle module settings.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Handles module options for interaction-oriented activities.
 */
class module_interaction_tools {
    /**
     * Add choice-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_choice_options(\stdClass $moduleinfo, array $options): void {
        $rawoptions = $options['choices'] ?? $options['options'] ?? [];
        if (is_string($rawoptions)) {
            $decoded = json_decode($rawoptions, true);
            if (is_array($decoded)) {
                $rawoptions = $decoded;
            }
        }
        if (!is_array($rawoptions) || count($rawoptions) < 2) {
            throw new \invalid_parameter_exception('options.choices must contain at least two choice labels.');
        }

        $labels = [];
        foreach ($rawoptions as $option) {
            $label = trim((string) $option);
            if ($label !== '') {
                $labels[] = $label;
            }
        }
        if (count($labels) < 2) {
            throw new \invalid_parameter_exception('options.choices must contain at least two non-empty choice labels.');
        }

        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->option = $labels;
        $limits = $options['limits'] ?? [];
        if (is_string($limits)) {
            $decodedlimits = json_decode($limits, true);
            if (is_array($decodedlimits)) {
                $limits = $decodedlimits;
            }
        }
        if ($limits !== [] && !is_array($limits)) {
            throw new \invalid_parameter_exception('options.limits must be an array of non-negative integers.');
        }

        $moduleinfo->limit = [];
        foreach ($labels as $index => $unused) {
            $limit = (int) ($limits[$index] ?? 0);
            if ($limit < 0) {
                throw new \invalid_parameter_exception('options.limits must contain non-negative integers.');
            }
            $moduleinfo->limit[] = $limit;
        }
        $moduleinfo->allowupdate = array_key_exists('allow_update', $options) ? (int) (bool) $options['allow_update'] : 1;
        $moduleinfo->allowmultiple = array_key_exists('allow_multiple', $options) ? (int) (bool) $options['allow_multiple'] : 0;
        $moduleinfo->showpreview = self::optional_bool($options, 'show_preview', 0);
        $moduleinfo->limitanswers = self::optional_bool($options, 'limit_answers', 0);
        $moduleinfo->showavailable = self::optional_bool($options, 'show_available', $moduleinfo->limitanswers ? 1 : 0);
        $moduleinfo->showresults = self::normalise_choice_show_results((string) ($options['show_results'] ?? 'always'));
        $moduleinfo->publish = self::normalise_choice_publish((string) ($options['publish'] ?? 'anonymous'));
        $moduleinfo->display = self::normalise_choice_display((string) ($options['display'] ?? 'vertical'));
        $moduleinfo->includeinactive = self::optional_bool($options, 'include_inactive', 0);
        $moduleinfo->showunanswered = self::optional_bool($options, 'show_unanswered', 1);
        $moduleinfo->timeopen = self::optional_int($options, 'time_open', 0);
        $moduleinfo->timeclose = self::optional_int($options, 'time_close', 0);
        $moduleinfo->completion = 0;
        $moduleinfo->completionsubmit = 0;
    }

    /**
     * Add feedback-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_feedback_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->timeopen = self::optional_int($options, 'time_open', 0);
        $moduleinfo->timeclose = self::optional_int($options, 'time_close', 0);
        if ($moduleinfo->timeopen > 0 && $moduleinfo->timeclose > 0 && $moduleinfo->timeclose <= $moduleinfo->timeopen) {
            throw new \invalid_parameter_exception('options.time_close must be greater than options.time_open.');
        }

        $moduleinfo->anonymous = self::normalise_feedback_anonymous((string) ($options['anonymous'] ?? 'anonymous'));
        $moduleinfo->multiple_submit = self::optional_bool($options, 'multiple_submit', 0);
        $moduleinfo->email_notification = self::optional_bool($options, 'email_notification', 0);
        $moduleinfo->autonumbering = self::optional_bool($options, 'autonumbering', 1);
        $moduleinfo->publish_stats = self::optional_bool($options, 'publish_stats', 0);
        $pageaftersubmit = (string) ($options['page_after_submit'] ?? '');
        $moduleinfo->page_after_submit = $pageaftersubmit;
        $moduleinfo->page_after_submitformat = FORMAT_HTML;
        $moduleinfo->page_after_submit_editor = [
            'text' => $pageaftersubmit,
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $siteaftersubmit = trim((string) ($options['site_after_submit'] ?? ''));
        if ($siteaftersubmit !== '' && !preg_match('/^https?:\/\/[^\s]+$/i', $siteaftersubmit)) {
            throw new \invalid_parameter_exception('options.site_after_submit must be an absolute http or https URL.');
        }
        $moduleinfo->site_after_submit = $siteaftersubmit;
        $moduleinfo->completionsubmit = self::optional_bool($options, 'completion_submit', 0);
    }

    /**
     * Add database-activity-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_data_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->comments = self::optional_bool($options, 'comments', 0);
        $moduleinfo->approval = self::optional_bool($options, 'approval_required', 0);
        $moduleinfo->manageapproved = self::optional_bool($options, 'manage_approved', 1);
        $moduleinfo->requiredentries = self::optional_int_range($options, 'required_entries', 0, 0, 1000);
        $moduleinfo->requiredentriestoview = self::optional_int_range($options, 'required_entries_to_view', 0, 0, 1000);
        $moduleinfo->maxentries = self::optional_int_range($options, 'max_entries', 0, 0, 1000);
        $moduleinfo->rssarticles = self::optional_int_range($options, 'rss_articles', 0, 0, 1000);
        $moduleinfo->timeavailablefrom = self::optional_int($options, 'available_from', 0);
        $moduleinfo->timeavailableto = self::optional_int($options, 'available_to', 0);
        if (
            $moduleinfo->timeavailablefrom > 0 && $moduleinfo->timeavailableto > 0
            && $moduleinfo->timeavailableto < $moduleinfo->timeavailablefrom
        ) {
            throw new \invalid_parameter_exception('options.available_to must be greater than options.available_from.');
        }
        $moduleinfo->timeviewfrom = self::optional_int($options, 'view_from', 0);
        $moduleinfo->timeviewto = self::optional_int($options, 'view_to', 0);
        if (
            $moduleinfo->timeviewfrom > 0 && $moduleinfo->timeviewto > 0
            && $moduleinfo->timeviewto < $moduleinfo->timeviewfrom
        ) {
            throw new \invalid_parameter_exception('options.view_to must be greater than options.view_from.');
        }
        $moduleinfo->scale = 0;
        $moduleinfo->assessed = 0;
        $moduleinfo->ratingtime = 0;
        $moduleinfo->assesstimestart = 0;
        $moduleinfo->assesstimefinish = 0;
        $moduleinfo->defaultsort = self::optional_int($options, 'default_sort_field_id', 0);
        $moduleinfo->defaultsortdir = self::normalise_data_sort_direction(
            (string) ($options['default_sort_direction'] ?? 'ascending')
        );
        $moduleinfo->editany = self::optional_bool($options, 'edit_any', 0);
        $moduleinfo->notification = self::optional_int($options, 'notification', 0);
        $moduleinfo->completionentries = self::optional_int_range($options, 'completion_entries', 0, 0, 1000);
    }

    /**
     * Add forum-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_forum_options(\stdClass $moduleinfo, array $options): void {
        $forumtype = clean_param((string) ($options['forum_type'] ?? $options['type'] ?? 'general'), PARAM_ALPHA);
        $allowedtypes = ['general', 'eachuser', 'qanda', 'single', 'blog'];
        if (!in_array($forumtype, $allowedtypes, true)) {
            throw new \invalid_parameter_exception('options.forum_type must be one of: general, eachuser, qanda, single, blog.');
        }

        $moduleinfo->type = $forumtype;
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->assessed = 0;
        $moduleinfo->scale = 100;
        $moduleinfo->grade_forum = 0;
        $moduleinfo->grade_forum_notify = 0;
        $moduleinfo->maxbytes = self::optional_int($options, 'max_bytes', 0);
        $moduleinfo->maxattachments = self::optional_int($options, 'max_attachments', 9);
        $moduleinfo->forcesubscribe = self::normalise_forum_subscription(
            (string) ($options['subscription_mode'] ?? $options['forcesubscribe'] ?? 'optional')
        );
        $moduleinfo->trackingtype = self::normalise_forum_tracking(
            (string) ($options['tracking_type'] ?? $options['trackingtype'] ?? 'optional')
        );
        $moduleinfo->rsstype = 0;
        $moduleinfo->rssarticles = 0;
        $moduleinfo->warnafter = self::optional_int($options, 'warn_after_posts', 0);
        $moduleinfo->blockafter = self::optional_int($options, 'block_after_posts', 0);
        $moduleinfo->blockperiod = self::optional_int($options, 'block_period_seconds', 0);
        self::validate_forum_blocking($moduleinfo);
        $moduleinfo->completiondiscussions = self::optional_int_range($options, 'completion_discussions', 0, 0, 1000);
        $moduleinfo->completionreplies = self::optional_int_range($options, 'completion_replies', 0, 0, 1000);
        $moduleinfo->completionposts = self::optional_int_range($options, 'completion_posts', 0, 0, 1000);
        $moduleinfo->displaywordcount = self::optional_bool($options, 'display_word_count', 0);
        $moduleinfo->lockdiscussionafter = self::optional_int($options, 'lock_discussion_after_seconds', 0);
        $moduleinfo->duedate = self::optional_int($options, 'due_date', 0);
        $moduleinfo->cutoffdate = self::optional_int($options, 'cutoff_date', 0);
        self::validate_forum_dates($moduleinfo);
    }

    /**
     * Add glossary-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_glossary_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;

        $displayformat = clean_param((string) ($options['display_format'] ?? $options['displayformat'] ?? 'dictionary'), PARAM_PLUGIN);
        $formats = get_list_of_plugins('mod/glossary/formats', 'TEMPLATE');
        if (!in_array($displayformat, $formats, true)) {
            throw new \invalid_parameter_exception('options.display_format must reference an installed glossary display format.');
        }

        $moduleinfo->globalglossary = 0;
        $moduleinfo->mainglossary = array_key_exists('main_glossary', $options) ? (int) (bool) $options['main_glossary'] : 0;
        $moduleinfo->defaultapproval = array_key_exists('default_approval', $options)
            ? (int) (bool) $options['default_approval']
            : 1;
        $moduleinfo->editalways = array_key_exists('edit_always', $options) ? (int) (bool) $options['edit_always'] : 0;
        $moduleinfo->allowduplicatedentries = array_key_exists('allow_duplicated_entries', $options)
            ? (int) (bool) $options['allow_duplicated_entries']
            : 0;
        $moduleinfo->allowcomments = array_key_exists('allow_comments', $options) ? (int) (bool) $options['allow_comments'] : 0;
        $moduleinfo->usedynalink = array_key_exists('use_dynamic_linking', $options)
            ? (int) (bool) $options['use_dynamic_linking']
            : 0;
        $moduleinfo->displayformat = $displayformat;
        $moduleinfo->approvaldisplayformat = clean_param(
            (string) ($options['approval_display_format'] ?? $options['approvaldisplayformat'] ?? 'default'),
            PARAM_PLUGIN
        );
        $moduleinfo->entbypage = max(1, (int) ($options['entries_per_page'] ?? 10));
        $moduleinfo->showalphabet = array_key_exists('show_alphabet', $options) ? (int) (bool) $options['show_alphabet'] : 1;
        $moduleinfo->showall = array_key_exists('show_all', $options) ? (int) (bool) $options['show_all'] : 1;
        $moduleinfo->showspecial = array_key_exists('show_special', $options) ? (int) (bool) $options['show_special'] : 1;
        $moduleinfo->allowprintview = array_key_exists('allow_print_view', $options)
            ? (int) (bool) $options['allow_print_view']
            : 1;
        $moduleinfo->assessed = 0;
        $moduleinfo->scale = 0;
        $moduleinfo->ratingtime = 0;
        $moduleinfo->assesstimestart = 0;
        $moduleinfo->assesstimefinish = 0;
        $moduleinfo->rsstype = 0;
        $moduleinfo->rssarticles = 0;
        $moduleinfo->completionentries = self::optional_int_range($options, 'completion_entries', 0, 0, 1000);
    }

    /**
     * Add wiki-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     * @param array $options Options.
     */
    public static function apply_wiki_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;

        $wikimode = clean_param((string) ($options['wiki_mode'] ?? $options['wikimode'] ?? 'collaborative'), PARAM_ALPHA);
        if (!in_array($wikimode, ['collaborative', 'individual'], true)) {
            throw new \invalid_parameter_exception('options.wiki_mode must be one of: collaborative, individual.');
        }

        $defaultformat = clean_param((string) ($options['default_format'] ?? $options['defaultformat'] ?? 'html'), PARAM_ALPHA);
        if (!in_array($defaultformat, wiki_get_formats(), true)) {
            throw new \invalid_parameter_exception('options.default_format must reference an installed wiki format.');
        }

        $firstpage = trim((string) ($options['first_page_title'] ?? $options['firstpagetitle'] ?? $moduleinfo->name));
        if ($firstpage === '') {
            throw new \invalid_parameter_exception('options.first_page_title must not be empty.');
        }

        $moduleinfo->wikimode = $wikimode;
        $moduleinfo->firstpagetitle = $firstpage;
        $moduleinfo->defaultformat = $defaultformat;
        $moduleinfo->forceformat = array_key_exists('force_format', $options) ? (int) (bool) $options['force_format'] : 0;
        $moduleinfo->editbegin = 0;
        $moduleinfo->editend = 0;
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
     * Map public choice display values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_choice_display(string $value): int {
        $display = clean_param($value, PARAM_ALPHA);
        $map = [
            'horizontal' => defined('CHOICE_DISPLAY_HORIZONTAL') ? CHOICE_DISPLAY_HORIZONTAL : 0,
            'vertical' => defined('CHOICE_DISPLAY_VERTICAL') ? CHOICE_DISPLAY_VERTICAL : 1,
        ];
        if (!array_key_exists($display, $map)) {
            throw new \invalid_parameter_exception('options.display must be one of: horizontal, vertical.');
        }

        return $map[$display];
    }

    /**
     * Map public choice result visibility values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_choice_show_results(string $value): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'none' => defined('CHOICE_SHOWRESULTS_NOT') ? CHOICE_SHOWRESULTS_NOT : 0,
            'after_answer' => defined('CHOICE_SHOWRESULTS_AFTER_ANSWER') ? CHOICE_SHOWRESULTS_AFTER_ANSWER : 1,
            'after_close' => defined('CHOICE_SHOWRESULTS_AFTER_CLOSE') ? CHOICE_SHOWRESULTS_AFTER_CLOSE : 2,
            'always' => defined('CHOICE_SHOWRESULTS_ALWAYS') ? CHOICE_SHOWRESULTS_ALWAYS : 3,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.show_results must be one of: none, after_answer, after_close, always.');
        }

        return $map[$key];
    }

    /**
     * Map public choice publish values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_choice_publish(string $value): int {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'anonymous' => defined('CHOICE_PUBLISH_ANONYMOUS') ? CHOICE_PUBLISH_ANONYMOUS : 0,
            'names' => defined('CHOICE_PUBLISH_NAMES') ? CHOICE_PUBLISH_NAMES : 1,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.publish must be one of: anonymous, names.');
        }

        return $map[$key];
    }

    /**
     * Map public feedback anonymity values to Moodle form values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_feedback_anonymous(string $value): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'anonymous' => 1,
            'anon' => 1,
            'non_anonymous' => 2,
            'nonanonymous' => 2,
            'named' => 2,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.anonymous must be one of: anonymous, non_anonymous.');
        }

        return $map[$key];
    }

    /**
     * Map public database sort direction values.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_data_sort_direction(string $value): int {
        $key = clean_param($value, PARAM_ALPHAEXT);
        $map = [
            'ascending' => 0,
            'asc' => 0,
            'descending' => 1,
            'desc' => 1,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.default_sort_direction must be one of: ascending, descending.');
        }

        return $map[$key];
    }

    /**
     * Map public forum subscription values to Moodle constants.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_forum_subscription(string $value): int {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'optional' => defined('FORUM_CHOOSESUBSCRIBE') ? FORUM_CHOOSESUBSCRIBE : 0,
            'forced' => defined('FORUM_FORCESUBSCRIBE') ? FORUM_FORCESUBSCRIBE : 1,
            'auto' => defined('FORUM_INITIALSUBSCRIBE') ? FORUM_INITIALSUBSCRIBE : 2,
            'disabled' => defined('FORUM_DISALLOWSUBSCRIBE') ? FORUM_DISALLOWSUBSCRIBE : 3,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.subscription_mode must be one of: optional, forced, auto, disabled.');
        }

        return $map[$key];
    }

    /**
     * Map public forum tracking values to Moodle constants.
     *
     * @param string $value Value.
     * @return int
     */
    private static function normalise_forum_tracking(string $value): int {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'optional' => defined('FORUM_TRACKING_OPTIONAL') ? FORUM_TRACKING_OPTIONAL : 0,
            'off' => defined('FORUM_TRACKING_OFF') ? FORUM_TRACKING_OFF : 1,
            'forced' => defined('FORUM_TRACKING_FORCED') ? FORUM_TRACKING_FORCED : 2,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.tracking_type must be one of: optional, off, forced.');
        }

        return $map[$key];
    }

    /**
     * Validate forum post blocking settings.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     */
    private static function validate_forum_blocking(\stdClass $moduleinfo): void {
        if ($moduleinfo->blockperiod <= 0 && ($moduleinfo->warnafter > 0 || $moduleinfo->blockafter > 0)) {
            throw new \invalid_parameter_exception('options.block_period_seconds is required when warning or blocking post limits are set.');
        }
        if ($moduleinfo->blockafter > 0 && $moduleinfo->warnafter > $moduleinfo->blockafter) {
            throw new \invalid_parameter_exception('options.warn_after_posts must be less than or equal to options.block_after_posts.');
        }
    }

    /**
     * Validate forum dates.
     *
     * @param \stdClass $moduleinfo Moduleinfo.
     */
    private static function validate_forum_dates(\stdClass $moduleinfo): void {
        if ($moduleinfo->duedate > 0 && $moduleinfo->cutoffdate > 0 && $moduleinfo->cutoffdate < $moduleinfo->duedate) {
            throw new \invalid_parameter_exception('options.cutoff_date must be greater than or equal to options.due_date.');
        }
    }
}
