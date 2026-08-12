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
 * Get Glossary entries by date external function.
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
use local_moodlia\operation\get_glossary_entries_by_date as get_glossary_entries_by_date_operation;

/**
 * External API adapter for get_glossary_entries_by_date.
 */
class get_glossary_entries_by_date extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'order' => new external_value(PARAM_ALPHA, 'Order: CREATION or UPDATE', VALUE_DEFAULT, 'UPDATE'),
            'sort' => new external_value(PARAM_ALPHA, 'Sort: ASC or DESC', VALUE_DEFAULT, 'DESC'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
            'include_not_approved' => new external_value(PARAM_BOOL, 'Include non-approved entries where allowed', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute(
        int $course_id,
        int $module_id,
        string $order = 'UPDATE',
        string $sort = 'DESC',
        int $from = 0,
        int $limit = 20,
        bool $include_not_approved = false
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $includenotapproved,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
            'include_not_approved' => $include_not_approved,
        ]);

        get_glossary_entries_by_letter::validate_glossary_view_context((int) $courseid, (int) $moduleid);

        return get_glossary_entries_by_date_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $order,
            $sort,
            (int) $from,
            (int) $limit,
            (bool) $includenotapproved
        );
    }

    public static function execute_returns(): external_single_structure {
        return get_glossary_entries_by_letter::entries_result_structure();
    }
}
