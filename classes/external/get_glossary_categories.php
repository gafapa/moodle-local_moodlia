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
 * Get Glossary categories external function.
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
use local_moodlia\operation\get_glossary_categories as get_glossary_categories_operation;

/**
 * External API adapter for get_glossary_categories.
 */
class get_glossary_categories extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $moduleid Module id.
     * @param int $from From.
     * @param int $limit Limit.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $from = 0, int $limit = 20): array {
        ['course_id' => $courseid, 'module_id' => $moduleid, 'from' => $from, 'limit' => $limit] =
            self::validate_parameters(self::execute_parameters(), [
                'course_id' => $courseid,
                'module_id' => $moduleid,
                'from' => $from,
                'limit' => $limit,
            ]);

        get_glossary_entries_by_letter::validate_glossary_view_context((int) $courseid, (int) $moduleid);

        return get_glossary_categories_operation::execute((int) $courseid, (int) $moduleid, (int) $from, (int) $limit);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'count' => new external_value(PARAM_INT, 'Category count'),
            'categories' => new external_multiple_structure(new external_single_structure([
                'category_id' => new external_value(PARAM_INT, 'Glossary category id'),
                'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
                'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
                'name' => new external_value(PARAM_RAW, 'Category name'),
                'use_dynamic_linking' => new external_value(PARAM_BOOL, 'Whether category dynamic linking is enabled'),
            ])),
            'warnings' => get_course_glossaries::warnings_structure(),
        ]);
    }
}
