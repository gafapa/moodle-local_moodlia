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
 * Native Moodle activity or section backup import external function.
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
use local_moodlia\operation\course_backup_tools;
use local_moodlia\operation\course_tools;
use local_moodlia\operation\restore_course_item_backup as restore_course_item_backup_operation;

/**
 * External API adapter for restore_course_item_backup.
 */
class restore_course_item_backup extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'backup_file_id' => new external_value(PARAM_INT, 'Stored activity or section .mbz backup file id'),
            'target_course_id' => new external_value(PARAM_INT, 'Existing course that receives the restored item'),
            'target_section_number' => new external_value(
                PARAM_INT,
                'Section that receives a restored activity; defaults to its original section number',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $backupfileid Backupfileid.
     * @param int $targetcourseid Targetcourseid.
     * @param int|null $targetsectionnumber Targetsectionnumber.
     * @return array
     */
    public static function execute(int $backupfileid, int $targetcourseid, ?int $targetsectionnumber = null): array {
        [
            'backup_file_id' => $backupfileid,
            'target_course_id' => $targetcourseid,
            'target_section_number' => $targetsectionnumber,
        ] = self::validate_parameters(self::execute_parameters(), [
            'backup_file_id' => $backupfileid,
            'target_course_id' => $targetcourseid,
            'target_section_number' => $targetsectionnumber,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $backupfile = course_backup_tools::get_backup_file((int) $backupfileid);
        $sourcecontext = \context::instance_by_id((int) $backupfile->get_contextid());
        self::validate_context($sourcecontext);
        course_backup_tools::require_backup_file_access($backupfile);

        $course = course_tools::get_course((int) $targetcourseid);
        $coursecontext = \context_course::instance((int) $course->id);
        self::validate_context($coursecontext);
        require_capability('moodle/course:manageactivities', $coursecontext);

        // Moodle's restore controller also requires moodle/restore:restoreactivity or
        // moodle/restore:restoresection for the backup type and fails with restore_user_missing_capability.
        return restore_course_item_backup_operation::execute(
            (int) $backupfileid,
            (int) $targetcourseid,
            $targetsectionnumber === null ? null : (int) $targetsectionnumber
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Target course id'),
            'backup_type' => new external_value(PARAM_ALPHA, 'activity or section'),
            'restored' => new external_value(PARAM_BOOL, 'Whether the restore completed'),
            'modules_json' => new external_value(PARAM_RAW, 'JSON array with the restored course modules'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with non-fatal restore warnings'),
        ]);
    }
}
