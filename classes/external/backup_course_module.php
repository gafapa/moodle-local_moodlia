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
 * Native Moodle single-activity backup external function.
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
use local_moodlia\operation\backup_course_module as backup_course_module_operation;
use local_moodlia\operation\course_tools;
use local_moodlia\operation\module_tools;

/**
 * External API adapter for backup_course_module.
 */
class backup_course_module extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Course module id to back up'),
            'filename' => new external_value(PARAM_FILE, 'Optional .mbz filename', VALUE_DEFAULT, ''),
            'include_users' => new external_value(PARAM_BOOL, 'Include user data', VALUE_DEFAULT, false),
            'include_blocks' => new external_value(PARAM_BOOL, 'Include blocks', VALUE_DEFAULT, true),
            'include_filters' => new external_value(PARAM_BOOL, 'Include filters', VALUE_DEFAULT, true),
            'include_comments' => new external_value(PARAM_BOOL, 'Include comments', VALUE_DEFAULT, false),
            'include_logs' => new external_value(PARAM_BOOL, 'Include logs', VALUE_DEFAULT, false),
            'include_grade_histories' => new external_value(PARAM_BOOL, 'Include grade histories', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $filename Filename.
     * @param bool $includeusers Includeusers.
     * @param bool $includeblocks Includeblocks.
     * @param bool $includefilters Includefilters.
     * @param bool $includecomments Includecomments.
     * @param bool $includelogs Includelogs.
     * @param bool $includegradehistories Includegradehistories.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $filename = '',
        bool $includeusers = false,
        bool $includeblocks = true,
        bool $includefilters = true,
        bool $includecomments = false,
        bool $includelogs = false,
        bool $includegradehistories = false
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'filename' => $filename,
            'include_users' => $includeusers,
            'include_blocks' => $includeblocks,
            'include_filters' => $includefilters,
            'include_comments' => $includecomments,
            'include_logs' => $includelogs,
            'include_grade_histories' => $includegradehistories,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'filename' => $filename,
            'include_users' => $includeusers,
            'include_blocks' => $includeblocks,
            'include_filters' => $includefilters,
            'include_comments' => $includecomments,
            'include_logs' => $includelogs,
            'include_grade_histories' => $includegradehistories,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        module_tools::require_module_api();
        $course = course_tools::get_course((int) $courseid);
        $cm = module_tools::get_course_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance((int) $cm->id);
        self::validate_context($modulecontext);
        require_capability('moodle/backup:backupactivity', $modulecontext);

        return backup_course_module_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (string) $filename,
            (bool) $includeusers,
            (bool) $includeblocks,
            (bool) $includefilters,
            (bool) $includecomments,
            (bool) $includelogs,
            (bool) $includegradehistories
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Source course id'),
            'module_id' => new external_value(PARAM_INT, 'Backed up course module id'),
            'module_name' => new external_value(PARAM_PLUGIN, 'Module type, for example assign'),
            'section_number' => new external_value(PARAM_INT, 'Section number that contains the module'),
            'file_id' => new external_value(PARAM_INT, 'Stored backup file id'),
            'filename' => new external_value(PARAM_FILE, 'Backup filename'),
            'url' => new external_value(PARAM_URL, 'Moodle pluginfile URL'),
            'filepath' => new external_value(PARAM_PATH, 'Stored filepath'),
            'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
            'mimetype' => new external_value(PARAM_TEXT, 'File MIME type'),
            'time_modified' => new external_value(PARAM_INT, 'Last modified timestamp'),
        ]);
    }
}
