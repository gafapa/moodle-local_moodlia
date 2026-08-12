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
 * Set activity completion status external function.
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
use local_moodlia\operation\set_activity_completion_status as set_activity_completion_status_operation;

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * External API adapter for set_activity_completion_status.
 */
class set_activity_completion_status extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'module_id' => new external_value(PARAM_INT, 'Moodle course module id'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the activity should be marked complete'),
        ]);
    }

    public static function execute(int $module_id, bool $completed): array {
        [
            'module_id' => $moduleid,
            'completed' => $completedstate,
        ] = self::validate_parameters(self::execute_parameters(), [
            'module_id' => $module_id,
            'completed' => $completed,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $cmrecord = get_coursemodule_from_id('', (int) $moduleid, 0, false, MUST_EXIST);
        $course = get_course((int) $cmrecord->course);
        $cm = get_fast_modinfo($course)->get_cm((int) $moduleid);
        $modulecontext = \context_module::instance((int) $cm->id);
        self::validate_context($modulecontext);
        require_login($course, false, $cm);

        return set_activity_completion_status_operation::execute((int) $moduleid, (bool) $completedstate);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Course module id'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the activity is marked complete'),
            'warnings' => get_course_completion_status::warnings_structure(),
        ]);
    }
}
