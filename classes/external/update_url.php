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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Update URL resource external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\course_tools;
use local_moodlia\operation\module_tools;
use local_moodlia\operation\update_url as update_url_operation;

/**
 * External API adapter for update_url.
 */
class update_url extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'URL course module id'),
            'name' => new external_value(PARAM_TEXT, 'Optional URL name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'external_url' => new external_value(PARAM_URL, 'Optional absolute URL', VALUE_DEFAULT, null, NULL_ALLOWED),
            'intro' => new external_value(PARAM_RAW, 'Optional description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'intro_format' => new external_value(
                PARAM_ALPHA,
                'Description format: html or plain',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'display' => new external_value(PARAM_INT, 'Optional Moodle display constant', VALUE_DEFAULT, null, NULL_ALLOWED),
            'print_intro' => new external_value(PARAM_BOOL, 'Print description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'popup_width' => new external_value(PARAM_INT, 'Popup width', VALUE_DEFAULT, null, NULL_ALLOWED),
            'popup_height' => new external_value(PARAM_INT, 'Popup height', VALUE_DEFAULT, null, NULL_ALLOWED),
            'filename' => new external_value(PARAM_FILE, 'Uploaded editor filename', VALUE_DEFAULT, ''),
            'upload_reference' => new external_value(PARAM_RAW, 'Base64 editor file content', VALUE_DEFAULT, ''),
            'draft_item_id' => new external_value(PARAM_INT, 'Moodle user draft item id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string|null $name Name.
     * @param string|null $externalurl Externalurl.
     * @param string|null $intro Intro.
     * @param string|null $introformat Introformat.
     * @param int|null $display Display.
     * @param bool|null $printintro Printintro.
     * @param int|null $popupwidth Popupwidth.
     * @param int|null $popupheight Popupheight.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $name = null,
        ?string $externalurl = null,
        ?string $intro = null,
        ?string $introformat = null,
        ?int $display = null,
        ?bool $printintro = null,
        ?int $popupwidth = null,
        ?int $popupheight = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'name' => $name,
            'external_url' => $externalurl,
            'intro' => $intro,
            'intro_format' => $introformat,
            'display' => $display,
            'print_intro' => $printintro,
            'popup_width' => $popupwidth,
            'popup_height' => $popupheight,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
            'draft_item_id' => $draftitemid,
        ]);
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        $course = course_tools::get_course((int) $params['course_id']);
        $cm = module_tools::get_course_module($course, (int) $params['module_id']);
        $context = \context_module::instance((int) $cm->id);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);
        if ($params['draft_item_id'] > 0 || trim($params['upload_reference']) !== '') {
            require_capability('moodle/course:managefiles', $context);
        }

        return update_url_operation::execute(
            (int) $params['course_id'],
            (int) $params['module_id'],
            $params['name'],
            $params['external_url'],
            $params['intro'],
            $params['intro_format'],
            $params['display'],
            $params['print_intro'],
            $params['popup_width'],
            $params['popup_height'],
            $params['filename'],
            $params['upload_reference'],
            (int) $params['draft_item_id']
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Moodle course module id'),
            'instance_id' => new external_value(PARAM_INT, 'URL instance id'),
            'name' => new external_value(PARAM_TEXT, 'URL name'),
            'external_url' => new external_value(PARAM_URL, 'External URL'),
            'intro' => new external_value(PARAM_RAW, 'Description'),
            'intro_format' => new external_value(PARAM_INT, 'Moodle description format'),
            'display' => new external_value(PARAM_INT, 'Moodle display constant'),
            'print_intro' => new external_value(PARAM_BOOL, 'Print description'),
            'popup_width' => new external_value(PARAM_INT, 'Popup width'),
            'popup_height' => new external_value(PARAM_INT, 'Popup height'),
            'files' => self::files_structure(),
        ]);
    }

    /**
     * Return the editor file manifest structure.
     *
     * @return external_multiple_structure
     */
    private static function files_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'file_id' => new external_value(PARAM_INT, 'Stored file id'),
            'filename' => new external_value(PARAM_FILE, 'Stored filename'),
            'url' => new external_value(PARAM_URL, 'File URL'),
            'filepath' => new external_value(PARAM_PATH, 'Stored filepath'),
            'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
            'mimetype' => new external_value(PARAM_RAW, 'File MIME type'),
            'content_hash' => new external_value(PARAM_ALPHANUM, 'Moodle content hash'),
            'time_modified' => new external_value(PARAM_INT, 'Last modified timestamp'),
        ]));
    }
}
