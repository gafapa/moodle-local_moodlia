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
 * Get wiki pages external function.
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
use local_moodlia\operation\get_wiki_pages as get_wiki_pages_operation;
use local_moodlia\operation\wiki_tools;

/**
 * External API adapter for get_wiki_pages.
 */
class get_wiki_pages extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'group_id' => new external_value(PARAM_INT, 'Group id, or -1 for current group', VALUE_DEFAULT, -1),
            'user_id' => new external_value(PARAM_INT, 'User id, or 0 for current user', VALUE_DEFAULT, 0),
            'sort_by' => new external_value(PARAM_ALPHA, 'Sort field', VALUE_DEFAULT, 'title'),
            'sort_direction' => new external_value(PARAM_ALPHA, 'Sort direction: ASC or DESC', VALUE_DEFAULT, 'ASC'),
            'include_content' => new external_value(PARAM_BOOL, 'Include rendered page content', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $groupid Groupid.
     * @param int $userid Userid.
     * @param string $sortby Sortby.
     * @param string $sortdirection Sortdirection.
     * @param bool $includecontent Includecontent.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $groupid = -1,
        int $userid = 0,
        string $sortby = 'title',
        string $sortdirection = 'ASC',
        bool $includecontent = true
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
            'user_id' => $userid,
            'sort_by' => $sortby,
            'sort_direction' => $sortdirection,
            'include_content' => $includecontent,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
            'user_id' => $userid,
            'sort_by' => $sortby,
            'sort_direction' => $sortdirection,
            'include_content' => $includecontent,
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

        return get_wiki_pages_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $groupid,
            (int) $userid,
            $sortby,
            $sortdirection,
            (bool) $includecontent
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
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'wiki_id' => new external_value(PARAM_INT, 'Wiki instance id'),
            'count' => new external_value(PARAM_INT, 'Number of returned pages'),
            'pages' => new external_multiple_structure(create_wiki_page::page_returns()),
        ]);
    }
}
