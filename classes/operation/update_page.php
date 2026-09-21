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
 * Update Page content operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Page without recreating its course module identity.
 */
class update_page {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string|null $name Name.
     * @param string|null $content Content.
     * @param string|null $contentformat Contentformat.
     * @param bool|null $printintro Printintro.
     * @param bool|null $printlastmodified Printlastmodified.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $name = null,
        ?string $content = null,
        ?string $contentformat = null,
        ?bool $printintro = null,
        ?bool $printlastmodified = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        global $CFG;

        module_tools::require_module_api();
        require_once($CFG->dirroot . '/course/modlib.php');

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);
        if ($cm->modname !== 'page') {
            throw new \invalid_parameter_exception('module_id must reference a Page activity.');
        }
        $hasupload = trim($uploadreference) !== '' || $draftitemid > 0;
        if ($hasupload && trim($filename) === '') {
            throw new \invalid_parameter_exception('filename is required when an upload is provided.');
        }
        if (!$hasupload && trim($filename) !== '') {
            throw new \invalid_parameter_exception('filename requires upload_reference or draft_item_id.');
        }
        if ($contentformat !== null && $content === null) {
            throw new \invalid_parameter_exception('content is required when content_format is provided.');
        }
        if ($name === null && $content === null && $printintro === null && $printlastmodified === null && !$hasupload) {
            throw new \invalid_parameter_exception('At least one Page field or upload is required.');
        }

        $rawcm = get_coursemodule_from_id('page', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $moduleinfo = get_moduleinfo_data($rawcm, $course);
        $rawcm = $moduleinfo[0];
        $moduledata = $moduleinfo[3];
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty when provided.');
            }
            $moduledata->name = $name;
        }
        if ($content !== null) {
            $moduledata->content_editor['text'] = $content;
            $moduledata->content_editor['format'] = $contentformat === null
                ? (int) ($moduledata->contentformat ?? FORMAT_HTML)
                : course_tools::format_to_constant($contentformat);
        }
        if ($printintro !== null) {
            $moduledata->printintro = $printintro ? 1 : 0;
        }
        if ($printlastmodified !== null) {
            $moduledata->printlastmodified = $printlastmodified ? 1 : 0;
        }
        if ($hasupload) {
            $context = \context_module::instance((int) $cm->id);
            $editor = module_file_tools::prepare_editor_draft(
                $context,
                'mod_page',
                'content',
                0,
                (string) ($moduledata->content_editor['text'] ?? ''),
                $filename,
                $uploadreference,
                $draftitemid,
                (int) ($course->maxbytes ?? 0)
            );
            $moduledata->content_editor['text'] = $editor['content'];
            $moduledata->content_editor['itemid'] = $editor['draft_item_id'];
        }

        update_moduleinfo($rawcm, $moduledata, $course);
        rebuild_course_cache((int) $course->id, true);
        $updatedcm = module_tools::get_course_module($course, $moduleid);
        $details = simple_activity_tools::get_page_details($course, $updatedcm);
        $response = module_tools::to_response($course, (int) $updatedcm->id);

        return [
            'module_id' => (int) $response['module_id'],
            'instance_id' => (int) $response['instance_id'],
            'name' => (string) $response['name'],
            'content' => (string) $details['content'],
            'content_format' => (int) $details['content_format'],
            'files' => $details['files'],
        ];
    }
}
