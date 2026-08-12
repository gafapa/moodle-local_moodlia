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
 * Get course forums external function.
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
use local_moodlia\operation\get_course_forums as get_course_forums_operation;

/**
 * External API adapter for get_course_forums.
 */
class get_course_forums extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Course id.
     * @return array
     */
    public static function execute(int $courseid): array {
        ['course_id' => $courseid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_course_forums_operation::execute((int) $courseid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'count' => new external_value(PARAM_INT, 'Returned Forum count'),
            'forums' => new external_multiple_structure(self::forum_summary_structure()),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Forum summary structure.
     *
     * @return external_single_structure
     */
    public static function forum_summary_structure(): external_single_structure {
        return new external_single_structure([
            'forum_id' => new external_value(PARAM_INT, 'Forum instance id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'forum_type' => new external_value(PARAM_TEXT, 'Forum type'),
            'name' => new external_value(PARAM_RAW, 'Forum activity name'),
            'intro' => new external_value(PARAM_RAW, 'Forum activity intro'),
            'intro_format' => new external_value(PARAM_INT, 'Moodle intro format'),
            'due_date' => new external_value(PARAM_INT, 'Due timestamp'),
            'cutoff_date' => new external_value(PARAM_INT, 'Cutoff timestamp'),
            'assessed' => new external_value(PARAM_INT, 'Aggregate type'),
            'scale' => new external_value(PARAM_INT, 'Scale id or grade value'),
            'grade_forum' => new external_value(PARAM_INT, 'Whole forum grade'),
            'grade_forum_notify' => new external_value(PARAM_INT, 'Whether grading notifications are enabled'),
            'max_bytes' => new external_value(PARAM_INT, 'Maximum attachment size'),
            'max_attachments' => new external_value(PARAM_INT, 'Maximum attachment count'),
            'force_subscribe' => new external_value(PARAM_INT, 'Subscription mode'),
            'tracking_type' => new external_value(PARAM_INT, 'Tracking type'),
            'rss_type' => new external_value(PARAM_INT, 'RSS type'),
            'rss_articles' => new external_value(PARAM_INT, 'RSS article count'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification timestamp'),
            'warn_after' => new external_value(PARAM_INT, 'Post warning threshold'),
            'block_after' => new external_value(PARAM_INT, 'Post blocking threshold'),
            'block_period' => new external_value(PARAM_INT, 'Post blocking period'),
            'completion_discussions' => new external_value(PARAM_INT, 'Completion required discussions'),
            'completion_replies' => new external_value(PARAM_INT, 'Completion required replies'),
            'completion_posts' => new external_value(PARAM_INT, 'Completion required posts'),
            'discussion_count' => new external_value(PARAM_INT, 'Discussion count'),
            'can_create_discussions' => new external_value(PARAM_BOOL, 'Whether current user can create discussions'),
            'lock_discussion_after' => new external_value(PARAM_INT, 'Auto-lock period in seconds'),
            'tracked' => new external_value(PARAM_BOOL, 'Whether the current user tracks the forum'),
            'unread_posts' => new external_value(PARAM_INT, 'Unread post count'),
            'show_immediately' => new external_value(PARAM_BOOL, 'Whether Q&A replies are shown immediately'),
            'url' => new external_value(PARAM_URL, 'Forum URL'),
        ]);
    }

    /**
     * Warnings structure.
     *
     * @return external_multiple_structure
     */
    public static function warnings_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'item' => new external_value(PARAM_TEXT, 'Warning item'),
            'item_id' => new external_value(PARAM_INT, 'Warning item id'),
            'warning_code' => new external_value(PARAM_TEXT, 'Warning code'),
            'message' => new external_value(PARAM_TEXT, 'Warning message'),
        ]));
    }
}
