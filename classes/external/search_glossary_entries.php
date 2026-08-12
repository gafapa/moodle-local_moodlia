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
 * Search glossary entries external function.
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
use local_moodlia\operation\glossary_tools;
use local_moodlia\operation\search_glossary_entries as search_glossary_entries_operation;

/**
 * External API adapter for search_glossary_entries.
 */
class search_glossary_entries extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'query' => new external_value(PARAM_TEXT, 'Search query'),
            'full_search' => new external_value(PARAM_BOOL, 'Search definitions too', VALUE_DEFAULT, true),
            'order' => new external_value(PARAM_ALPHA, 'Order: CONCEPT, CREATION, or UPDATE', VALUE_DEFAULT, 'CONCEPT'),
            'sort' => new external_value(PARAM_ALPHA, 'Sort: ASC or DESC', VALUE_DEFAULT, 'ASC'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
            'include_not_approved' => new external_value(PARAM_BOOL, 'Include non-approved entries where allowed', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $query Query.
     * @param bool $fullsearch Fullsearch.
     * @param string $order Order.
     * @param string $sort Sort.
     * @param int $from From.
     * @param int $limit Limit.
     * @param bool $includenotapproved Includenotapproved.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $query,
        bool $fullsearch = true,
        string $order = 'CONCEPT',
        string $sort = 'ASC',
        int $from = 0,
        int $limit = 20,
        bool $includenotapproved = false
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'query' => $query,
            'full_search' => $fullsearch,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'query' => $query,
            'full_search' => $fullsearch,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/glossary:view', $modulecontext);

        return search_glossary_entries_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $query,
            (bool) $fullsearch,
            $order,
            $sort,
            (int) $from,
            (int) $limit,
            (bool) $includenotapproved
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'count' => new external_value(PARAM_INT, 'Matching entry count'),
            'entries' => new external_multiple_structure(create_glossary_entry::entry_returns()),
        ]);
    }
}
