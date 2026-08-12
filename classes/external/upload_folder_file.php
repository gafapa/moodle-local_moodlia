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
 * Upload folder file external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\upload_folder_file as upload_folder_file_operation;

/**
 * External API adapter for upload_folder_file.
 */
class upload_folder_file extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Folder course module id'),
            'filename' => new external_value(PARAM_FILE, 'Target filename'),
            'upload_reference' => new external_value(PARAM_RAW, 'Base64-encoded file content'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Folder course module id.
     * @param string $filename Target filename.
     * @param string $uploadreference Base64-encoded file content.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $filename, string $uploadreference): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:managefiles', $coursecontext);
        require_capability('moodle/course:manageactivities', $coursecontext);

        return upload_folder_file_operation::execute((int) $courseid, (int) $moduleid, $filename, $uploadreference);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'file_id' => new external_value(PARAM_INT, 'Stored file id'),
            'filename' => new external_value(PARAM_FILE, 'Stored filename'),
            'url' => new external_value(PARAM_URL, 'File URL'),
        ]);
    }
}
