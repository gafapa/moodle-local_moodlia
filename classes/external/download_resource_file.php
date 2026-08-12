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
 * Download resource file external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\download_resource_file as download_resource_file_operation;

/**
 * External API adapter for download_resource_file.
 */
class download_resource_file extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Resource course module id'),
            'file_id' => new external_value(PARAM_INT, 'Stored file id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'path' => new external_value(PARAM_PATH, 'Filename or relative path', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Resource course module id.
     * @param int|null $file_id Stored file id.
     * @param string|null $path Filename or relative path.
     * @return array
     */
    public static function execute(int $course_id, int $module_id, ?int $file_id = null, ?string $path = null): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'file_id' => $fileid,
            'path' => $path,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'file_id' => $file_id,
            'path' => $path,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return download_resource_file_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $fileid === null ? 0 : (int) $fileid,
            $path === null ? '' : (string) $path
        );
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
            'filepath' => new external_value(PARAM_PATH, 'Stored filepath'),
            'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
            'mimetype' => new external_value(PARAM_TEXT, 'File MIME type'),
            'time_modified' => new external_value(PARAM_INT, 'Last modified timestamp'),
        ]);
    }
}
