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
 * MoodlIA plugin implementation.
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
use local_moodlia\operation\apply_course_blueprint as apply_course_blueprint_operation;
use local_moodlia\operation\course_workflow_tools;

/**
 * External API adapter for apply_course_blueprint.
 */
class apply_course_blueprint extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Target Moodle course id'),
            'blueprint' => new external_value(PARAM_RAW, 'JSON-encoded MoodlIA course blueprint'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $blueprint Blueprint.
     * @return array
     */
    public static function execute(int $courseid, string $blueprint): array {
        [
            'course_id' => $courseid,
            'blueprint' => $blueprint,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'blueprint' => $blueprint,
        ]);

        $decoded = course_workflow_tools::decode_object($blueprint, 'blueprint');
        self::validate_course_write_context((int) $courseid, $decoded);

        return apply_course_blueprint_operation::execute(
            (int) $courseid,
            $decoded
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return course_workflow_response::applied_blueprint_structure();
    }

    /**
     * Validate course write context.
     *
     * @param int $courseid Courseid.
     * @param array $blueprint Blueprint.
     * @return void
     */
    private static function validate_course_write_context(int $courseid, array $blueprint): void {
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        if (!empty($blueprint['sections']) && is_array($blueprint['sections'])) {
            require_capability('moodle/course:manageactivities', $coursecontext);
        }
        if (!empty($blueprint['groups']) && is_array($blueprint['groups'])) {
            require_capability('moodle/course:managegroups', $coursecontext);
        }
        if (!empty($blueprint['enrolments']) && is_array($blueprint['enrolments'])) {
            require_capability('enrol/manual:enrol', $coursecontext);
        }
    }
}
