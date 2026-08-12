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
 * Delete question category external function.
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
use local_moodlia\operation\delete_question_category as delete_question_category_operation;
use local_moodlia\operation\question_tools;

/**
 * External API adapter for delete_question_category.
 */
class delete_question_category extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'category_id' => new external_value(PARAM_INT, 'Question category id'),
            'context_id' => new external_value(PARAM_INT, 'Question bank context id'),
            'delete_mode' => new external_value(PARAM_ALPHA, 'Delete mode', VALUE_DEFAULT, 'delete'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $category_id Question category id.
     * @param int $context_id Question bank context id.
     * @param string $delete_mode Delete mode.
     * @return array
     */
    public static function execute(int $category_id, int $context_id, string $delete_mode = 'delete'): array {
        [
            'category_id' => $categoryid,
            'context_id' => $contextid,
            'delete_mode' => $deletemode,
        ] = self::validate_parameters(self::execute_parameters(), [
            'category_id' => $category_id,
            'context_id' => $context_id,
            'delete_mode' => $delete_mode,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $categorycontext = question_tools::require_question_category_context((int) $categoryid, (int) $contextid);
        self::validate_context($categorycontext);
        require_capability('moodle/question:managecategory', $categorycontext);

        return delete_question_category_operation::execute((int) $categoryid, $deletemode);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Whether the category was deleted'),
            'id' => new external_value(PARAM_INT, 'Deleted question category id'),
        ]);
    }
}
