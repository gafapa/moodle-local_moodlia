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
 * Get wiki files external function.
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
use local_moodlia\operation\get_wiki_files as get_wiki_files_operation;
use local_moodlia\operation\wiki_tools;

/**
 * External API adapter for get_wiki_files.
 */
class get_wiki_files extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'group_id' => new external_value(PARAM_INT, 'Group id, or -1 for current group', VALUE_DEFAULT, -1),
            'user_id' => new external_value(PARAM_INT, 'User id, or 0 for current user', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $course_id, int $module_id, int $group_id = -1, int $user_id = 0): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'group_id' => $groupid,
            'user_id' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'group_id' => $group_id,
            'user_id' => $user_id,
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

        return get_wiki_files_operation::execute((int) $courseid, (int) $moduleid, (int) $groupid, (int) $userid);
    }

    public static function file_structure(): external_single_structure {
        return new external_single_structure([
            'file_name' => new external_value(PARAM_RAW, 'File name'),
            'file_path' => new external_value(PARAM_RAW, 'File path'),
            'file_size' => new external_value(PARAM_INT, 'File size in bytes'),
            'file_url' => new external_value(PARAM_RAW, 'Download URL'),
            'time_modified' => new external_value(PARAM_INT, 'Modification timestamp'),
            'mime_type' => new external_value(PARAM_RAW, 'MIME type'),
            'is_external_file' => new external_value(PARAM_BOOL, 'Whether this is an external repository file'),
            'repository_type' => new external_value(PARAM_RAW, 'External repository type'),
        ]);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'wiki_id' => new external_value(PARAM_INT, 'Wiki instance id'),
            'group_id' => new external_value(PARAM_INT, 'Requested group id'),
            'user_id' => new external_value(PARAM_INT, 'Requested user id'),
            'count' => new external_value(PARAM_INT, 'Number of returned files'),
            'files' => new external_multiple_structure(self::file_structure()),
            'warnings' => get_wiki_subwikis::warnings_structure(),
        ]);
    }
}
