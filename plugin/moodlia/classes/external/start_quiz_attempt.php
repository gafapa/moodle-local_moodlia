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
 * Start quiz attempt external function.
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
use local_moodlia\operation\start_quiz_attempt as start_quiz_attempt_operation;

/**
 * External API adapter for start_quiz_attempt.
 */
class start_quiz_attempt extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'force_new' => new external_value(PARAM_BOOL, 'Force a new attempt when Moodle permits it', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $quiz_module_id Quiz course module id.
     * @param bool $force_new Force a new attempt when Moodle permits it.
     * @return array
     */
    public static function execute(int $quiz_module_id, bool $force_new = false): array {
        [
            'quiz_module_id' => $quizmoduleid,
            'force_new' => $forcenew,
        ] = self::validate_parameters(self::execute_parameters(), [
            'quiz_module_id' => $quiz_module_id,
            'force_new' => $force_new,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $modulecontext = \context_module::instance($quizmoduleid);
        self::validate_context($modulecontext);
        if (!has_capability('mod/quiz:attempt', $modulecontext) && !has_capability('mod/quiz:preview', $modulecontext)) {
            throw new \required_capability_exception($modulecontext, 'mod/quiz:attempt', 'nopermissions', '');
        }

        return start_quiz_attempt_operation::execute((int) $quizmoduleid, (bool) $forcenew);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'quiz_module_id' => new external_value(PARAM_INT, 'Quiz course module id'),
            'attempt' => self::attempt_structure(),
        ]);
    }

    /**
     * Return the canonical attempt structure.
     *
     * @return external_single_structure
     */
    public static function attempt_structure(): external_single_structure {
        return new external_single_structure([
            'attempt_id' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'quiz_id' => new external_value(PARAM_INT, 'Quiz instance id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'attempt_number' => new external_value(PARAM_INT, 'Attempt number'),
            'unique_id' => new external_value(PARAM_INT, 'Question usage id'),
            'state' => new external_value(PARAM_ALPHA, 'Attempt state'),
            'preview' => new external_value(PARAM_BOOL, 'Whether this is a preview attempt'),
            'time_start' => new external_value(PARAM_INT, 'Attempt start timestamp'),
            'time_finish' => new external_value(PARAM_INT, 'Attempt finish timestamp'),
            'time_modified' => new external_value(PARAM_INT, 'Attempt modified timestamp'),
            'sum_grades' => new external_value(PARAM_FLOAT, 'Raw attempt sum grades'),
        ]);
    }
}
