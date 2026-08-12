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
 * Get module details operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns common Moodle course module details through Moodle core APIs.
 */
class get_module_details {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        $base = module_tools::to_response($course, $cm->id);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($moduleid);
        $sectioninfo = self::get_module_section($modinfo, $cm);

        return array_merge($base, [
            'course_id' => (int) $course->id,
            'context_id' => (int) $modulecontext->id,
            'section_id' => (int) $sectioninfo->id,
            'section_number' => (int) ($sectioninfo->section ?? 0),
            'section_name' => get_section_name($course, $sectioninfo),
            'description' => self::render_description($cm, $modulecontext),
            'show_description' => (bool) ($cm->showdescription ?? false),
            'completion' => (int) ($cm->completion ?? 0),
            'completion_view' => (int) ($cm->completionview ?? 0),
            'completion_grade_item_number' => (int) ($cm->completiongradeitemnumber ?? -1),
            'completion_expected' => (int) ($cm->completionexpected ?? 0),
            'added' => (int) ($cm->added ?? 0),
            'deletion_in_progress' => (bool) ($cm->deletioninprogress ?? false),
            'extra_json' => self::encode_extra_details($cm),
        ]);
    }

    /**
     * Return the section that contains a module.
     *
     * @param \course_modinfo $modinfo Modinfo.
     * @param \cm_info $cm Cm.
     * @return \section_info
     */
    private static function get_module_section(\course_modinfo $modinfo, \cm_info $cm): \section_info {
        foreach ($modinfo->get_section_info_all() as $section) {
            $moduleids = $modinfo->sections[$section->section] ?? [];
            if (in_array((int) $cm->id, array_map('intval', $moduleids), true)) {
                return $section;
            }
        }

        throw new \moodle_exception('invalidsection');
    }

    /**
     * Render the module intro/description where Moodle exposes it through cm_info.
     *
     * @param \cm_info $cm Cm.
     * @param \context_module $context Context.
     * @return string
     */
    private static function render_description(\cm_info $cm, \context_module $context): string {
        $content = (string) ($cm->content ?? '');
        if ($content !== '') {
            return $content;
        }

        $customdata = $cm->customdata ?? [];
        if (is_object($customdata)) {
            $customdata = (array) $customdata;
        }

        $intro = (string) ($customdata['intro'] ?? '');
        if ($intro === '') {
            return '';
        }

        return format_text($intro, (int) ($customdata['introformat'] ?? FORMAT_HTML), [
            'context' => $context,
            'overflowdiv' => true,
        ]);
    }

    /**
     * Build a small JSON object with optional cm_info details.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    private static function build_extra_details(\cm_info $cm): array {
        return [
            'indent' => (int) ($cm->indent ?? 0),
            'downloadcontent' => (int) ($cm->downloadcontent ?? 0),
            'customdata' => self::normalise_custom_data($cm->customdata ?? []),
            'activity' => self::build_activity_details($cm),
        ];
    }

    /**
     * Return module-specific details where Moodle exposes safe APIs.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    private static function build_activity_details(\cm_info $cm): array {
        if ($cm->modname === 'assign') {
            $course = course_tools::get_course((int) $cm->course);
            return assignment_tools::get_assignment_details($course, $cm);
        }

        if ($cm->modname === 'page') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_page_details($course, $cm);
        }

        if ($cm->modname === 'qbank') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_qbank_details($course, $cm);
        }

        if ($cm->modname === 'label') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_label_details($course, $cm);
        }

        if ($cm->modname === 'url') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_url_details($course, $cm);
        }

        if ($cm->modname === 'book') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_book_details($course, $cm);
        }

        if ($cm->modname === 'folder') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_folder_details($course, $cm);
        }

        if ($cm->modname === 'resource') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_resource_details($course, $cm);
        }

        if ($cm->modname === 'subsection') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_subsection_details($course, $cm);
        }

        if ($cm->modname === 'quiz') {
            $course = course_tools::get_course((int) $cm->course);
            return question_tools::get_quiz_details($course, $cm);
        }

        if ($cm->modname === 'choice') {
            $course = course_tools::get_course((int) $cm->course);
            return choice_tools::get_choice_details($course, $cm);
        }

        if ($cm->modname === 'data') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_data_details($course, $cm);
        }

        if ($cm->modname === 'feedback') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_feedback_details($course, $cm);
        }

        if ($cm->modname === 'lesson') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_lesson_details($course, $cm);
        }

        if ($cm->modname === 'lti') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_lti_details($course, $cm);
        }

        if ($cm->modname === 'workshop') {
            $course = course_tools::get_course((int) $cm->course);
            return simple_activity_tools::get_workshop_details($course, $cm);
        }

        if ($cm->modname === 'forum') {
            $course = course_tools::get_course((int) $cm->course);
            return forum_tools::get_forum_details($course, $cm);
        }

        if ($cm->modname === 'glossary') {
            $course = course_tools::get_course((int) $cm->course);
            return glossary_tools::get_glossary_details($course, $cm);
        }

        if ($cm->modname === 'wiki') {
            $course = course_tools::get_course((int) $cm->course);
            return wiki_tools::get_wiki_details($course, $cm);
        }

        return [];
    }

    /**
     * Encode optional cm_info details as JSON.
     *
     * @param \cm_info $cm Cm.
     * @return string
     */
    private static function encode_extra_details(\cm_info $cm): string {
        $encoded = json_encode(self::build_extra_details($cm), JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Normalise custom data to a JSON-safe shape.
     *
     * @param mixed $customdata Customdata.
     * @return mixed
     */
    private static function normalise_custom_data($customdata) {
        if (is_object($customdata)) {
            $customdata = (array) $customdata;
        }
        if (!is_array($customdata)) {
            return $customdata;
        }

        $normalised = [];
        foreach ($customdata as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $normalised[$key] = $value;
            }
        }

        return $normalised;
    }
}
