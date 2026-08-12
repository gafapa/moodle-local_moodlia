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
 * Get wiki subwikis external function.
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
use local_moodlia\operation\get_wiki_subwikis as get_wiki_subwikis_operation;
use local_moodlia\operation\wiki_tools;

/**
 * External API adapter for get_wiki_subwikis.
 */
class get_wiki_subwikis extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        $course = get_course($courseid);
        $cm = wiki_tools::get_wiki_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/wiki:viewpage', $modulecontext);

        return get_wiki_subwikis_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Warning structure.
     *
     * @return external_single_structure
     */
    public static function warning_structure(): external_single_structure {
        return new external_single_structure([
            'item' => new external_value(PARAM_RAW, 'Warning item', VALUE_DEFAULT, ''),
            'item_id' => new external_value(PARAM_INT, 'Warning item id', VALUE_DEFAULT, 0),
            'warning_code' => new external_value(PARAM_RAW, 'Warning code', VALUE_DEFAULT, ''),
            'message' => new external_value(PARAM_RAW, 'Warning message', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Warnings structure.
     *
     * @return external_multiple_structure
     */
    public static function warnings_structure(): external_multiple_structure {
        return new external_multiple_structure(self::warning_structure());
    }

    /**
     * Subwiki structure.
     *
     * @return external_single_structure
     */
    public static function subwiki_structure(): external_single_structure {
        return new external_single_structure([
            'subwiki_id' => new external_value(PARAM_INT, 'Subwiki id'),
            'wiki_id' => new external_value(PARAM_INT, 'Wiki instance id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'group_id' => new external_value(PARAM_INT, 'Subwiki group id'),
            'user_id' => new external_value(PARAM_INT, 'Subwiki user id'),
            'can_edit' => new external_value(PARAM_BOOL, 'Whether the current user can edit this subwiki'),
        ]);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'wiki_id' => new external_value(PARAM_INT, 'Wiki instance id'),
            'count' => new external_value(PARAM_INT, 'Number of returned subwikis'),
            'subwikis' => new external_multiple_structure(self::subwiki_structure()),
            'warnings' => self::warnings_structure(),
        ]);
    }
}
