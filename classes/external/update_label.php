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
 * Update Text and media external function.
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
use local_moodlia\operation\update_label as update_label_operation;

/**
 * External API adapter for update_label.
 */
class update_label extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Text and media course module id'),
            'content' => new external_value(PARAM_RAW, 'Optional authored content', VALUE_DEFAULT, null, NULL_ALLOWED),
            'content_format' => new external_value(
                PARAM_ALPHA,
                'Content format: html or plain',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
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
     * @param string|null $content Content.
     * @param string|null $contentformat Contentformat.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $content = null,
        ?string $contentformat = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'content' => $content,
            'content_format' => $contentformat,
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

        return update_label_operation::execute(
            (int) $params['course_id'],
            (int) $params['module_id'],
            $params['content'],
            $params['content_format'],
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
            'instance_id' => new external_value(PARAM_INT, 'Text and media instance id'),
            'content' => new external_value(PARAM_RAW, 'Authored content'),
            'content_format' => new external_value(PARAM_INT, 'Moodle content format'),
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
