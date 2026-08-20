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
 * Upload folder file operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Uploads a file into a Moodle folder activity through Moodle File API.
 */
class upload_folder_file {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $filename,
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_file_tools::get_folder_module($course, $moduleid);
        $context = \context_module::instance($cm->id);

        $filename = clean_param(trim($filename), PARAM_FILE);
        if ($filename === '') {
            throw new \invalid_parameter_exception('filename is required.');
        }

        $draftfile = module_file_tools::prepare_user_draft_file(
            $filename,
            $uploadreference,
            $draftitemid,
            $context,
            (int) ($course->maxbytes ?? 0)
        );

        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $existing = $fs->get_file($context->id, 'mod_folder', 'content', 0, '/', $filename);

        try {
            if ($existing && !$existing->is_directory()) {
                $existing->replace_file_with($draftfile);
                $existing->set_timemodified(time());
                $file = $existing;
            } else {
                $file = $fs->create_file_from_storedfile($filerecord, $draftfile);
            }
        } finally {
            $draftfile->delete();
        }

        rebuild_course_cache($course->id, true);

        return module_file_tools::folder_file_download_to_response($cm, $file);
    }
}
