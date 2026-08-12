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
 * Repair course completion external function.
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
use local_moodlia\operation\repair_course_completion as repair_course_completion_operation;

/**
 * External API adapter for repair_course_completion.
 */
class repair_course_completion extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'mode' => new external_value(PARAM_ALPHANUMEXT, 'Repair mode', VALUE_DEFAULT, 'book_view_only'),
            'dry_run' => new external_value(PARAM_BOOL, 'Only report changes without mutating Moodle', VALUE_DEFAULT, true),
            'reset_completion_states' => new external_value(PARAM_BOOL, 'Reset completion states when applying changes', VALUE_DEFAULT, true),
        ]);
    }

    public static function execute(
        int $course_id,
        string $mode = 'book_view_only',
        bool $dry_run = true,
        bool $reset_completion_states = true
    ): array {
        [
            'course_id' => $courseid,
            'mode' => $mode,
            'dry_run' => $dryrun,
            'reset_completion_states' => $resetcompletionstates,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'mode' => $mode,
            'dry_run' => $dry_run,
            'reset_completion_states' => $reset_completion_states,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance((int) $courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:manageactivities', $coursecontext);

        return repair_course_completion_operation::execute(
            (int) $courseid,
            (string) $mode,
            (bool) $dryrun,
            (bool) $resetcompletionstates
        );
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'mode' => new external_value(PARAM_ALPHANUMEXT, 'Repair mode'),
            'dry_run' => new external_value(PARAM_BOOL, 'Whether this was a dry run'),
            'changed_count' => new external_value(PARAM_INT, 'Number of matching modules'),
            'warning_count' => new external_value(PARAM_INT, 'Number of non-fatal warnings'),
            'changes_json' => new external_value(PARAM_RAW, 'JSON array with planned or applied changes'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with non-fatal warnings'),
        ]);
    }
}
