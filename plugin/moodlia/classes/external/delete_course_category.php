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
 * Delete course category external function.
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
use local_moodlia\operation\course_tools;
use local_moodlia\operation\delete_course_category as delete_course_category_operation;

/**
 * External API adapter for delete_course_category.
 */
class delete_course_category extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'category_id' => new external_value(PARAM_INT, 'Moodle course category id'),
        ]);
    }

    public static function execute(int $category_id): array {
        ['category_id' => $categoryid] = self::validate_parameters(self::execute_parameters(), [
            'category_id' => $category_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        course_tools::get_category((int) $categoryid);
        $categorycontext = \context_coursecat::instance((int) $categoryid);
        self::validate_context($categorycontext);
        require_capability('moodle/category:manage', $categorycontext);

        return delete_course_category_operation::execute((int) $categoryid);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Whether the category was deleted'),
            'id' => new external_value(PARAM_INT, 'Deleted category id'),
        ]);
    }
}
