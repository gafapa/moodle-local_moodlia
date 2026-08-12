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
 * List forum discussions external function.
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
use local_moodlia\operation\forum_tools;
use local_moodlia\operation\get_forum_discussions as get_forum_discussions_operation;

/**
 * External API adapter for get_forum_discussions.
 */
class get_forum_discussions extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
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
        require_capability('moodle/course:view', $coursecontext);

        $course = get_course($courseid);
        $cm = forum_tools::get_forum_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/forum:viewdiscussion', $modulecontext);

        return get_forum_discussions_operation::execute((int) $courseid, (int) $moduleid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'forum_id' => new external_value(PARAM_INT, 'Forum instance id'),
            'discussions' => new external_multiple_structure(self::discussion_structure()),
        ]);
    }

    /**
     * Discussion structure.
     *
     * @return external_single_structure
     */
    public static function discussion_structure(): external_single_structure {
        return new external_single_structure([
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'forum_id' => new external_value(PARAM_INT, 'Forum instance id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'first_post_id' => new external_value(PARAM_INT, 'First post id'),
            'name' => new external_value(PARAM_TEXT, 'Discussion name'),
            'message' => new external_value(PARAM_RAW, 'Discussion message'),
            'user_id' => new external_value(PARAM_INT, 'Author user id'),
            'reply_count' => new external_value(PARAM_INT, 'Reply count'),
            'created' => new external_value(PARAM_INT, 'Creation timestamp'),
            'modified' => new external_value(PARAM_INT, 'Modified timestamp'),
            'can_reply' => new external_value(PARAM_BOOL, 'Whether the current user can reply'),
            'url' => new external_value(PARAM_URL, 'Discussion URL'),
        ]);
    }
}
