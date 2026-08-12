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
 * Shared feedback helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle Feedback operations.
 */
class feedback_tools {
    /**
     * Load Moodle feedback APIs.
     */
    public static function require_feedback_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/feedback/lib.php');
        require_once($CFG->dirroot . '/mod/feedback/classes/external.php');
        require_once($CFG->dirroot . '/mod/feedback/item/feedback_item_class.php');
    }

    /**
     * Verify that a course module belongs to a feedback activity.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return \cm_info
     */
    public static function get_feedback_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'feedback') {
            throw new \invalid_parameter_exception('module_id must reference a feedback activity.');
        }

        return $cm;
    }

    /**
     * Return feedback items exposed through Moodle's feedback external API.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_items(\cm_info $cm): array {
        self::require_feedback_api();

        $result = \mod_feedback_external::get_items((int) $cm->instance, 0);
        $items = [];
        foreach (($result['items'] ?? []) as $item) {
            $items[] = self::item_to_response($cm, (array) $item);
        }

        return $items;
    }

    /**
     * Return one feedback page through Moodle's external API.
     *
     * @param \cm_info $cm Cm.
     * @param int $page Page.
     * @return array
     */
    public static function get_page_items(\cm_info $cm, int $page): array {
        self::require_feedback_api();

        if ($page < 0) {
            throw new \invalid_parameter_exception('page must be zero or greater.');
        }

        $result = \mod_feedback_external::get_page_items((int) $cm->instance, $page, 0);
        return self::page_items_to_response($cm, $page, $result);
    }

    /**
     * Return feedback analysis through Moodle's external API.
     *
     * @param \cm_info $cm Cm.
     * @param int $groupid Groupid.
     * @return array
     */
    public static function get_analysis(\cm_info $cm, int $groupid = 0): array {
        self::require_feedback_api();

        $groupid = max(0, $groupid);
        $result = \mod_feedback_external::get_analysis((int) $cm->instance, $groupid, 0);
        return self::analysis_to_response($cm, $groupid, $result);
    }

    /**
     * Return current user's finished feedback responses through Moodle's external API.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_finished_responses(\cm_info $cm): array {
        self::require_feedback_api();

        $result = \mod_feedback_external::get_finished_responses((int) $cm->instance, 0);
        return self::finished_responses_to_response($cm, $result);
    }

    /**
     * Return one feedback item visible in a course module.
     *
     * @param \cm_info $cm Cm.
     * @param int $itemid Itemid.
     * @return array
     */
    public static function get_item(\cm_info $cm, int $itemid): array {
        if ($itemid <= 0) {
            throw new \invalid_parameter_exception('item_id must be a positive integer.');
        }

        foreach (self::get_items($cm) as $item) {
            if ((int) $item['item_id'] === $itemid) {
                return $item;
            }
        }

        throw new \moodle_exception('invaliditemid', 'feedback');
    }

    /**
     * Decode and validate a Feedback item definition payload.
     *
     * @param string $definitionjson Definitionjson.
     * @return array
     */
    public static function decode_item_definition(string $definitionjson): array {
        $decoded = json_decode($definitionjson, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('definition must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * Create or update a Feedback item through Moodle item classes.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param string $type Type.
     * @param string|null $name Name.
     * @param array $definition Definition.
     * @param int|null $position Position.
     * @param string|null $label Label.
     * @param bool|null $required Required.
     * @param int|null $dependitemid Dependitemid.
     * @param string|null $dependvalue Dependvalue.
     * @param array|null $existing Existing.
     * @return array
     */
    public static function save_item(
        \stdClass $course,
        \cm_info $cm,
        string $type,
        ?string $name,
        array $definition,
        ?int $position = null,
        ?string $label = null,
        ?bool $required = null,
        ?int $dependitemid = null,
        ?string $dependvalue = null,
        ?array $existing = null
    ): array {
        self::require_feedback_api();

        $type = self::clean_item_type($type);
        $currentitems = self::get_items($cm);
        $feedback = self::feedback_record($course, $cm);
        $isupdate = $existing !== null;
        $existingposition = $isupdate ? (int) ($existing['position'] ?? 1) : count($currentitems) + 1;
        $targetposition = self::validate_target_position($position, count($currentitems), $isupdate);

        if ($type === 'pagebreak') {
            if ($isupdate) {
                throw new \invalid_parameter_exception('pagebreak items cannot be updated; delete and recreate the page break.');
            }
            if (count($currentitems) === 0) {
                throw new \invalid_parameter_exception('pagebreak items require at least one existing feedback item.');
            }
            $itemid = feedback_create_pagebreak((int) $cm->instance);
            if (!$itemid) {
                throw new \invalid_parameter_exception('pagebreak cannot be created after another pagebreak.');
            }
            $saved = (object) [
                'id' => (int) $itemid,
                'feedback' => (int) $cm->instance,
                'position' => count($currentitems) + 1,
            ];
            if ($targetposition !== null && (int) $saved->position !== $targetposition) {
                $saved->position = $targetposition;
                feedback_move_item($saved, $targetposition);
            }
            feedback_renumber_items((int) $cm->instance);
            rebuild_course_cache($course->id, true);

            return self::get_item($cm, (int) $itemid);
        }

        if ($type === 'captcha') {
            self::validate_captcha_definition($definition, $currentitems, $isupdate, $required, $dependitemid, $dependvalue);
        }

        $item = new \stdClass();
        $item->id = $isupdate ? (int) $existing['item_id'] : 0;
        $item->feedback = (int) $cm->instance;
        $item->template = 0;
        $item->typ = $type;
        $item->name = $type === 'captcha' ? get_string('captcha', 'feedback') : self::resolve_item_name($name, $existing);
        $item->nameformat = FORMAT_HTML;
        $item->label = self::resolve_optional_string($label, $existing['label'] ?? '');
        $item->position = $isupdate ? $existingposition : count($currentitems) + 1;
        $item->required = self::resolve_optional_bool($required, (bool) ($existing['required'] ?? false)) ? 1 : 0;
        $item->dependitem = self::resolve_dependency_item($cm, $dependitemid, (int) ($existing['depend_item_id'] ?? 0), $item->id);
        $item->dependvalue = self::resolve_optional_string($dependvalue, $existing['depend_value'] ?? '');
        $item->options = (string) ($existing['options'] ?? '');

        self::apply_type_definition($item, $type, $definition, $existing, (int) $feedback->anonymous);

        $itemclass = feedback_get_item_class($type);
        if (!$itemclass) {
            throw new \invalid_parameter_exception('Unsupported feedback item type.');
        }
        $itemclass->build_editform($item, $feedback, $cm);
        $itemclass->set_data($item);
        $saved = $itemclass->save_item();
        if (!$saved || empty($saved->id)) {
            throw new \moodle_exception('Could not save feedback item.', 'local_moodlia');
        }

        if ($targetposition !== null && (int) $saved->position !== $targetposition) {
            $saved->position = $targetposition;
            feedback_move_item($saved, $targetposition);
        }
        feedback_renumber_items((int) $cm->instance);
        rebuild_course_cache($course->id, true);

        return self::get_item($cm, (int) $saved->id);
    }

    /**
     * Build a minimal Moodle feedback record for item APIs.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @return \stdClass
     */
    private static function feedback_record(\stdClass $course, \cm_info $cm): \stdClass {
        $feedback = new \stdClass();
        $feedback->id = (int) $cm->instance;
        $feedback->course = (int) $course->id;
        $feedback->coursemodule = (int) $cm->id;
        $feedback->name = (string) $cm->name;
        $feedback->anonymous = FEEDBACK_ANONYMOUS_YES;

        try {
            $details = simple_activity_tools::get_feedback_details($course, $cm);
            if (isset($details['anonymous']) && $details['anonymous'] !== null) {
                $feedback->anonymous = (int) $details['anonymous'];
            }
        } catch (\Throwable $exception) {
            // Item APIs only need this for narrowing info-mode options.
            unset($exception);
        }

        return $feedback;
    }

    /**
     * Validate a supported Feedback item type.
     *
     * @param string $type Type.
     * @return string
     */
    private static function clean_item_type(string $type): string {
        $type = clean_param($type, PARAM_ALPHANUMEXT);
        $supported = ['textfield', 'textarea', 'numeric', 'multichoice', 'multichoicerated', 'label', 'info', 'captcha', 'pagebreak'];
        if (!in_array($type, $supported, true)) {
            throw new \invalid_parameter_exception('type must be one of: textfield, textarea, numeric, multichoice, multichoicerated, label, info, captcha, pagebreak.');
        }

        return $type;
    }

    /**
     * Validate the narrow Feedback captcha writer contract before Moodle item code can call notice()/exit.
     *
     * @param array $definition Definition.
     * @param array $currentitems Currentitems.
     * @param bool $isupdate Isupdate.
     * @param bool|null $required Required.
     * @param int|null $dependitemid Dependitemid.
     * @param string|null $dependvalue Dependvalue.
     */
    private static function validate_captcha_definition(
        array $definition,
        array $currentitems,
        bool $isupdate,
        ?bool $required,
        ?int $dependitemid,
        ?string $dependvalue
    ): void {
        if ($isupdate) {
            throw new \invalid_parameter_exception('captcha items cannot be updated; delete and recreate the captcha item.');
        }
        if (!empty($definition)) {
            throw new \invalid_parameter_exception('captcha items do not accept definition settings.');
        }
        if ($required === false) {
            throw new \invalid_parameter_exception('captcha items are always required.');
        }
        if (($dependitemid !== null && $dependitemid > 0) || trim((string) $dependvalue) !== '') {
            throw new \invalid_parameter_exception('captcha items cannot depend on another feedback item.');
        }
        foreach ($currentitems as $item) {
            if ((string) ($item['type'] ?? '') === 'captcha') {
                throw new \invalid_parameter_exception('Only one captcha item is allowed in a feedback activity.');
            }
        }
    }

    /**
     * Resolve and validate the item name.
     *
     * @param string|null $name Name.
     * @param array|null $existing Existing.
     * @return string
     */
    private static function resolve_item_name(?string $name, ?array $existing): string {
        $value = $name === null ? (string) ($existing['name'] ?? '') : trim($name);
        if ($value === '') {
            throw new \invalid_parameter_exception('name must be non-empty.');
        }

        return $value;
    }

    /**
     * Resolve an optional string preserving current values on update.
     *
     * @param string|null $value Value.
     * @param string $fallback Fallback.
     * @return string
     */
    private static function resolve_optional_string(?string $value, string $fallback): string {
        return $value === null ? $fallback : trim($value);
    }

    /**
     * Resolve an optional bool preserving current values on update.
     *
     * @param bool|null $value Value.
     * @param bool $fallback Fallback.
     * @return bool
     */
    private static function resolve_optional_bool(?bool $value, bool $fallback): bool {
        return $value === null ? $fallback : $value;
    }

    /**
     * Validate and resolve item dependency ownership.
     *
     * @param \cm_info $cm Cm.
     * @param int|null $requested Requested.
     * @param int $fallback Fallback.
     * @param int $selfid Selfid.
     * @return int
     */
    private static function resolve_dependency_item(\cm_info $cm, ?int $requested, int $fallback, int $selfid): int {
        $dependitemid = $requested === null ? $fallback : $requested;
        if ($dependitemid < 0) {
            throw new \invalid_parameter_exception('depend_item_id must be zero or greater.');
        }
        if ($dependitemid > 0) {
            if ($selfid > 0 && $dependitemid === $selfid) {
                throw new \invalid_parameter_exception('depend_item_id cannot reference the item itself.');
            }
            self::get_item($cm, $dependitemid);
        }

        return $dependitemid;
    }

    /**
     * Validate one-based target position.
     *
     * @param int|null $position Position.
     * @param int $itemcount Itemcount.
     * @param bool $isupdate Isupdate.
     * @return int|null
     */
    private static function validate_target_position(?int $position, int $itemcount, bool $isupdate): ?int {
        if ($position === null) {
            return null;
        }
        $max = $isupdate ? max(1, $itemcount) : $itemcount + 1;
        if ($position < 1 || $position > $max) {
            throw new \invalid_parameter_exception('position is outside the valid feedback item range.');
        }

        return $position;
    }

    /**
     * Apply type-specific definition values.
     *
     * @param \stdClass $item Item.
     * @param string $type Type.
     * @param array $definition Definition.
     * @param array|null $existing Existing.
     * @param int $feedbackanonymous Feedbackanonymous.
     */
    private static function apply_type_definition(
        \stdClass $item,
        string $type,
        array $definition,
        ?array $existing,
        int $feedbackanonymous
    ): void {
        if (empty($definition) && $existing !== null) {
            $item->presentation = (string) ($existing['presentation'] ?? '');
            $item->presentationformat = (int) ($existing['presentation_format'] ?? FORMAT_HTML);
            if ($type === 'multichoice' || $type === 'multichoicerated') {
                $item->ignoreempty = strpos($item->options, 'i') !== false;
                $item->hidenoselect = strpos($item->options, 'h') !== false;
            }
            if ($type === 'label') {
                $item->presentation_editor = [
                    'text' => $item->presentation,
                    'format' => $item->presentationformat,
                    'itemid' => 0,
                ];
            }
            return;
        }

        switch ($type) {
            case 'textfield':
                $size = self::int_option($definition, 'size', 30, 5, 255);
                $maxlength = self::int_option($definition, 'max_length', 255, 1, 2000);
                $item->presentation = $size . '|' . $maxlength;
                $item->presentationformat = FORMAT_HTML;
                break;

            case 'textarea':
                $width = self::int_option($definition, 'width', 30, 5, 255);
                $height = self::int_option($definition, 'height', 5, 1, 100);
                $item->presentation = $width . '|' . $height;
                $item->presentationformat = FORMAT_HTML;
                break;

            case 'numeric':
                $rangefrom = self::nullable_float_option($definition, 'range_from');
                $rangeto = self::nullable_float_option($definition, 'range_to');
                if ($rangefrom !== null && $rangeto !== null && $rangefrom > $rangeto) {
                    throw new \invalid_parameter_exception('definition.range_from must not be greater than definition.range_to.');
                }
                $item->presentation = self::feedback_number($rangefrom) . '|' . self::feedback_number($rangeto);
                $item->presentationformat = FORMAT_HTML;
                break;

            case 'multichoice':
                $subtype = self::string_option($definition, 'subtype', 'radio');
                $subtypes = ['radio' => 'r', 'checkbox' => 'c', 'dropdown' => 'd', 'r' => 'r', 'c' => 'c', 'd' => 'd'];
                if (!array_key_exists($subtype, $subtypes)) {
                    throw new \invalid_parameter_exception('definition.subtype must be radio, checkbox, or dropdown.');
                }
                $choices = self::choice_options($definition);
                $horizontal = !empty($definition['horizontal']) ? '1' : '0';
                $presentation = $subtypes[$subtype] . '>>>>>' . implode('|', $choices);
                if ($subtypes[$subtype] !== 'd') {
                    $presentation .= '<<<<<' . $horizontal;
                }
                $item->presentation = $presentation;
                $item->presentationformat = FORMAT_HTML;
                $item->ignoreempty = array_key_exists('ignore_empty', $definition) ? (bool) $definition['ignore_empty'] : true;
                $item->hidenoselect = array_key_exists('hide_no_select', $definition) ? (bool) $definition['hide_no_select'] : false;
                $item->options = '';
                break;

            case 'multichoicerated':
                $subtype = self::string_option($definition, 'subtype', 'radio');
                $subtypes = ['radio' => 'r', 'dropdown' => 'd', 'r' => 'r', 'd' => 'd'];
                if (!array_key_exists($subtype, $subtypes)) {
                    throw new \invalid_parameter_exception('definition.subtype must be radio or dropdown for multichoicerated items.');
                }
                $choices = self::rated_choice_options($definition);
                $horizontal = !empty($definition['horizontal']) ? '1' : '0';
                $presentation = $subtypes[$subtype] . '>>>>>' . implode('|', $choices);
                if ($subtypes[$subtype] !== 'd') {
                    $presentation .= '<<<<<' . $horizontal;
                }
                $item->presentation = $presentation;
                $item->presentationformat = FORMAT_HTML;
                $item->ignoreempty = array_key_exists('ignore_empty', $definition) ? (bool) $definition['ignore_empty'] : true;
                $item->hidenoselect = array_key_exists('hide_no_select', $definition) ? (bool) $definition['hide_no_select'] : false;
                $item->options = '';
                break;

            case 'label':
                $content = trim((string) ($definition['content'] ?? ''));
                if ($content === '') {
                    throw new \invalid_parameter_exception('definition.content must be non-empty for label items.');
                }
                $item->presentation = $content;
                $item->presentationformat = FORMAT_HTML;
                $item->presentation_editor = [
                    'text' => $content,
                    'format' => FORMAT_HTML,
                    'itemid' => 0,
                ];
                $item->required = 0;
                break;

            case 'info':
                $mode = self::string_option($definition, 'mode', 'course');
                $modes = [
                    'response_time' => 1,
                    'responsetime' => 1,
                    '1' => 1,
                    'course' => 2,
                    '2' => 2,
                    'category' => 3,
                    'course_category' => 3,
                    '3' => 3,
                ];
                if (!array_key_exists($mode, $modes)) {
                    throw new \invalid_parameter_exception('definition.mode must be course, category, or response_time.');
                }
                if ($modes[$mode] === 1 && $feedbackanonymous !== FEEDBACK_ANONYMOUS_NO) {
                    throw new \invalid_parameter_exception('response_time info items require non-anonymous Feedback settings.');
                }
                $item->presentation = (string) $modes[$mode];
                $item->presentationformat = FORMAT_HTML;
                $item->required = 0;
                break;

            case 'captcha':
                $item->presentation = '';
                $item->presentationformat = FORMAT_HTML;
                $item->required = 1;
                $item->label = '';
                $item->dependitem = 0;
                $item->dependvalue = '';
                $item->options = '';
                break;
        }
    }

    /**
     * Return an integer option within bounds.
     *
     * @param array $definition Definition.
     * @param string $key Key.
     * @param int $default Default.
     * @param int $min Min.
     * @param int $max Max.
     * @return int
     */
    private static function int_option(array $definition, string $key, int $default, int $min, int $max): int {
        $value = (int) ($definition[$key] ?? $default);
        if ($value < $min || $value > $max) {
            throw new \invalid_parameter_exception('definition.' . $key . ' is outside the valid range.');
        }

        return $value;
    }

    /**
     * Return a nullable float option.
     *
     * @param array $definition Definition.
     * @param string $key Key.
     * @return float|null
     */
    private static function nullable_float_option(array $definition, string $key): ?float {
        if (!array_key_exists($key, $definition) || $definition[$key] === null || $definition[$key] === '') {
            return null;
        }
        if (!is_numeric($definition[$key])) {
            throw new \invalid_parameter_exception('definition.' . $key . ' must be numeric.');
        }

        return (float) $definition[$key];
    }

    /**
     * Format a Feedback numeric boundary.
     *
     * @param float|null $value Value.
     * @return string
     */
    private static function feedback_number(?float $value): string {
        if ($value === null) {
            return '-';
        }

        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
    }

    /**
     * Return a trimmed string option.
     *
     * @param array $definition Definition.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private static function string_option(array $definition, string $key, string $default): string {
        return trim((string) ($definition[$key] ?? $default));
    }

    /**
     * Validate multichoice options.
     *
     * @param array $definition Definition.
     * @return array
     */
    private static function choice_options(array $definition): array {
        $choices = $definition['choices'] ?? null;
        if (!is_array($choices) || count($choices) < 2) {
            throw new \invalid_parameter_exception('definition.choices must contain at least two choices.');
        }

        $seen = [];
        $items = [];
        foreach ($choices as $choice) {
            $text = trim((string) $choice);
            if ($text === '') {
                throw new \invalid_parameter_exception('definition.choices cannot contain empty choices.');
            }
            if (strpos($text, '|') !== false || strpos($text, '>>>>>') !== false || strpos($text, '<<<<<') !== false) {
                throw new \invalid_parameter_exception('definition.choices contain unsupported separator characters.');
            }
            $key = \core_text::strtolower($text);
            if (isset($seen[$key])) {
                throw new \invalid_parameter_exception('definition.choices must be unique.');
            }
            $seen[$key] = true;
            $items[] = $text;
        }

        return $items;
    }

    /**
     * Validate multichoice rated options.
     *
     * @param array $definition Definition.
     * @return array
     */
    private static function rated_choice_options(array $definition): array {
        $choices = $definition['choices'] ?? null;
        if (!is_array($choices) || count($choices) < 2) {
            throw new \invalid_parameter_exception('definition.choices must contain at least two rated choices.');
        }

        $seen = [];
        $items = [];
        foreach ($choices as $choice) {
            if (!is_array($choice)) {
                throw new \invalid_parameter_exception('Each rated choice must be an object.');
            }
            $text = trim((string) ($choice['text'] ?? $choice['label'] ?? ''));
            if ($text === '') {
                throw new \invalid_parameter_exception('Each rated choice requires text.');
            }
            if (!array_key_exists('value', $choice) && !array_key_exists('weight', $choice)) {
                throw new \invalid_parameter_exception('Each rated choice requires value or weight.');
            }
            $weight = $choice['value'] ?? $choice['weight'];
            if (!is_numeric($weight)) {
                throw new \invalid_parameter_exception('Each rated choice value must be numeric.');
            }
            $weight = (int) $weight;
            if (
                strpos($text, '|') !== false || strpos($text, '####') !== false ||
                    strpos($text, '>>>>>') !== false || strpos($text, '<<<<<') !== false
            ) {
                throw new \invalid_parameter_exception('Rated choice text contains unsupported separator characters.');
            }
            $key = \core_text::strtolower($text);
            if (isset($seen[$key])) {
                throw new \invalid_parameter_exception('Rated choice text values must be unique.');
            }
            $seen[$key] = true;
            $items[] = $weight . '####' . $text;
        }

        return $items;
    }

    /**
     * Convert Moodle external warnings to the canonical response shape.
     *
     * @param array $warnings Warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $items = [];
        foreach ($warnings as $warning) {
            $warning = (array) $warning;
            $items[] = [
                'item' => (string) ($warning['item'] ?? ''),
                'item_id' => (int) ($warning['itemid'] ?? $warning['item_id'] ?? 0),
                'warning_code' => (string) ($warning['warningcode'] ?? $warning['warning_code'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Convert a Moodle feedback summary payload to the canonical response shape.
     *
     * @param array $feedback Feedback.
     * @return array
     */
    public static function summary_to_response(array $feedback): array {
        $moduleid = (int) ($feedback['coursemodule'] ?? $feedback['cmid'] ?? 0);
        $url = $moduleid > 0 ? (new \moodle_url('/mod/feedback/view.php', ['id' => $moduleid]))->out(false) : '';

        return [
            'feedback_id' => (int) ($feedback['id'] ?? 0),
            'module_id' => $moduleid,
            'course_id' => (int) ($feedback['course'] ?? 0),
            'name' => (string) ($feedback['name'] ?? ''),
            'intro' => (string) ($feedback['intro'] ?? ''),
            'intro_format' => (int) ($feedback['introformat'] ?? FORMAT_MOODLE),
            'language' => (string) ($feedback['lang'] ?? ''),
            'anonymous' => (int) ($feedback['anonymous'] ?? 0),
            'email_notification' => (bool) ($feedback['email_notification'] ?? false),
            'multiple_submit' => (bool) ($feedback['multiple_submit'] ?? false),
            'auto_numbering' => (bool) ($feedback['autonumbering'] ?? false),
            'site_after_submit' => (string) ($feedback['site_after_submit'] ?? ''),
            'page_after_submit' => (string) ($feedback['page_after_submit'] ?? ''),
            'page_after_submit_format' => (int) ($feedback['page_after_submitformat'] ?? FORMAT_MOODLE),
            'publish_stats' => (bool) ($feedback['publish_stats'] ?? false),
            'time_open' => (int) ($feedback['timeopen'] ?? 0),
            'time_close' => (int) ($feedback['timeclose'] ?? 0),
            'time_modified' => (int) ($feedback['timemodified'] ?? 0),
            'completion_submit' => (bool) ($feedback['completionsubmit'] ?? false),
            'url' => $url,
        ];
    }

    /**
     * Convert Moodle feedback course listing to the canonical response shape.
     *
     * @param \stdClass $course Course.
     * @param array $result Result.
     * @return array
     */
    public static function course_feedbacks_to_response(\stdClass $course, array $result): array {
        $items = [];
        foreach (($result['feedbacks'] ?? []) as $feedback) {
            $summary = self::summary_to_response((array) $feedback);
            if ((int) $summary['course_id'] === (int) $course->id) {
                $items[] = $summary;
            }
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($items),
            'feedbacks' => $items,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Convert Moodle feedback access information to the canonical response shape.
     *
     * @param \cm_info $cm Cm.
     * @param array $result Result.
     * @return array
     */
    public static function access_to_response(\cm_info $cm, array $result): array {
        return [
            'feedback_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'can_view_analysis' => (bool) ($result['canviewanalysis'] ?? false),
            'can_complete' => (bool) ($result['cancomplete'] ?? false),
            'can_submit' => (bool) ($result['cansubmit'] ?? false),
            'can_delete_submissions' => (bool) ($result['candeletesubmissions'] ?? false),
            'can_view_reports' => (bool) ($result['canviewreports'] ?? false),
            'can_edit_items' => (bool) ($result['canedititems'] ?? false),
            'is_empty' => (bool) ($result['isempty'] ?? false),
            'is_open' => (bool) ($result['isopen'] ?? false),
            'is_already_submitted' => (bool) ($result['isalreadysubmitted'] ?? false),
            'is_anonymous' => (bool) ($result['isanonymous'] ?? false),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Convert a Moodle feedback item payload to the canonical response shape.
     *
     * @param \cm_info $cm Cm.
     * @param array $item Item.
     * @return array
     */
    public static function item_to_response(\cm_info $cm, array $item): array {
        return [
            'item_id' => (int) ($item['id'] ?? 0),
            'feedback_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'name' => (string) ($item['name'] ?? ''),
            'name_format' => (int) ($item['nameformat'] ?? FORMAT_HTML),
            'label' => (string) ($item['label'] ?? ''),
            'presentation' => (string) ($item['presentation'] ?? ''),
            'presentation_format' => (int) ($item['presentationformat'] ?? FORMAT_HTML),
            'type' => (string) ($item['typ'] ?? ''),
            'has_value' => (bool) ($item['hasvalue'] ?? false),
            'position' => (int) ($item['position'] ?? 0),
            'item_number' => (int) ($item['itemnumber'] ?? 0),
            'required' => (bool) ($item['required'] ?? false),
            'depend_item_id' => (int) ($item['dependitem'] ?? 0),
            'depend_value' => (string) ($item['dependvalue'] ?? ''),
            'options' => (string) ($item['options'] ?? ''),
            'other_data' => self::string_value($item['otherdata'] ?? ''),
        ];
    }

    /**
     * Convert a Moodle feedback page payload to the canonical response shape.
     *
     * @param \cm_info $cm Cm.
     * @param int $page Page.
     * @param array $result Result.
     * @return array
     */
    public static function page_items_to_response(\cm_info $cm, int $page, array $result): array {
        $items = [];
        foreach (($result['items'] ?? []) as $item) {
            $items[] = self::item_to_response($cm, (array) $item);
        }

        return [
            'feedback_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'page' => max(0, $page),
            'count' => count($items),
            'has_previous_page' => (bool) ($result['hasprevpage'] ?? false),
            'has_next_page' => (bool) ($result['hasnextpage'] ?? false),
            'items' => $items,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Convert a Moodle feedback analysis payload to the canonical response shape.
     *
     * @param \cm_info $cm Cm.
     * @param int $groupid Groupid.
     * @param array $result Result.
     * @return array
     */
    public static function analysis_to_response(\cm_info $cm, int $groupid, array $result): array {
        $items = [];
        foreach (($result['itemsdata'] ?? []) as $entry) {
            $entry = (array) $entry;
            $items[] = [
                'item' => self::item_to_response($cm, (array) ($entry['item'] ?? [])),
                'data_json' => self::json_value($entry['data'] ?? []),
            ];
        }

        return [
            'feedback_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'group_id' => max(0, $groupid),
            'completed_count' => (int) ($result['completedcount'] ?? 0),
            'items_count' => (int) ($result['itemscount'] ?? 0),
            'items_data' => $items,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Convert Moodle feedback response values to the canonical response shape.
     *
     * @param \cm_info $cm Cm.
     * @param array $result Result.
     * @return array
     */
    public static function finished_responses_to_response(\cm_info $cm, array $result): array {
        $responses = [];
        foreach (($result['responses'] ?? []) as $response) {
            $item = (array) $response;
            $responses[] = [
                'response_id' => (int) ($item['id'] ?? 0),
                'name' => (string) ($item['name'] ?? ''),
                'print_value' => (string) ($item['printval'] ?? ''),
                'raw_value' => (string) ($item['rawval'] ?? ''),
            ];
        }

        return [
            'feedback_id' => (int) $cm->instance,
            'module_id' => (int) $cm->id,
            'count' => count($responses),
            'responses' => $responses,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Convert a Moodle value to a stable string response.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function string_value($value): string {
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '' : $encoded;
    }

    /**
     * Encode flexible Moodle payloads as stable JSON strings.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function json_value($value): string {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '[]' : $encoded;
    }
}
