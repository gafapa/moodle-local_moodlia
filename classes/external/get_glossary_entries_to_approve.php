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
 * Get Glossary entries to approve external function.
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
use local_moodlia\operation\get_glossary_entries_to_approve as get_glossary_entries_to_approve_operation;
use local_moodlia\operation\glossary_tools;

/**
 * External API adapter for get_glossary_entries_to_approve.
 */
class get_glossary_entries_to_approve extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'letter' => new external_value(PARAM_ALPHA, 'Letter, ALL, or SPECIAL', VALUE_DEFAULT, 'ALL'),
            'order' => new external_value(PARAM_ALPHA, 'CONCEPT, CREATION, or UPDATE', VALUE_DEFAULT, 'CONCEPT'),
            'sort' => new external_value(PARAM_ALPHA, 'ASC or DESC', VALUE_DEFAULT, 'ASC'),
            'from' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 20),
        ]);
    }

    public static function execute(
        int $course_id,
        int $module_id,
        string $letter = 'ALL',
        string $order = 'CONCEPT',
        string $sort = 'ASC',
        int $from = 0,
        int $limit = 20
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'letter' => $letter,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'letter' => $letter,
            'order' => $order,
            'sort' => $sort,
            'from' => $from,
            'limit' => $limit,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/glossary:approve', $modulecontext);

        return get_glossary_entries_to_approve_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $letter,
            $order,
            $sort,
            (int) $from,
            (int) $limit
        );
    }

    public static function execute_returns(): external_single_structure {
        return get_glossary_entries_by_letter::entries_result_structure();
    }
}
