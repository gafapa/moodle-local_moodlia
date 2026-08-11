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
 * Get Glossary entries by author id external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_glossary_entries_by_author_id as get_glossary_entries_by_author_id_operation;

/**
 * External API adapter for get_glossary_entries_by_author_id.
 */
class get_glossary_entries_by_author_id extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'author_id' => new external_value(PARAM_INT, 'Author user id'),
            'order' => new external_value(PARAM_ALPHA, 'CONCEPT, CREATION, or UPDATE', VALUE_DEFAULT, 'CONCEPT'),
            'sort' => new external_value(PARAM_ALPHA, 'ASC or DESC', VALUE_DEFAULT, 'ASC'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
            'include_not_approved' => new external_value(PARAM_BOOL, 'Include non-approved entries where allowed', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute(
        int $course_id,
        int $module_id,
        int $author_id,
        string $order = 'CONCEPT',
        string $sort = 'ASC',
        int $from = 0,
        int $limit = 20,
        bool $include_not_approved = false
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'author_id' => $authorid,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'author_id' => $author_id,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $include_not_approved,
        ]);

        get_glossary_entries_by_letter::validate_glossary_view_context((int) $courseid, (int) $moduleid);

        return get_glossary_entries_by_author_id_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $authorid,
            $order,
            $sort,
            (int) $from,
            (int) $limit,
            (bool) $includenotapproved
        );
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'count' => new external_value(PARAM_INT, 'Matching entry count'),
            'entries' => new external_multiple_structure(create_glossary_entry::entry_returns()),
            'warnings' => get_course_glossaries::warnings_structure(),
            'author_id' => new external_value(PARAM_INT, 'Requested author user id'),
        ]);
    }
}
