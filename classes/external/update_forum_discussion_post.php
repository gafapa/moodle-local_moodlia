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
 * Update forum discussion post external function.
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
use local_moodlia\operation\forum_tools;
use local_moodlia\operation\update_forum_discussion_post as update_forum_discussion_post_operation;

/**
 * External API adapter for update_forum_discussion_post.
 */
class update_forum_discussion_post extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'post_id' => new external_value(PARAM_INT, 'Forum post id'),
            'subject' => new external_value(PARAM_TEXT, 'Updated subject', VALUE_DEFAULT, ''),
            'message' => new external_value(PARAM_RAW, 'Updated message', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $discussionid Discussionid.
     * @param int $postid Postid.
     * @param string $subject Subject.
     * @param string $message Message.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $discussionid,
        int $postid,
        string $subject = '',
        string $message = ''
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'discussion_id' => $discussionid,
            'post_id' => $postid,
            'subject' => $subject,
            'message' => $message,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'discussion_id' => $discussionid,
            'post_id' => $postid,
            'subject' => $subject,
            'message' => $message,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);

        $course = get_course($courseid);
        $cm = forum_tools::get_forum_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        if (
            !has_capability('mod/forum:editanypost', $modulecontext) &&
            !has_capability('mod/forum:editownpost', $modulecontext)
        ) {
            require_capability('mod/forum:editownpost', $modulecontext);
        }

        return update_forum_discussion_post_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $discussionid,
            (int) $postid,
            $subject !== '' ? $subject : null,
            $message !== '' ? $message : null
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_forum_discussion_posts::post_structure();
    }
}
