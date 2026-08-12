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
 * Native Moodle course backup external function.
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
use local_moodlia\operation\backup_course as backup_course_operation;

/**
 * External API adapter for backup_course.
 */
class backup_course extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'filename' => new external_value(PARAM_FILE, 'Optional .mbz filename', VALUE_DEFAULT, ''),
            'include_users' => new external_value(PARAM_BOOL, 'Include enrolled users and user data', VALUE_DEFAULT, false),
            'include_activities' => new external_value(PARAM_BOOL, 'Include activities', VALUE_DEFAULT, true),
            'include_blocks' => new external_value(PARAM_BOOL, 'Include blocks', VALUE_DEFAULT, true),
            'include_filters' => new external_value(PARAM_BOOL, 'Include filters', VALUE_DEFAULT, true),
            'include_comments' => new external_value(PARAM_BOOL, 'Include comments', VALUE_DEFAULT, false),
            'include_logs' => new external_value(PARAM_BOOL, 'Include logs', VALUE_DEFAULT, false),
            'include_grade_histories' => new external_value(PARAM_BOOL, 'Include grade histories', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute(
        int $course_id,
        string $filename = '',
        bool $include_users = false,
        bool $include_activities = true,
        bool $include_blocks = true,
        bool $include_filters = true,
        bool $include_comments = false,
        bool $include_logs = false,
        bool $include_grade_histories = false
    ): array {
        [
            'course_id' => $courseid,
            'filename' => $filename,
            'include_users' => $includeusers,
            'include_activities' => $includeactivities,
            'include_blocks' => $includeblocks,
            'include_filters' => $includefilters,
            'include_comments' => $includecomments,
            'include_logs' => $includelogs,
            'include_grade_histories' => $includegradehistories,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'filename' => $filename,
            'include_users' => $include_users,
            'include_activities' => $include_activities,
            'include_blocks' => $include_blocks,
            'include_filters' => $include_filters,
            'include_comments' => $include_comments,
            'include_logs' => $include_logs,
            'include_grade_histories' => $include_grade_histories,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance((int) $courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/backup:backupcourse', $coursecontext);

        return backup_course_operation::execute(
            (int) $courseid,
            (string) $filename,
            (bool) $includeusers,
            (bool) $includeactivities,
            (bool) $includeblocks,
            (bool) $includefilters,
            (bool) $includecomments,
            (bool) $includelogs,
            (bool) $includegradehistories
        );
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Source course id'),
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
