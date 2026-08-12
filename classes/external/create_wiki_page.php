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
 * Create wiki page external function.
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
use local_moodlia\operation\create_wiki_page as create_wiki_page_operation;
use local_moodlia\operation\wiki_tools;

/**
 * External API adapter for create_wiki_page.
 */
class create_wiki_page extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'title' => new external_value(PARAM_TEXT, 'Wiki page title'),
            'content' => new external_value(PARAM_RAW, 'Wiki page content'),
            'content_format' => new external_value(PARAM_ALPHA, 'Wiki content format', VALUE_DEFAULT, 'html'),
            'group_id' => new external_value(PARAM_INT, 'Group id, or -1 for current group', VALUE_DEFAULT, -1),
            'user_id' => new external_value(PARAM_INT, 'User id, or 0 for current user', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $title Title.
     * @param string $content Content.
     * @param string $contentformat Contentformat.
     * @param int $groupid Groupid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $title,
        string $content,
        string $contentformat = 'html',
        int $groupid = -1,
        int $userid = 0
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'group_id' => $groupid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'group_id' => $groupid,
            'user_id' => $userid,
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
        require_capability('mod/wiki:editpage', $modulecontext);

        return create_wiki_page_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            $title,
            $content,
            $contentformat,
            (int) $groupid,
            (int) $userid
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return self::page_returns();
    }

    /**
     * Shared wiki page return structure.
     *
     * @return external_single_structure
     */
    public static function page_returns(): external_single_structure {
        return new external_single_structure([
            'page_id' => new external_value(PARAM_INT, 'Wiki page id'),
            'wiki_id' => new external_value(PARAM_INT, 'Wiki instance id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'subwiki_id' => new external_value(PARAM_INT, 'Subwiki id'),
            'title' => new external_value(PARAM_TEXT, 'Wiki page title'),
            'content' => new external_value(PARAM_RAW, 'Rendered wiki page content'),
            'content_format' => new external_value(PARAM_RAW, 'Rendered content format'),
            'can_edit' => new external_value(PARAM_BOOL, 'Whether the current user can edit the page'),
            'first_page' => new external_value(PARAM_BOOL, 'Whether this page is the first page'),
            'time_created' => new external_value(PARAM_INT, 'Creation timestamp'),
            'time_modified' => new external_value(PARAM_INT, 'Modification timestamp'),
            'url' => new external_value(PARAM_URL, 'Wiki page URL'),
        ]);
    }
}
