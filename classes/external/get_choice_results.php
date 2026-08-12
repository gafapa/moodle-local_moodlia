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
 * List choice results external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_choice_results as get_choice_results_operation;

/**
 * External API adapter for get_choice_results.
 */
class get_choice_results extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'choice_module_id' => new external_value(PARAM_INT, 'Choice course module id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $choicemoduleid Choice course module id.
     * @return array
     */
    public static function execute(int $courseid, int $choicemoduleid): array {
        [
            'course_id' => $courseid,
            'choice_module_id' => $choicemoduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'choice_module_id' => $choicemoduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($choicemoduleid);
        self::validate_context($modulecontext);
        require_capability('mod/choice:readresponses', $modulecontext);

        return get_choice_results_operation::execute((int) $courseid, (int) $choicemoduleid);
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
            'results' => new external_multiple_structure(new external_single_structure([
                'option_id' => new external_value(PARAM_INT, 'Choice option id'),
                'text' => new external_value(PARAM_RAW, 'Choice option text'),
                'answer_count' => new external_value(PARAM_INT, 'Number of answers for this option'),
            ])),
        ]);
    }
}
