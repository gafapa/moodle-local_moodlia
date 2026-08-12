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
 * Visible courses external function.
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
use local_moodlia\operation\get_courses as get_courses_operation;

/**
 * External API adapter for get_courses.
 */
class get_courses extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'limit' => new external_value(PARAM_INT, 'Maximum number of courses to return', VALUE_DEFAULT, 100),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $limit Limit.
     * @return array
     */
    public static function execute(int $limit = 100): array {
        ['limit' => $limit] = self::validate_parameters(self::execute_parameters(), ['limit' => $limit]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);

        return get_courses_operation::execute((int) $limit);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Moodle course id'),
                'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                'category_id' => new external_value(PARAM_INT, 'Course category id'),
                'visible' => new external_value(PARAM_BOOL, 'Whether the course is visible'),
                'url' => new external_value(PARAM_URL, 'Course URL'),
            ])),
        ]);
    }
}
