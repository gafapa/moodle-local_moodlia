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
 * Native Moodle course restore external function.
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
use local_moodlia\operation\restore_course_backup as restore_course_backup_operation;

/**
 * External API adapter for restore_course_backup.
 */
class restore_course_backup extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'backup_file_id' => new external_value(PARAM_INT, 'Stored .mbz backup file id'),
            'target' => new external_value(PARAM_ALPHANUMEXT, 'new_course, existing_add, or existing_delete', VALUE_DEFAULT, 'new_course'),
            'target_course_id' => new external_value(PARAM_INT, 'Existing course id for existing restore targets', VALUE_DEFAULT, null, NULL_ALLOWED),
            'category_id' => new external_value(PARAM_INT, 'Category id for new course restores', VALUE_DEFAULT, null, NULL_ALLOWED),
            'fullname' => new external_value(PARAM_TEXT, 'New course fullname', VALUE_DEFAULT, ''),
            'shortname' => new external_value(PARAM_TEXT, 'New course shortname', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $backupfileid Backupfileid.
     * @param string $target Target.
     * @param int|null $targetcourseid Targetcourseid.
     * @param int|null $categoryid Categoryid.
     * @param string $fullname Fullname.
     * @param string $shortname Shortname.
     * @return array
     */
    public static function execute(
        int $backupfileid,
        string $target = 'new_course',
        ?int $targetcourseid = null,
        ?int $categoryid = null,
        string $fullname = '',
        string $shortname = ''
    ): array {
        [
            'backup_file_id' => $backupfileid,
            'target' => $target,
            'target_course_id' => $targetcourseid,
            'category_id' => $categoryid,
            'fullname' => $fullname,
            'shortname' => $shortname,
        ] = self::validate_parameters(self::execute_parameters(), [
            'backup_file_id' => $backupfileid,
            'target' => $target,
            'target_course_id' => $targetcourseid,
            'category_id' => $categoryid,
            'fullname' => $fullname,
            'shortname' => $shortname,
        ]);

        global $USER;

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $backupfile = course_backup_tools::get_backup_file((int) $backupfileid);
        $sourcecontext = \context::instance_by_id((int) $backupfile->get_contextid());
        self::validate_context($sourcecontext);

        $callerownsbackup = $sourcecontext->contextlevel === CONTEXT_USER
            && (int) $sourcecontext->instanceid === (int) $USER->id;
        if (!$callerownsbackup && $sourcecontext->contextlevel === CONTEXT_COURSE) {
            require_capability('moodle/backup:backupcourse', $sourcecontext);
        } else if (!$callerownsbackup) {
            throw new \required_capability_exception($sourcecontext, 'moodle/backup:backupcourse', 'nopermissions', '');
        }

        $target = (string) $target;
        if ($target === 'new_course') {
            $categorycontext = \context_coursecat::instance((int) $categoryid);
            self::validate_context($categorycontext);
            require_capability('moodle/course:create', $categorycontext);
            require_capability('moodle/restore:restorecourse', $categorycontext);
        } else {
            $course = course_tools::get_course((int) $targetcourseid);
            $coursecontext = \context_course::instance((int) $course->id);
            self::validate_context($coursecontext);
            require_capability('moodle/restore:restorecourse', $coursecontext);
            if ($target === 'existing_delete') {
                require_capability('moodle/course:manageactivities', $coursecontext);
            }
        }

        return restore_course_backup_operation::execute(
            (int) $backupfileid,
            $target,
            $targetcourseid === null ? 0 : (int) $targetcourseid,
            $categoryid === null ? 0 : (int) $categoryid,
            (string) $fullname,
            (string) $shortname
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Restored course id'),
            'target' => new external_value(PARAM_ALPHANUMEXT, 'Restore target'),
            'restored' => new external_value(PARAM_BOOL, 'Whether the restore completed'),
            'fullname' => new external_value(PARAM_TEXT, 'Restored course fullname'),
            'shortname' => new external_value(PARAM_TEXT, 'Restored course shortname'),
            'category_id' => new external_value(PARAM_INT, 'Restored course category id'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with non-fatal restore warnings'),
        ]);
    }
}
