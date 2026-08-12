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
 * Get workshop submissions external function.
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
use local_moodlia\operation\get_workshop_submissions as get_workshop_submissions_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for get_workshop_submissions.
 */
class get_workshop_submissions extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'user_id' => new external_value(PARAM_INT, 'User id or 0', VALUE_DEFAULT, 0),
            'group_id' => new external_value(PARAM_INT, 'Group id or 0', VALUE_DEFAULT, 0),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'per_page' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @return array
     */
    public static function execute(
        int $course_id,
        int $module_id,
        int $user_id = 0,
        int $group_id = 0,
        int $page = 0,
        int $per_page = 20
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'user_id' => $userid,
            'group_id' => $groupid,
            'page' => $page,
            'per_page' => $perpage,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'user_id' => $user_id,
            'group_id' => $group_id,
            'page' => $page,
            'per_page' => $per_page,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:view', $modulecontext);

        return get_workshop_submissions_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $userid,
            (int) $groupid,
            (int) $page,
            (int) $perpage
        );
    }

    /**
     * Return a workshop submission structure.
     *
     * @return external_single_structure
     */
    public static function submission_structure(): external_single_structure {
        return new external_single_structure([
            'submission_id' => new external_value(PARAM_INT, 'Workshop submission id'),
            'workshop_id' => new external_value(PARAM_INT, 'Workshop instance id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'author_id' => new external_value(PARAM_INT, 'Submission author id'),
            'title' => new external_value(PARAM_TEXT, 'Submission title'),
            'content' => new external_value(PARAM_RAW, 'Submission content'),
            'content_format' => new external_value(PARAM_ALPHA, 'Content format'),
            'grade' => new external_value(PARAM_FLOAT, 'Submission grade'),
            'grade_over' => new external_value(PARAM_FLOAT, 'Overridden grade'),
            'grade_over_by' => new external_value(PARAM_INT, 'User id that overrode the grade'),
            'published' => new external_value(PARAM_BOOL, 'Whether the submission is published'),
            'late' => new external_value(PARAM_BOOL, 'Whether the submission is late'),
            'time_created' => new external_value(PARAM_INT, 'Submission creation time'),
            'time_modified' => new external_value(PARAM_INT, 'Submission modification time'),
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
            'count' => new external_value(PARAM_INT, 'Returned submission count'),
            'submissions' => new external_multiple_structure(self::submission_structure()),
        ]);
    }
}
