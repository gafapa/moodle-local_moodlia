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
 * Get workshop user plan external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_workshop_user_plan as get_workshop_user_plan_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for get_workshop_user_plan.
 */
class get_workshop_user_plan extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'user_id' => new external_value(PARAM_INT, 'User id or 0 for the current user', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @return array
     */
    public static function execute(int $course_id, int $module_id, int $user_id = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'user_id' => $user_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:view', $modulecontext);

        return get_workshop_user_plan_operation::execute((int) $courseid, (int) $moduleid, (int) $userid);
    }

    /**
     * Return a user-plan task structure.
     *
     * @return external_single_structure
     */
    private static function task_structure(): external_single_structure {
        return new external_single_structure([
            'code' => new external_value(PARAM_ALPHANUMEXT, 'Task code'),
            'title' => new external_value(PARAM_RAW, 'Task title'),
            'link' => new external_value(PARAM_RAW, 'Task URL'),
            'details' => new external_value(PARAM_RAW, 'Task details'),
            'completed' => new external_value(PARAM_RAW, 'Completion state'),
        ]);
    }

    /**
     * Return a user-plan action structure.
     *
     * @return external_single_structure
     */
    private static function action_structure(): external_single_structure {
        return new external_single_structure([
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Action type'),
            'label' => new external_value(PARAM_RAW, 'Action label'),
            'url' => new external_value(PARAM_RAW, 'Action URL'),
            'method' => new external_value(PARAM_ALPHANUMEXT, 'HTTP method'),
        ]);
    }

    /**
     * Return a user-plan phase structure.
     *
     * @return external_single_structure
     */
    private static function phase_structure(): external_single_structure {
        return new external_single_structure([
            'code' => new external_value(PARAM_INT, 'Workshop phase code'),
            'title' => new external_value(PARAM_RAW, 'Workshop phase title'),
            'phase' => new external_value(PARAM_ALPHA, 'Canonical phase name'),
            'active' => new external_value(PARAM_BOOL, 'Whether this is the active phase'),
            'task_count' => new external_value(PARAM_INT, 'Returned task count'),
            'tasks' => new external_multiple_structure(self::task_structure()),
            'action_count' => new external_value(PARAM_INT, 'Returned action count'),
            'actions' => new external_multiple_structure(self::action_structure()),
        ]);
    }

    /**
     * Return a workshop example submission structure.
     *
     * @return external_single_structure
     */
    private static function example_structure(): external_single_structure {
        return new external_single_structure([
            'submission_id' => new external_value(PARAM_INT, 'Example submission id'),
            'title' => new external_value(PARAM_RAW, 'Example title'),
            'assessment_id' => new external_value(PARAM_INT, 'Example assessment id'),
            'grade' => new external_value(PARAM_FLOAT, 'Submission grade'),
            'grading_grade' => new external_value(PARAM_FLOAT, 'Assessment grade'),
        ]);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'workshop_id' => new external_value(PARAM_INT, 'Workshop instance id'),
            'user_id' => new external_value(PARAM_INT, 'User id'),
            'phase_count' => new external_value(PARAM_INT, 'Returned phase count'),
            'phases' => new external_multiple_structure(self::phase_structure()),
            'example_count' => new external_value(PARAM_INT, 'Returned example count'),
            'examples' => new external_multiple_structure(self::example_structure()),
        ]);
    }
}
