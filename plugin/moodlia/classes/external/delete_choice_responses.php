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
 * Delete choice responses external function.
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
use local_moodlia\operation\delete_choice_responses as delete_choice_responses_operation;

/**
 * External API adapter for delete_choice_responses.
 */
class delete_choice_responses extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'choice_module_id' => new external_value(PARAM_INT, 'Choice course module id'),
            'response_ids' => new external_value(PARAM_RAW, 'Optional JSON array of response ids', VALUE_DEFAULT, '[]'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $choice_module_id Choice course module id.
     * @param string $response_ids Optional JSON array of response ids.
     * @return array
     */
    public static function execute(int $course_id, int $choice_module_id, string $response_ids = '[]'): array {
        [
            'course_id' => $courseid,
            'choice_module_id' => $choicemoduleid,
            'response_ids' => $responseids,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'choice_module_id' => $choice_module_id,
            'response_ids' => $response_ids,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($choicemoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/choice:choose', $modulecontext);

        return delete_choice_responses_operation::execute((int) $courseid, (int) $choicemoduleid, (string) $responseids);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'choice_id' => new external_value(PARAM_INT, 'Choice instance id'),
            'choice_module_id' => new external_value(PARAM_INT, 'Choice course module id'),
            'deleted' => new external_value(PARAM_BOOL, 'Whether Moodle deleted the selected responses'),
            'response_ids' => new external_value(PARAM_RAW, 'Deleted JSON response ids, or an empty array for current-user responses'),
            'warnings' => get_course_choices::warnings_structure(),
        ]);
    }
}
