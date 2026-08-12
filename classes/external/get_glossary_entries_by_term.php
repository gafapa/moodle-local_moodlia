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
 * Get Glossary entries by term external function.
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
use local_moodlia\operation\get_glossary_entries_by_term as get_glossary_entries_by_term_operation;

/**
 * External API adapter for get_glossary_entries_by_term.
 */
class get_glossary_entries_by_term extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'term' => new external_value(PARAM_TEXT, 'Concept or alias term'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
            'include_not_approved' => new external_value(PARAM_BOOL, 'Include non-approved entries where allowed', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $term Term.
     * @param int $from From.
     * @param int $limit Limit.
     * @param bool $includenotapproved Includenotapproved.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $term,
        int $from = 0,
        int $limit = 20,
        bool $includenotapproved = false
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'term' => $term,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'term' => $term,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ]);

        get_glossary_entries_by_letter::validate_glossary_view_context((int) $courseid, (int) $moduleid);

        return get_glossary_entries_by_term_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $term,
            (int) $from,
            (int) $limit,
            (bool) $includenotapproved
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_glossary_entries_by_letter::entries_result_structure();
    }
}
