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
 * Update file resource operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Replaces a file resource through Moodle's module update API.
 */
class update_resource {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @param string|null $name Name.
     * @param string|null $intro Intro.
     * @param string|null $introformat Introformat.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $filename,
        string $uploadreference = '',
        int $draftitemid = 0,
        ?string $name = null,
        ?string $intro = null,
        ?string $introformat = null
    ): array {
        global $CFG;

        module_tools::require_module_api();
        require_once($CFG->dirroot . '/course/modlib.php');

        $course = course_tools::get_course($courseid);
        $cm = module_file_tools::get_resource_module($course, $moduleid);

        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty when provided.');
            }
        }
        if ($intro === null && $introformat !== null) {
            throw new \invalid_parameter_exception('intro is required when intro_format is provided.');
        }

        $draftfile = module_file_tools::prepare_user_draft_file(
            $filename,
            $uploadreference,
            $draftitemid,
            \context_module::instance((int) $cm->id),
            (int) ($course->maxbytes ?? 0)
        );

        $rawcm = get_coursemodule_from_id('resource', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $moduleinfo = get_moduleinfo_data($rawcm, $course);
        $rawcm = $moduleinfo[0];
        $moduledata = $moduleinfo[3];
        $moduledata->files = (int) $draftfile->get_itemid();

        if ($name !== null) {
            $moduledata->name = $name;
        }
        if ($intro !== null) {
            $moduledata->introeditor['text'] = $intro;
            $moduledata->introeditor['format'] = $introformat === null
                ? (int) ($moduledata->introformat ?? FORMAT_HTML)
                : course_tools::format_to_constant($introformat);
        }

        update_moduleinfo($rawcm, $moduledata, $course);
        rebuild_course_cache((int) $course->id, true);

        $updatedcm = module_file_tools::get_resource_module($course, $moduleid);
        $response = module_tools::to_response($course, (int) $updatedcm->id);

        return [
            'module_id' => (int) $response['module_id'],
            'course_module_id' => (int) $response['course_module_id'],
            'instance_id' => (int) $response['instance_id'],
            'name' => (string) $response['name'],
            'files' => module_file_tools::get_resource_files($updatedcm),
        ];
    }
}
