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
 * Shared choice helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle choice activity operations.
 */
class choice_tools {
    /**
     * Load Moodle choice APIs.
     */
    public static function require_choice_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/choice/lib.php');
        require_once($CFG->dirroot . '/mod/choice/classes/external.php');
    }

    /**
     * Verify that a course module belongs to a choice activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_choice_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'choice') {
            throw new \invalid_parameter_exception('choice_module_id must reference a choice activity.');
        }

        return $cm;
    }

    /**
     * Decode a JSON array of option ids.
     *
     * @param string $json JSON array string.
     * @return array
     */
    public static function decode_option_ids(string $json): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_is_list($decoded) || $decoded === []) {
            throw new \invalid_parameter_exception('option_ids must be a non-empty JSON array of integers.');
        }

        $ids = [];
        foreach ($decoded as $value) {
            $id = (int) $value;
            if ($id <= 0) {
                throw new \invalid_parameter_exception('option_ids must contain only positive integers.');
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Decode a JSON array of response ids.
     *
     * @param string $json JSON array string.
     * @return array
     */
    public static function decode_response_ids(string $json): array {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new \invalid_parameter_exception('response_ids must be a JSON array of integers.');
        }

        $ids = [];
        foreach ($decoded as $value) {
            $id = (int) $value;
            if ($id <= 0) {
                throw new \invalid_parameter_exception('response_ids must contain only positive integers.');
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Normalize Moodle warning payloads.
     *
     * @param array $warnings Moodle warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $mapped = [];
        foreach ($warnings as $warning) {
            $item = is_array($warning) ? $warning : (array) $warning;
            $mapped[] = [
                'item' => (string) ($item['item'] ?? ''),
                'item_id' => (int) ($item['itemid'] ?? 0),
                'warning_code' => (string) ($item['warningcode'] ?? ''),
                'message' => (string) ($item['message'] ?? ''),
            ];
        }

        return $mapped;
    }

    /**
     * Return a normalized choice option item.
     *
     * @param array|\stdClass $option Moodle external option item.
     * @return array
     */
    public static function option_to_response($option): array {
        $option = (array) $option;

        return [
            'option_id' => (int) ($option['id'] ?? $option['optionid'] ?? 0),
            'text' => (string) ($option['text'] ?? ''),
            'max_answers' => (int) ($option['maxanswers'] ?? $option['maxanswer'] ?? 0),
            'answer_count' => (int) ($option['countanswers'] ?? $option['numberofuser'] ?? 0),
            'checked' => (bool) ($option['checked'] ?? false),
            'disabled' => (bool) ($option['disabled'] ?? false),
        ];
    }

    /**
     * Return a normalized choice result item.
     *
     * @param array|\stdClass $result Moodle external result item.
     * @return array
     */
    public static function result_to_response($result): array {
        $result = (array) $result;

        return [
            'option_id' => (int) ($result['id'] ?? $result['optionid'] ?? 0),
            'text' => (string) ($result['text'] ?? ''),
            'answer_count' => (int) ($result['countanswers'] ?? $result['numberofuser'] ?? count((array) ($result['userresponses'] ?? []))),
        ];
    }

    /**
     * Return a normalized choice summary.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info|null $cm Optional Choice course module.
     * @param array|\stdClass $choice Moodle external choice item.
     * @return array
     */
    public static function choice_summary_to_response(\stdClass $course, ?\cm_info $cm, $choice): array {
        $choice = (array) $choice;

        return [
            'choice_id' => (int) ($choice['id'] ?? ($cm ? $cm->instance : 0)),
            'choice_module_id' => (int) ($choice['coursemodule'] ?? $choice['coursemoduleid'] ?? ($cm ? $cm->id : 0)),
            'course_id' => (int) ($choice['course'] ?? $course->id),
            'name' => (string) ($choice['name'] ?? ''),
            'intro' => (string) ($choice['intro'] ?? ''),
            'intro_format' => (int) ($choice['introformat'] ?? FORMAT_MOODLE),
            'publish_anonymous' => (bool) ($choice['publish'] ?? false),
            'show_results' => (int) ($choice['showresults'] ?? 0),
            'display' => (int) ($choice['display'] ?? 0),
            'allow_update' => (bool) ($choice['allowupdate'] ?? false),
            'allow_multiple' => (bool) ($choice['allowmultiple'] ?? false),
            'show_unanswered' => (bool) ($choice['showunanswered'] ?? false),
            'include_inactive' => (bool) ($choice['includeinactive'] ?? false),
            'limit_answers' => (bool) ($choice['limitanswers'] ?? false),
            'time_open' => (int) ($choice['timeopen'] ?? 0),
            'time_close' => (int) ($choice['timeclose'] ?? 0),
            'show_preview' => (bool) ($choice['showpreview'] ?? false),
            'time_modified' => (int) ($choice['timemodified'] ?? 0),
            'completion_submit' => (bool) ($choice['completionsubmit'] ?? false),
            'show_available' => (bool) ($choice['showavailable'] ?? false),
        ];
    }

    /**
     * Return a canonical course Choice listing response.
     *
     * @param \stdClass $course Moodle course.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function course_choices_to_response(\stdClass $course, array $result): array {
        $choices = [];
        foreach (($result['choices'] ?? []) as $choice) {
            $choices[] = self::choice_summary_to_response($course, null, $choice);
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($choices),
            'choices' => $choices,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return choice settings, options, and result totals exposed through Moodle choice APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Choice course module.
     * @return array
     */
    public static function get_choice_details(\stdClass $course, \cm_info $cm): array {
        self::require_choice_api();

        self::get_choice_module($course, (int) $cm->id);

        $choice = self::find_choice_instance($course, $cm);
        $optionsresult = \mod_choice_external::get_choice_options((int) $cm->instance);
        $options = array_map([self::class, 'option_to_response'], (array) ($optionsresult['options'] ?? $optionsresult));
        $results = self::get_normalised_results((int) $cm->instance);
        $totalresponses = array_reduce($results, static function(int $total, array $result): int {
            return $total + (int) $result['answer_count'];
        }, 0);

        return [
            'choice_id' => (int) $cm->instance,
            'allowupdate' => (int) ($choice['allowupdate'] ?? 0),
            'allowmultiple' => (int) ($choice['allowmultiple'] ?? 0),
            'showpreview' => (int) ($choice['showpreview'] ?? 0),
            'limitanswers' => (int) ($choice['limitanswers'] ?? 0),
            'showavailable' => (int) ($choice['showavailable'] ?? 0),
            'showresults' => (int) ($choice['showresults'] ?? 0),
            'publish' => (int) ($choice['publish'] ?? 0),
            'display' => (int) ($choice['display'] ?? 0),
            'includeinactive' => (int) ($choice['includeinactive'] ?? 0),
            'showunanswered' => (int) ($choice['showunanswered'] ?? 0),
            'timeopen' => (int) ($choice['timeopen'] ?? 0),
            'timeclose' => (int) ($choice['timeclose'] ?? 0),
            'option_count' => count($options),
            'total_responses' => $totalresponses,
            'options' => $options,
            'results' => $results,
        ];
    }

    /**
     * Return a choice instance payload from Moodle external APIs where available.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Choice course module.
     * @return array
     */
    private static function find_choice_instance(\stdClass $course, \cm_info $cm): array {
        if (!method_exists(\mod_choice_external::class, 'get_choices_by_courses')) {
            return [];
        }

        $result = \mod_choice_external::get_choices_by_courses([(int) $course->id]);
        foreach ((array) ($result['choices'] ?? []) as $choice) {
            $choice = (array) $choice;
            if (
                (int) ($choice['id'] ?? 0) === (int) $cm->instance ||
                (int) ($choice['coursemodule'] ?? $choice['coursemoduleid'] ?? 0) === (int) $cm->id
            ) {
                return $choice;
            }
        }

        return [];
    }

    /**
     * Return normalised choice results, falling back to option counts where Moodle cannot compute percentages.
     *
     * @param int $choiceid Moodle choice instance id.
     * @return array
     */
    private static function get_normalised_results(int $choiceid): array {
        try {
            $results = \mod_choice_external::get_choice_results($choiceid);
            $items = (array) ($results['options'] ?? $results['responses'] ?? $results);
        } catch (\DivisionByZeroError $error) {
            $fallback = \mod_choice_external::get_choice_options($choiceid);
            $items = (array) ($fallback['options'] ?? []);
        }

        return array_map([self::class, 'result_to_response'], $items);
    }
}
