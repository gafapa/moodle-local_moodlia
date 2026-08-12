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
use local_moodlia\operation\course_tools;
use local_moodlia\operation\course_workflow_tools;
use local_moodlia\operation\create_course_from_blueprint as create_course_from_blueprint_operation;

/**
 * External API adapter for create_course_from_blueprint.
 */
class create_course_from_blueprint extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blueprint' => new external_value(PARAM_RAW, 'JSON-encoded MoodlIA course blueprint'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $blueprint Blueprint.
     * @return array
     */
    public static function execute(string $blueprint): array {
        ['blueprint' => $blueprint] = self::validate_parameters(self::execute_parameters(), [
            'blueprint' => $blueprint,
        ]);

        $decoded = course_workflow_tools::decode_object($blueprint, 'blueprint');
        $courseinput = is_array($decoded['course'] ?? null) ? $decoded['course'] : $decoded;
        $categoryid = course_tools::resolve_category_id((int) ($courseinput['category_id'] ?? 0));

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $categorycontext = \context_coursecat::instance($categoryid);
        self::validate_context($categorycontext);
        require_capability('moodle/course:create', $categorycontext);

        return create_course_from_blueprint_operation::execute(
            $decoded,
            static function (int $courseid, array $createdblueprint): void {
                self::validate_created_course_write_context($courseid, $createdblueprint);
            }
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return course_workflow_response::created_course_structure();
    }

    /**
     * Validate created course write context.
     *
     * @param int $courseid Courseid.
     * @param array $blueprint Blueprint.
     * @return void
     */
    private static function validate_created_course_write_context(int $courseid, array $blueprint): void {
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
