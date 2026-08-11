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
 * Delete native Moodle backup file external function.
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
use local_moodlia\operation\course_backup_tools;
use local_moodlia\operation\delete_course_backup_file as delete_course_backup_file_operation;

/**
 * External API adapter for delete_course_backup_file.
 */
class delete_course_backup_file extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'file_id' => new external_value(PARAM_INT, 'Stored .mbz backup file id'),
        ]);
    }

    public static function execute(int $file_id): array {
        global $USER;

        [
            'file_id' => $fileid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'file_id' => $file_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $file = course_backup_tools::get_backup_file((int) $fileid);
        $context = \context::instance_by_id((int) $file->get_contextid());
        self::validate_context($context);

        if ($context->contextlevel === CONTEXT_USER && (int) $context->instanceid === (int) $USER->id) {
            return delete_course_backup_file_operation::execute((int) $fileid);
        }

        if ($context->contextlevel === CONTEXT_COURSE) {
            require_capability('moodle/backup:backupcourse', $context);
            return delete_course_backup_file_operation::execute((int) $fileid);
        }

        throw new \required_capability_exception($context, 'moodle/backup:backupcourse', 'nopermissions', '');
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'file_id' => new external_value(PARAM_INT, 'Deleted stored backup file id'),
            'filename' => new external_value(PARAM_FILE, 'Deleted backup filename'),
            'deleted' => new external_value(PARAM_BOOL, 'Whether the file was deleted'),
        ]);
    }
}
