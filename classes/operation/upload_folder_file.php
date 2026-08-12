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

defined('MOODLE_INTERNAL') || die();

/**
 * Uploads a file into a Moodle folder activity through Moodle File API.
 */
class upload_folder_file {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Folder course module id.
     * @param string $filename Target filename.
     * @param string $uploadreference Base64-encoded file content.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $filename, string $uploadreference): array {
        global $USER;

        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_file_tools::get_folder_module($course, $moduleid);
        $context = \context_module::instance($cm->id);

        $filename = clean_param(trim($filename), PARAM_FILE);
        if ($filename === '') {
            throw new \invalid_parameter_exception('filename is required.');
        }

        $content = base64_decode($uploadreference, true);
        if ($content === false) {
            throw new \invalid_parameter_exception('upload_reference must be base64-encoded file content.');
        }

        $maxbytes = 2 * 1024 * 1024;
        if (strlen($content) > $maxbytes) {
            throw new \invalid_parameter_exception('File content exceeds the 2 MB API limit.');
        }

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

        if ($existing && !$existing->is_directory()) {
            $draftitemid = file_get_unused_draft_itemid();
            $draftfile = $fs->create_file_from_string([
                'contextid' => \context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => $filename,
            ], $content);

            try {
                $existing->replace_file_with($draftfile);
                $existing->set_timemodified(time());
                $file = $existing;
            } finally {
                $draftfile->delete();
            }
        } else {
            $file = $fs->create_file_from_string($filerecord, $content);
        }

        rebuild_course_cache($course->id, true);

        return module_file_tools::folder_file_download_to_response($cm, $file);
    }
}
