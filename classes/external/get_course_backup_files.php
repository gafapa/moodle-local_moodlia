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
 * List native Moodle backup files external function.
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
use local_moodlia\operation\course_tools;
use local_moodlia\operation\get_course_backup_files as get_course_backup_files_operation;

/**
 * External API adapter for get_course_backup_files.
 */
class get_course_backup_files extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Optional Moodle course id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'include_private' => new external_value(PARAM_BOOL, 'Include current user private .mbz files', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int|null $courseid Courseid.
     * @param bool $includeprivate Includeprivate.
     * @return array
     */
    public static function execute(?int $courseid = null, bool $includeprivate = true): array {
        [
            'course_id' => $courseid,
            'include_private' => $includeprivate,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'include_private' => $includeprivate,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        if (!empty($courseid)) {
            $course = course_tools::get_course((int) $courseid);
            $coursecontext = \context_course::instance((int) $course->id);
            self::validate_context($coursecontext);
            require_capability('moodle/backup:backupcourse', $coursecontext);
        }

        return get_course_backup_files_operation::execute($courseid === null ? 0 : (int) $courseid, (bool) $includeprivate);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id, or 0 when only private files were listed'),
            'count' => new external_value(PARAM_INT, 'Number of backup files'),
            'files_json' => new external_value(PARAM_RAW, 'JSON array with backup file metadata'),
        ]);
    }
}
