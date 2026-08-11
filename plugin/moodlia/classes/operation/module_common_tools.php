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
 * Shared helpers for Moodle common module settings.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles Moodle settings that are common to multiple activity modules.
 */
class module_common_tools {
    /**
     * Add Moodle's common module fields to module info.
     *
     * @param \stdClass $course Moodle course.
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_create_options(\stdClass $course, \stdClass $moduleinfo, array $options): void {
        if (array_key_exists('visible', $options)) {
            $visible = (int) (bool) $options['visible'];
            $moduleinfo->visible = $visible;
            if (!array_key_exists('visible_on_course_page', $options) && !array_key_exists('visibleoncoursepage', $options)) {
                $moduleinfo->visibleoncoursepage = $visible;
            }
        }

        if (array_key_exists('visible_on_course_page', $options) || array_key_exists('visibleoncoursepage', $options)) {
            $moduleinfo->visibleoncoursepage = (int) (bool) ($options['visible_on_course_page'] ?? $options['visibleoncoursepage']);
        }

        if (array_key_exists('show_description', $options) || array_key_exists('showdescription', $options)) {
            $moduleinfo->showdescription = (int) (bool) ($options['show_description'] ?? $options['showdescription']);
        }

        if (array_key_exists('id_number', $options) || array_key_exists('cmidnumber', $options)) {
            $moduleinfo->cmidnumber = clean_param(
                trim((string) ($options['id_number'] ?? $options['cmidnumber'])),
                PARAM_NOTAGS
            );
        }

        if (array_key_exists('language', $options) || array_key_exists('lang', $options)) {
            $language = clean_param(trim((string) ($options['language'] ?? $options['lang'])), PARAM_LANG);
            if ($language !== '') {
                $translations = get_string_manager()->get_list_of_translations(true);
                if (!array_key_exists($language, $translations)) {
                    throw new \invalid_parameter_exception('options.language must reference an installed Moodle language pack.');
                }
            }
            $moduleinfo->lang = $language;
        }

        if (array_key_exists('group_mode', $options) || array_key_exists('groupmode', $options)) {
            $moduleinfo->groupmode = self::normalise_group_mode($options['group_mode'] ?? $options['groupmode']);
        }

        if (array_key_exists('grouping_id', $options) || array_key_exists('groupingid', $options)) {
            $groupingid = (int) ($options['grouping_id'] ?? $options['groupingid']);
            if ($groupingid < 0) {
                throw new \invalid_parameter_exception('options.grouping_id must be zero or a positive integer.');
            }
            $groupings = groups_get_all_groupings($course->id);
            if ($groupingid > 0 && (!is_array($groupings) || !array_key_exists($groupingid, $groupings))) {
                throw new \invalid_parameter_exception('options.grouping_id must reference a grouping in the selected course.');
            }
            $moduleinfo->groupingid = $groupingid;
        }

        if (array_key_exists('availability', $options) || array_key_exists('availabilityconditionsjson', $options)) {
            $moduleinfo->availabilityconditionsjson = self::normalise_availability(
                $options['availability'] ?? $options['availabilityconditionsjson']
            );
        }

        if (array_key_exists('tags', $options)) {
            $moduleinfo->tags = self::normalise_tags($options['tags']);
        }

        if (array_key_exists('download_content', $options) || array_key_exists('downloadcontent', $options)) {
            $moduleinfo->downloadcontent = (int) (bool) ($options['download_content'] ?? $options['downloadcontent']);
        }

        self::apply_completion_options($course, $moduleinfo, $options, false);
    }

    /**
     * Apply common module updates that Moodle exposes through stable partial-update APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Course module.
     * @param array $options Module options.
     */
    public static function apply_update_options(\stdClass $course, \cm_info $cm, array $options): void {
        $allowed = [
            'visible',
            'visible_on_course_page',
            'visibleoncoursepage',
            'id_number',
            'cmidnumber',
            'group_mode',
            'groupmode',
            'tags',
            'download_content',
            'downloadcontent',
            'completion_tracking',
            'completion',
            'completion_view_required',
            'completion_view',
            'completionview',
            'completion_grade_item_number',
            'completiongradeitemnumber',
            'completion_use_grade',
            'completionusegrade',
            'completion_pass_grade',
            'completionpassgrade',
            'completion_expected',
            'completionexpected',
            'reset_completion_states',
        ];
        $unknown = array_diff(array_keys($options), $allowed);
        if ($unknown) {
            throw new \invalid_parameter_exception(
                'update_module options currently supports only: ' . implode(', ', $allowed) . '.'
            );
        }

        if (array_key_exists('id_number', $options) || array_key_exists('cmidnumber', $options)) {
            $idnumber = clean_param(
                trim((string) ($options['id_number'] ?? $options['cmidnumber'])),
                PARAM_NOTAGS
            );
            set_coursemodule_idnumber((int) $cm->id, $idnumber);
        }

        if (array_key_exists('group_mode', $options) || array_key_exists('groupmode', $options)) {
            $groupmode = self::normalise_group_mode($options['group_mode'] ?? $options['groupmode']);
            \core_courseformat\formatactions::cm($course->id)->set_groupmode((int) $cm->id, $groupmode);
        }

        if (array_key_exists('tags', $options) && \core_tag_tag::is_enabled('core', 'course_modules')) {
            \core_tag_tag::set_item_tags(
                'core',
                'course_modules',
                (int) $cm->id,
                \context_module::instance((int) $cm->id),
                self::normalise_tags($options['tags'])
            );
        }

        if (array_key_exists('download_content', $options) || array_key_exists('downloadcontent', $options)) {
            set_downloadcontent((int) $cm->id, (bool) ($options['download_content'] ?? $options['downloadcontent']));
        }

        if (self::has_completion_options($options)) {
            if (empty($options['reset_completion_states'])) {
                throw new \invalid_parameter_exception(
                    'options.reset_completion_states=true is required when updating activity completion settings ' .
                        'because Moodle may reset existing completion states.'
                );
            }

            [, , , $moduleinfo] = get_moduleinfo_data($cm, $course);
            $modulecm = get_coursemodule_from_id('', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
            $moduleinfo->id = (int) $cm->instance;
            self::normalise_update_form_data($moduleinfo);
            self::normalise_numeric_form_fields($moduleinfo);
            $moduleinfo->completionunlocked = 1;
            self::apply_completion_options($course, $moduleinfo, $options, true);
            update_moduleinfo($modulecm, $moduleinfo, $course);
        }
    }

    /**
     * Return Moodle's raw course-page visibility setting for a module.
     *
     * @param \cm_info $cm Course module info.
     * @return bool
     */
    public static function is_visible_on_course_page(\cm_info $cm): bool {
        return (bool) ($cm->visibleoncoursepage ?? $cm->visible);
    }

    /**
     * Apply Moodle activity completion settings to a module form data object.
     *
     * @param \stdClass $course Moodle course.
     * @param \stdClass $moduleinfo Module form data object.
     * @param array $options Module options.
     * @param bool $update Whether the data object comes from an existing module.
     */
    private static function apply_completion_options(
        \stdClass $course,
        \stdClass $moduleinfo,
        array $options,
        bool $update
    ): void {
        if (!self::has_completion_options($options)) {
            return;
        }

        $trackingprovided = array_key_exists('completion_tracking', $options) || array_key_exists('completion', $options);
        $viewprovided = array_key_exists('completion_view_required', $options)
            || array_key_exists('completion_view', $options)
            || array_key_exists('completionview', $options);
        $gradeprovided = array_key_exists('completion_grade_item_number', $options)
            || array_key_exists('completiongradeitemnumber', $options)
            || array_key_exists('completion_use_grade', $options)
            || array_key_exists('completionusegrade', $options)
            || array_key_exists('completion_pass_grade', $options)
            || array_key_exists('completionpassgrade', $options);
        $expectedprovided = array_key_exists('completion_expected', $options) || array_key_exists('completionexpected', $options);

        $tracking = $trackingprovided
            ? self::normalise_completion_tracking($options['completion_tracking'] ?? $options['completion'])
            : (int) ($moduleinfo->completion ?? 0);

        if (!$trackingprovided && ($viewprovided || $gradeprovided)) {
            $tracking = 2;
        }

        if ($tracking > 0 && empty($course->enablecompletion)) {
            throw new \invalid_parameter_exception('Course completion must be enabled before activity completion can be configured.');
        }

        $viewrequired = $viewprovided
            ? (bool) ($options['completion_view_required'] ?? $options['completion_view'] ?? $options['completionview'])
            : (bool) ($moduleinfo->completionview ?? false);
        $gradeitemnumber = $gradeprovided
            ? self::normalise_completion_grade_item_number($options, $moduleinfo)
            : (int) ($moduleinfo->completiongradeitemnumber ?? -1);

        if ($tracking !== 2) {
            if (($viewprovided && $viewrequired) || ($gradeprovided && $gradeitemnumber >= 0)) {
                throw new \invalid_parameter_exception('completion_view_required and completion_grade_item_number require completion_tracking=automatic.');
            }

            $viewrequired = false;
            $gradeitemnumber = -1;
        }

        if ($tracking !== 2 && ($viewrequired || $gradeitemnumber >= 0)) {
            throw new \invalid_parameter_exception('completion_view_required and completion_grade_item_number require completion_tracking=automatic.');
        }

        $moduleinfo->completion = $tracking;
        $moduleinfo->completionview = $tracking === 2 && $viewrequired ? 1 : 0;
        $moduleinfo->completiongradeitemnumber = $tracking === 2 ? $gradeitemnumber : -1;
        $moduleinfo->completionusegrade = $tracking === 2 && $gradeitemnumber >= 0 ? 1 : 0;
        $moduleinfo->completionpassgrade = $tracking === 2 && $gradeitemnumber >= 0
            && self::bool_option($options, ['completion_pass_grade', 'completionpassgrade'], false) ? 1 : 0;

        if ($expectedprovided) {
            $expected = (int) ($options['completion_expected'] ?? $options['completionexpected']);
            if ($expected < 0) {
                throw new \invalid_parameter_exception('options.completion_expected must be zero or a positive Unix timestamp.');
            }
            $moduleinfo->completionexpected = $expected;
        } else if (!$update && !isset($moduleinfo->completionexpected)) {
            $moduleinfo->completionexpected = 0;
        }
    }

    /**
     * Return whether options include common completion settings.
     *
     * @param array $options Module options.
     * @return bool
     */
    private static function has_completion_options(array $options): bool {
        foreach ([
            'completion_tracking',
            'completion',
            'completion_view_required',
            'completion_view',
            'completionview',
            'completion_grade_item_number',
            'completiongradeitemnumber',
            'completion_use_grade',
            'completionusegrade',
            'completion_pass_grade',
            'completionpassgrade',
            'completion_expected',
            'completionexpected',
        ] as $key) {
            if (array_key_exists($key, $options)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rebuild module-specific form fields that Moodle update handlers expect.
     *
     * @param \stdClass $moduleinfo Module form data object.
     */
    private static function normalise_update_form_data(\stdClass $moduleinfo): void {
        switch ($moduleinfo->modulename ?? '') {
            case 'page':
                if (!isset($moduleinfo->page)) {
                    $moduleinfo->page = self::editor_array(
                        (string) ($moduleinfo->content ?? ''),
                        (int) ($moduleinfo->contentformat ?? FORMAT_HTML)
                    );
                }
                break;

            case 'feedback':
                if (!isset($moduleinfo->page_after_submit_editor)) {
                    $moduleinfo->page_after_submit_editor = self::editor_array(
                        (string) ($moduleinfo->page_after_submit ?? ''),
                        (int) ($moduleinfo->page_after_submitformat ?? FORMAT_HTML)
                    );
                }
                break;

            case 'workshop':
                if (!isset($moduleinfo->instructauthorseditor)) {
                    $moduleinfo->instructauthorseditor = self::editor_array(
                        (string) ($moduleinfo->instructauthors ?? ''),
                        (int) ($moduleinfo->instructauthorsformat ?? FORMAT_HTML)
                    );
                }
                if (!isset($moduleinfo->instructreviewerseditor)) {
                    $moduleinfo->instructreviewerseditor = self::editor_array(
                        (string) ($moduleinfo->instructreviewers ?? ''),
                        (int) ($moduleinfo->instructreviewersformat ?? FORMAT_HTML)
                    );
                }
                if (!isset($moduleinfo->conclusioneditor)) {
                    $moduleinfo->conclusioneditor = self::editor_array(
                        (string) ($moduleinfo->conclusion ?? ''),
                        (int) ($moduleinfo->conclusionformat ?? FORMAT_HTML)
                    );
                }
                break;

            case 'quiz':
                if (!isset($moduleinfo->password) || $moduleinfo->password === null) {
                    $moduleinfo->password = '';
                }
                if (!isset($moduleinfo->quizpassword) || $moduleinfo->quizpassword === null) {
                    $moduleinfo->quizpassword = (string) $moduleinfo->password;
                }
                break;
        }
    }

    /**
     * Build a Moodle editor form value without creating or moving draft files.
     *
     * @param string $text Existing editor text.
     * @param int $format Existing text format.
     * @return array
     */
    private static function editor_array(string $text, int $format): array {
        return [
            'text' => $text,
            'format' => $format,
            'itemid' => 0,
        ];
    }

    /**
     * Convert Moodle-localised decimal strings back to numeric form values.
     *
     * @param \stdClass $moduleinfo Module form data object.
     */
    private static function normalise_numeric_form_fields(\stdClass $moduleinfo): void {
        foreach (['gradepass', 'grade', 'grademax', 'grademin', 'scale'] as $field) {
            if (property_exists($moduleinfo, $field)) {
                $moduleinfo->$field = self::normalise_numeric_form_value($moduleinfo->$field);
            }
        }
    }

    /**
     * Return a numeric form value from a scalar Moodle form value.
     *
     * @param mixed $value Moodle form value.
     * @return mixed
     */
    private static function normalise_numeric_form_value($value) {
        if (!is_string($value)) {
            return $value;
        }

        $normalised = str_replace(',', '.', trim($value));
        if ($normalised === '' || !is_numeric($normalised)) {
            return $value;
        }

        return (float) $normalised;
    }

    /**
     * Convert a public completion mode to Moodle's numeric values.
     *
     * @param mixed $value Public completion tracking value.
     * @return int
     */
    private static function normalise_completion_tracking($value): int {
        if (is_int($value) || ctype_digit((string) $value)) {
            $mode = (int) $value;
            if (in_array($mode, [0, 1, 2], true)) {
                return $mode;
            }
        }

        $key = clean_param((string) $value, PARAM_ALPHAEXT);
        $map = [
            'none' => 0,
            'off' => 0,
            'manual' => 1,
            'automatic' => 2,
            'auto' => 2,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.completion_tracking must be one of: none, manual, automatic.');
        }

        return $map[$key];
    }

    /**
     * Validate the public completion grade item number.
     *
     * @param array $options Module options.
     * @param \stdClass $moduleinfo Module form data object.
     * @return int
     */
    private static function normalise_completion_grade_item_number(array $options, \stdClass $moduleinfo): int {
        if (array_key_exists('completion_use_grade', $options) || array_key_exists('completionusegrade', $options)) {
            $usegrade = self::bool_option($options, ['completion_use_grade', 'completionusegrade'], false);
            if (!$usegrade) {
                return -1;
            }
        }

        if (array_key_exists('completion_grade_item_number', $options)
                || array_key_exists('completiongradeitemnumber', $options)) {
            $gradeitemnumber = (int) ($options['completion_grade_item_number'] ?? $options['completiongradeitemnumber']);
        } else {
            $gradeitemnumber = (int) ($moduleinfo->completiongradeitemnumber ?? 0);
        }

        if ($gradeitemnumber < -1) {
            throw new \invalid_parameter_exception('options.completion_grade_item_number must be -1 or a non-negative integer.');
        }

        return $gradeitemnumber;
    }

    /**
     * Return a boolean option from a list of aliases.
     *
     * @param array $options Module options.
     * @param array $keys Candidate option names.
     * @param bool $default Default value.
     * @return bool
     */
    private static function bool_option(array $options, array $keys, bool $default): bool {
        foreach ($keys as $key) {
            if (array_key_exists($key, $options)) {
                return (bool) $options[$key];
            }
        }

        return $default;
    }

    /**
     * Convert a public group mode value to Moodle's integer constants.
     *
     * @param mixed $value Public group mode value.
     * @return int
     */
    private static function normalise_group_mode($value): int {
        if (is_int($value) || ctype_digit((string) $value)) {
            $mode = (int) $value;
            if (in_array($mode, [NOGROUPS, SEPARATEGROUPS, VISIBLEGROUPS], true)) {
                return $mode;
            }
        }

        $key = clean_param((string) $value, PARAM_ALPHAEXT);
        $map = [
            'none' => NOGROUPS,
            'no_groups' => NOGROUPS,
            'nogroups' => NOGROUPS,
            'separate' => SEPARATEGROUPS,
            'separate_groups' => SEPARATEGROUPS,
            'separategroups' => SEPARATEGROUPS,
            'visible' => VISIBLEGROUPS,
            'visible_groups' => VISIBLEGROUPS,
            'visiblegroups' => VISIBLEGROUPS,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.group_mode must be one of: none, separate_groups, visible_groups.');
        }

        return $map[$key];
    }

    /**
     * Validate and serialise an availability restriction object.
     *
     * @param mixed $value Availability JSON string or object.
     * @return string
     */
    private static function normalise_availability($value): string {
        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value);
            if ($encoded === false) {
                throw new \invalid_parameter_exception('options.availability must be JSON serialisable.');
            }
            $value = $encoded;
        }

        $json = trim((string) $value);
        if ($json === '') {
            return '';
        }
        if (strlen($json) > 10000) {
            throw new \invalid_parameter_exception('options.availability exceeds the maximum allowed length.');
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \invalid_parameter_exception('options.availability must be a JSON object.');
        }

        return json_encode($decoded);
    }

    /**
     * Validate and normalise tag names for module creation.
     *
     * @param mixed $value Public tag list.
     * @return array
     */
    private static function normalise_tags($value): array {
        $rawtags = is_array($value) ? $value : explode(',', (string) $value);
        $tags = [];
        $paramtype = defined('PARAM_TAG') ? PARAM_TAG : PARAM_TEXT;

        foreach ($rawtags as $tag) {
            $clean = clean_param(trim((string) $tag), $paramtype);
            if ($clean !== '') {
                $tags[] = $clean;
            }
        }

        return array_values(array_unique($tags));
    }
}
