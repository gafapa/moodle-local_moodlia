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
 * Course categories external function.
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
use local_moodlia\operation\get_course_categories as get_course_categories_operation;

/**
 * External API adapter for get_course_categories.
 */
class get_course_categories extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'parent_id' => new external_value(PARAM_INT, 'Parent category id, or -1 for all categories', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $parentid Parentid.
     * @return array
     */
    public static function execute(int $parentid = -1): array {
        ['parent_id' => $parentid] = self::validate_parameters(self::execute_parameters(), [
            'parent_id' => $parentid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        return get_course_categories_operation::execute((int) $parentid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(self::category_structure()),
        ]);
    }

    /**
     * Category structure.
     *
     * @return external_single_structure
     */
    public static function category_structure(): external_single_structure {
        return new external_single_structure([
            'category_id' => new external_value(PARAM_INT, 'Moodle course category id'),
            'name' => new external_value(PARAM_TEXT, 'Category name'),
            'parent_id' => new external_value(PARAM_INT, 'Parent category id'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the category is visible'),
            'course_count' => new external_value(PARAM_INT, 'Number of direct courses in the category'),
            'url' => new external_value(PARAM_URL, 'Category URL'),
        ]);
    }
}
