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
 * Create module operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course module through Moodle core APIs.
 */
class create_module {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $sectionnumber Course section number.
     * @param string $moduletype Module type.
     * @param string $name Module name.
     * @param array $options Type-specific options.
     * @return array
     */
    public static function execute(int $courseid, int $sectionnumber, string $moduletype, string $name, array $options = []): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        section_tools::get_section($course, null, $sectionnumber);

        $moduletype = clean_param($moduletype, PARAM_PLUGIN);
        $supportedtypes = [
            'assign',
            'book',
            'choice',
            'data',
            'feedback',
            'lesson',
            'lti',
            'page',
            'folder',
            'forum',
            'glossary',
            'label',
            'qbank',
            'quiz',
            'resource',
            'subsection',
            'url',
            'wiki',
            'workshop',
        ];
        if (!in_array($moduletype, $supportedtypes, true)) {
            throw new \invalid_parameter_exception(
                'Only these module_type values are currently supported: ' .
                    implode(', ', $supportedtypes) . '.'
            );
        }
        if ($moduletype === 'qbank' && $sectionnumber !== 0) {
            throw new \invalid_parameter_exception(
                'module_type=qbank must be created in section_number=0 because Moodle does not display ' .
                    'question bank modules as course-section activities.'
            );
        }

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        if ($moduletype === 'quiz' || $moduletype === 'lti' || $moduletype === 'qbank' || $moduletype === 'subsection') {
            $prepared = prepare_new_moduleinfo_data($course, $moduletype, $sectionnumber);
            $moduleinfo = $prepared[4];
            $moduleinfo->name = $name;
            $moduleinfo->intro = (string) ($options['intro'] ?? '');
            $moduleinfo->introformat = FORMAT_HTML;
            $moduleinfo->visible = 1;
            $moduleinfo->visibleoncoursepage = 1;
            $moduleinfo->showdescription = 0;
        } else {
            $moduleinfo = (object) [
                'modulename' => $moduletype,
                'module' => module_tools::resolve_content_item_id($course, $moduletype),
                'section' => $sectionnumber,
                'name' => $name,
                'intro' => (string) ($options['intro'] ?? ''),
                'introformat' => FORMAT_HTML,
                'visible' => 1,
                'visibleoncoursepage' => 1,
                'showdescription' => 0,
            ];
        }

        if ($moduletype === 'assign') {
            module_tools::apply_assign_options($moduleinfo, $options);
        } else if ($moduletype === 'book') {
            module_tools::apply_book_options($moduleinfo, $options);
        } else if ($moduletype === 'choice') {
            module_tools::apply_choice_options($moduleinfo, $options);
        } else if ($moduletype === 'data') {
            module_tools::apply_data_options($moduleinfo, $options);
        } else if ($moduletype === 'feedback') {
            module_tools::apply_feedback_options($moduleinfo, $options);
        } else if ($moduletype === 'lesson') {
            module_tools::apply_lesson_options($moduleinfo, $options);
        } else if ($moduletype === 'lti') {
            module_tools::apply_lti_options($moduleinfo, $options);
        } else if ($moduletype === 'page') {
            module_tools::apply_page_options($moduleinfo, $options);
        } else if ($moduletype === 'qbank') {
            module_tools::apply_qbank_options($moduleinfo, $options);
        } else if ($moduletype === 'folder') {
            module_tools::apply_folder_options($moduleinfo, $options);
        } else if ($moduletype === 'forum') {
            module_tools::apply_forum_options($moduleinfo, $options);
        } else if ($moduletype === 'glossary') {
            module_tools::apply_glossary_options($moduleinfo, $options);
        } else if ($moduletype === 'label') {
            module_tools::apply_label_options($moduleinfo, $options);
        } else if ($moduletype === 'resource') {
            module_tools::apply_resource_options($moduleinfo, $options);
        } else if ($moduletype === 'subsection') {
            module_tools::apply_subsection_options($moduleinfo, $options);
        } else if ($moduletype === 'url') {
            module_tools::apply_url_options($moduleinfo, $options);
        } else if ($moduletype === 'wiki') {
            module_tools::apply_wiki_options($moduleinfo, $options);
        } else if ($moduletype === 'workshop') {
            module_tools::apply_workshop_options($moduleinfo, $options);
        } else {
            module_tools::apply_quiz_options($moduleinfo, $options);
        }

        module_tools::apply_common_options($course, $moduleinfo, $options);

        $created = add_moduleinfo($moduleinfo, $course);
        $createdcmid = (int) $created->coursemodule;

        set_coursemodule_visible(
            $createdcmid,
            (int) ($moduleinfo->visible ?? 1),
            (int) ($moduleinfo->visibleoncoursepage ?? 1)
        );
        rebuild_course_cache($course->id, true);

        return module_tools::to_response($course, $createdcmid);
    }
}
