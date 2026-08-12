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
 * Create course category external function.
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
use local_moodlia\operation\create_course_category as create_course_category_operation;
use local_moodlia\operation\course_tools;

/**
 * External API adapter for create_course_category.
 */
class create_course_category extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Category name'),
            'parent_id' => new external_value(PARAM_INT, 'Parent category id, or 0 for top level', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_BOOL, 'Whether the category is visible', VALUE_DEFAULT, true),
            'reuse_existing' => new external_value(PARAM_BOOL, 'Return an existing sibling category with the same name instead of creating a duplicate', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $name Name.
     * @param int $parentid Parentid.
     * @param bool $visible Visible.
     * @param bool $reuseexisting Reuseexisting.
     * @return array
     */
    public static function execute(string $name, int $parentid = 0, bool $visible = true, bool $reuseexisting = false): array {
        [
            'name' => $name,
            'parent_id' => $parentid,
            'visible' => $visible,
            'reuse_existing' => $reuseexisting,
        ] = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'parent_id' => $parentid,
            'visible' => $visible,
            'reuse_existing' => $reuseexisting,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $context = ((int) $parentid) > 0 ? \context_coursecat::instance((int) $parentid) : $systemcontext;
        self::validate_context($context);
        require_capability('moodle/category:manage', $context);

        return create_course_category_operation::execute($name, (int) $parentid, (bool) $visible, (bool) $reuseexisting);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'category_id' => new external_value(PARAM_INT, 'Moodle course category id'),
            'name' => new external_value(PARAM_TEXT, 'Category name'),
            'parent_id' => new external_value(PARAM_INT, 'Parent category id'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the category is visible'),
            'course_count' => new external_value(PARAM_INT, 'Number of direct courses in the category'),
            'url' => new external_value(PARAM_URL, 'Category URL'),
            'created' => new external_value(PARAM_BOOL, 'Whether a new category was created'),
        ]);
    }
}
