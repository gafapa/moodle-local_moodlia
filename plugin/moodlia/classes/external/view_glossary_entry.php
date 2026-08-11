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
 * View Glossary entry external function.
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
use local_moodlia\operation\glossary_tools;
use local_moodlia\operation\view_glossary_entry as view_glossary_entry_operation;

/**
 * External API adapter for view_glossary_entry.
 */
class view_glossary_entry extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'entry_id' => new external_value(PARAM_INT, 'Glossary entry id'),
        ]);
    }

    public static function execute(int $course_id, int $module_id, int $entry_id): array {
        ['course_id' => $courseid, 'module_id' => $moduleid, 'entry_id' => $entryid] =
            self::validate_parameters(self::execute_parameters(), [
                'course_id' => $course_id,
                'module_id' => $module_id,
                'entry_id' => $entry_id,
            ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/glossary:view', $modulecontext);

        return view_glossary_entry_operation::execute((int) $courseid, (int) $moduleid, (int) $entryid);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'entry_id' => new external_value(PARAM_INT, 'Glossary entry id'),
            'viewed' => new external_value(PARAM_BOOL, 'Whether the entry view was registered'),
            'warnings' => get_course_glossaries::warnings_structure(),
        ]);
    }
}
