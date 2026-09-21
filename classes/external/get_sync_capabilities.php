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
 * Synchronization capability discovery external function.
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
use local_moodlia\operation\get_sync_capabilities as get_sync_capabilities_operation;

/**
 * External adapter for contextual synchronization capability discovery.
 */
class get_sync_capabilities extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Optional Moodle course id', VALUE_DEFAULT, 0),
            'category_id' => new external_value(PARAM_INT, 'Optional Moodle course category id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $categoryid Categoryid.
     * @return array
     */
    public static function execute(int $courseid = 0, int $categoryid = 0): array {
        ['course_id' => $courseid, 'category_id' => $categoryid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'category_id' => $categoryid,
        ]);
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        if ($courseid > 0) {
            self::validate_context(\context_course::instance($courseid));
        }
        if ($categoryid > 0) {
            self::validate_context(\context_coursecat::instance($categoryid));
        }
        return get_sync_capabilities_operation::execute(
            $courseid > 0 ? $courseid : null,
            $categoryid > 0 ? $categoryid : null
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Evaluated course id, or zero'),
            'category_id' => new external_value(PARAM_INT, 'Evaluated course category id, or zero'),
            'context_evaluated' => new external_value(PARAM_BOOL, 'Whether course capabilities were evaluated'),
            'capabilities_json' => new external_value(PARAM_RAW, 'JSON object containing contextual capability evidence'),
        ]);
    }
}
