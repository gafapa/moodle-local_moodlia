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
 * Shared module helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for course module operations.
 */
class module_tools {
    /**
     * Load Moodle module APIs.
     */
    public static function require_module_api(): void {
        module_lookup_tools::require_module_api();
    }

    /**
     * Decode JSON object parameters passed through Moodle REST.
     *
     * @param string $json JSON object string.
     * @return array
     */
    public static function decode_options(string $json): array {
        if (trim($json) === '') {
            return [];
        }

        $options = json_decode($json, true);
        if (!is_array($options) || ($options !== [] && array_is_list($options))) {
            throw new \invalid_parameter_exception('options must be a JSON object.');
        }

        return $options;
    }

    /**
     * Resolve a content item module id for an activity type.
     *
     * @param \stdClass $course Moodle course.
     * @param string $modulename Module name.
     * @return int
     */
    public static function resolve_content_item_id(\stdClass $course, string $modulename): int {
        return module_lookup_tools::resolve_content_item_id($course, $modulename);
    }

    /**
     * Load a course module from a course context.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_course_module(\stdClass $course, int $cmid): \cm_info {
        return module_lookup_tools::get_course_module($course, $cmid);
    }

    /**
     * Return the canonical module response shape.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return array
     */
    public static function to_response(\stdClass $course, int $cmid): array {
        return module_lookup_tools::to_response($course, $cmid);
    }

    /**
     * Add Moodle's common module fields to module info.
     *
     * @param \stdClass $course Moodle course.
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_common_options(\stdClass $course, \stdClass $moduleinfo, array $options): void {
        module_common_tools::apply_create_options($course, $moduleinfo, $options);
    }

    /**
     * Apply common module updates that Moodle exposes through stable partial-update APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Course module.
     * @param array $options Module options.
     */
    public static function apply_common_update_options(\stdClass $course, \cm_info $cm, array $options): void {
        module_common_tools::apply_update_options($course, $cm, $options);
    }

    /**
     * Return Moodle's raw course-page visibility setting for a module.
     *
     * @param \cm_info $cm Course module info.
     * @return bool
     */
    public static function is_visible_on_course_page(\cm_info $cm): bool {
        return module_common_tools::is_visible_on_course_page($cm);
    }

    /**
     * Add page-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_page_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_page_options($moduleinfo, $options);
    }

    /**
     * Add question-bank-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_qbank_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_qbank_options($moduleinfo, $options);
    }

    /**
     * Add assignment-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_assign_options(\stdClass $moduleinfo, array $options): void {
        module_assignment_tools::apply_assign_options($moduleinfo, $options);
    }

    /**
     * Add choice-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_choice_options(\stdClass $moduleinfo, array $options): void {
        module_interaction_tools::apply_choice_options($moduleinfo, $options);
    }

    /**
     * Add feedback-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_feedback_options(\stdClass $moduleinfo, array $options): void {
        module_interaction_tools::apply_feedback_options($moduleinfo, $options);
    }

    /**
     * Add database-activity-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_data_options(\stdClass $moduleinfo, array $options): void {
        module_interaction_tools::apply_data_options($moduleinfo, $options);
    }

    /**
     * Add lesson-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_lesson_options(\stdClass $moduleinfo, array $options): void {
        module_advanced_tools::apply_lesson_options($moduleinfo, $options);
    }

    /**
     * Add workshop-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_workshop_options(\stdClass $moduleinfo, array $options): void {
        module_advanced_tools::apply_workshop_options($moduleinfo, $options);
    }

    /**
     * Add LTI-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_lti_options(\stdClass $moduleinfo, array $options): void {
        module_advanced_tools::apply_lti_options($moduleinfo, $options);
    }

    /**
     * Add book-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_book_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_book_options($moduleinfo, $options);
    }

    /**
     * Add folder-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_folder_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_folder_options($moduleinfo, $options);
    }

    /**
     * Add forum-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_forum_options(\stdClass $moduleinfo, array $options): void {
        module_interaction_tools::apply_forum_options($moduleinfo, $options);
    }

    /**
     * Add glossary-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_glossary_options(\stdClass $moduleinfo, array $options): void {
        module_interaction_tools::apply_glossary_options($moduleinfo, $options);
    }

    /**
     * Add label-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_label_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_label_options($moduleinfo, $options);
    }

    /**
     * Add file resource-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_resource_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_resource_options($moduleinfo, $options);
    }

    /**
     * Add subsection-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_subsection_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_subsection_options($moduleinfo, $options);
    }

    /**
     * Add URL-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_url_options(\stdClass $moduleinfo, array $options): void {
        module_content_tools::apply_url_options($moduleinfo, $options);
    }

    /**
     * Add wiki-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_wiki_options(\stdClass $moduleinfo, array $options): void {
        module_interaction_tools::apply_wiki_options($moduleinfo, $options);
    }

    /**
     * Add quiz-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_quiz_options(\stdClass $moduleinfo, array $options): void {
        module_quiz_tools::apply_quiz_options($moduleinfo, $options);
    }

    /**
     * Verify that a course module belongs to a quiz activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_quiz_module(\stdClass $course, int $cmid): \cm_info {
        return module_lookup_tools::get_quiz_module($course, $cmid);
    }
}
