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
 * List forum discussion posts external function.
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
use local_moodlia\operation\forum_tools;
use local_moodlia\operation\get_forum_discussion_posts as get_forum_discussion_posts_operation;

/**
 * External API adapter for get_forum_discussion_posts.
 */
class get_forum_discussion_posts extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
        ]);
    }

    public static function execute(int $course_id, int $module_id, int $discussion_id): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'discussion_id' => $discussionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'discussion_id' => $discussion_id,
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

        return get_forum_discussion_posts_operation::execute((int) $courseid, (int) $moduleid, (int) $discussionid);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'forum_id' => new external_value(PARAM_INT, 'Forum instance id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'posts' => new external_multiple_structure(self::post_structure()),
        ]);
    }

    public static function post_structure(): external_single_structure {
        return new external_single_structure([
            'post_id' => new external_value(PARAM_INT, 'Forum post id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'parent_post_id' => new external_value(PARAM_INT, 'Parent post id'),
            'subject' => new external_value(PARAM_TEXT, 'Post subject'),
            'message' => new external_value(PARAM_RAW, 'Post message'),
            'user_id' => new external_value(PARAM_INT, 'Author user id'),
            'created' => new external_value(PARAM_INT, 'Creation timestamp'),
            'modified' => new external_value(PARAM_INT, 'Modified timestamp'),
            'url' => new external_value(PARAM_URL, 'Post URL'),
        ]);
    }
}
