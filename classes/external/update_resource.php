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
 * Update file resource external function.
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
use local_moodlia\operation\module_file_tools;
use local_moodlia\operation\update_resource as update_resource_operation;

/**
 * External API adapter for update_resource.
 */
class update_resource extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Resource course module id'),
            'filename' => new external_value(PARAM_FILE, 'Replacement filename'),
            'upload_reference' => new external_value(PARAM_RAW, 'Base64 replacement file content', VALUE_DEFAULT, ''),
            'draft_item_id' => new external_value(PARAM_INT, 'Moodle user draft item id', VALUE_DEFAULT, 0),
            'name' => new external_value(PARAM_TEXT, 'Optional resource name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'intro' => new external_value(PARAM_RAW, 'Optional resource description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'intro_format' => new external_value(PARAM_ALPHA, 'Description format: html or plain', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
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
        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
            'draft_item_id' => $draftitemid,
            'name' => $name,
            'intro' => $intro,
            'intro_format' => $introformat,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = course_tools::get_course((int) $params['course_id']);
        $cm = module_file_tools::get_resource_module($course, (int) $params['module_id']);
        $modulecontext = \context_module::instance((int) $cm->id);
        self::validate_context($modulecontext);
        require_capability('moodle/course:manageactivities', $modulecontext);
        require_capability('moodle/course:managefiles', $modulecontext);

        return update_resource_operation::execute(
            (int) $params['course_id'],
            (int) $params['module_id'],
            $params['filename'],
            $params['upload_reference'],
            (int) $params['draft_item_id'],
            $params['name'],
            $params['intro'],
            $params['intro_format']
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'MoodlIA module id alias'),
            'course_module_id' => new external_value(PARAM_INT, 'Moodle course module id'),
            'instance_id' => new external_value(PARAM_INT, 'Resource instance id'),
            'name' => new external_value(PARAM_TEXT, 'Resource name'),
            'files' => new external_multiple_structure(new external_single_structure([
                'file_id' => new external_value(PARAM_INT, 'Stored file id'),
                'filename' => new external_value(PARAM_FILE, 'Stored filename'),
                'url' => new external_value(PARAM_URL, 'File URL'),
                'filepath' => new external_value(PARAM_PATH, 'Stored filepath'),
                'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
                'mimetype' => new external_value(PARAM_RAW, 'File MIME type'),
                'time_modified' => new external_value(PARAM_INT, 'Last modified timestamp'),
            ])),
        ]);
    }
}
