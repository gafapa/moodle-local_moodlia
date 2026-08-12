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
 * Update course category external function.
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
use local_moodlia\operation\update_course_category as update_course_category_operation;

/**
 * External API adapter for update_course_category.
 */
class update_course_category extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'category_id' => new external_value(PARAM_INT, 'Moodle course category id'),
            'name' => new external_value(PARAM_TEXT, 'Category name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'visible' => new external_value(PARAM_BOOL, 'Whether the category is visible', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $categoryid Category id.
     * @param string|null $name Name.
     * @param bool|null $visible Visible.
     * @return array
     */
    public static function execute(int $categoryid, ?string $name = null, ?bool $visible = null): array {
        [
            'category_id' => $categoryid,
            'name' => $name,
            'visible' => $visible,
        ] = self::validate_parameters(self::execute_parameters(), [
            'category_id' => $categoryid,
            'name' => $name,
            'visible' => $visible,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        course_tools::get_category((int) $categoryid);
        $categorycontext = \context_coursecat::instance((int) $categoryid);
        self::validate_context($categorycontext);
        require_capability('moodle/category:manage', $categorycontext);

        return update_course_category_operation::execute((int) $categoryid, $name, $visible);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_course_categories::category_structure();
    }
}
