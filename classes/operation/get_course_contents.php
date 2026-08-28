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
 * Course contents operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists Moodle course sections and modules through Moodle course APIs.
 */
class get_course_contents {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @return array
     */
    public static function execute(int $courseid): array {
        $course = section_tools::get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $sections = [];

        foreach ($modinfo->get_section_info_all() as $section) {
            $modules = [];
            $sectionmoduleids = $modinfo->sections[$section->section] ?? [];

            foreach ($sectionmoduleids as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                $modulecontext = \context_module::instance($cm->id);
                $modules[] = [
                    'module_id' => (int) $cm->id,
                    'course_module_id' => (int) $cm->id,
                    'instance_id' => (int) $cm->instance,
                    'name' => format_string($cm->name, true, ['context' => $modulecontext]),
                    'module_type' => (string) $cm->modname,
                    'visible' => (bool) $cm->visible,
                    'visible_on_course_page' => module_tools::is_visible_on_course_page($cm),
                    'user_visible' => (bool) $cm->uservisible,
                    'completion' => (int) ($cm->completion ?? 0),
                    'completion_view' => (int) ($cm->completionview ?? 0),
                    'completion_grade_item_number' => (int) ($cm->completiongradeitemnumber ?? -1),
                    'completion_expected' => (int) ($cm->completionexpected ?? 0),
                    'url' => $cm->url ? $cm->url->out(false) : '',
                ];
            }

            $sections[] = [
                'section_id' => (int) $section->id,
                'course_id' => (int) $course->id,
                'section_number' => (int) $section->section,
                'name' => get_section_name($course, $section),
                'summary' => section_tools::render_summary($course, $section),
                'visible' => (bool) $section->visible,
                'modules' => $modules,
            ];
        }

        return [
            'course_id' => (int) $course->id,
            'sections' => $sections,
        ];
    }
}
