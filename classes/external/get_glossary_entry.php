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
 * Get Glossary entry external function.
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
use local_moodlia\operation\get_glossary_entry as get_glossary_entry_operation;

/**
 * External API adapter for get_glossary_entry.
 */
class get_glossary_entry extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'entry_id' => new external_value(PARAM_INT, 'Glossary entry id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $entryid Entryid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $entryid): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'entry_id' => $entryid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'entry_id' => $entryid,
        ]);

        get_glossary_entries_by_letter::validate_glossary_view_context((int) $courseid, (int) $moduleid);

        return get_glossary_entry_operation::execute((int) $courseid, (int) $moduleid, (int) $entryid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'entry_id' => new external_value(PARAM_INT, 'Glossary entry id'),
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'concept' => new external_value(PARAM_RAW, 'Entry concept'),
            'definition' => new external_value(PARAM_RAW, 'Entry definition'),
            'definition_format' => new external_value(PARAM_ALPHA, 'Entry definition format'),
            'approved' => new external_value(PARAM_BOOL, 'Whether the entry is approved'),
            'url' => new external_value(PARAM_URL, 'Entry URL'),
            'can_delete' => new external_value(PARAM_BOOL, 'Whether the current user can delete this entry'),
            'can_update' => new external_value(PARAM_BOOL, 'Whether the current user can update this entry'),
            'warnings' => get_course_glossaries::warnings_structure(),
        ]);
    }
}
