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
 * Get Glossary entries by letter external function.
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
use local_moodlia\operation\get_glossary_entries_by_letter as get_glossary_entries_by_letter_operation;
use local_moodlia\operation\glossary_tools;

/**
 * External API adapter for get_glossary_entries_by_letter.
 */
class get_glossary_entries_by_letter extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'letter' => new external_value(PARAM_ALPHANUMEXT, 'Letter, ALL, or SPECIAL', VALUE_DEFAULT, 'ALL'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
            'include_not_approved' => new external_value(PARAM_BOOL, 'Include non-approved entries where allowed', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @param int $moduleid Module id.
     * @param string $letter Letter.
     * @param int $from From.
     * @param int $limit Limit.
     * @param bool $includenotapproved Include not approved.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $letter = 'ALL',
        int $from = 0,
        int $limit = 20,
        bool $includenotapproved = false
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'letter' => $letter,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'letter' => $letter,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ]);

        self::validate_glossary_view_context((int) $courseid, (int) $moduleid);

        return get_glossary_entries_by_letter_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $letter,
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
        return self::entries_result_structure();
    }

    /**
     * Entries result structure.
     *
     * @return external_single_structure
     */
    public static function entries_result_structure(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'count' => new external_value(PARAM_INT, 'Matching entry count'),
            'entries' => new external_multiple_structure(create_glossary_entry::entry_returns()),
            'warnings' => get_course_glossaries::warnings_structure(),
        ]);
    }

    /**
     * Validate glossary view context.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return void
     */
    public static function validate_glossary_view_context(int $courseid, int $moduleid): void {
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/glossary:view', $modulecontext);
    }
}
