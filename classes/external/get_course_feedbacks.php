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
 * Get course feedbacks external function.
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
use local_moodlia\operation\get_course_feedbacks as get_course_feedbacks_operation;

/**
 * External API adapter for get_course_feedbacks.
 */
class get_course_feedbacks extends external_api {
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

        return get_course_feedbacks_operation::execute((int) $courseid);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'count' => new external_value(PARAM_INT, 'Returned Feedback count'),
            'feedbacks' => new external_multiple_structure(self::feedback_summary_structure()),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Feedback summary structure.
     *
     * @return external_single_structure
     */
    public static function feedback_summary_structure(): external_single_structure {
        return new external_single_structure([
            'feedback_id' => new external_value(PARAM_INT, 'Feedback instance id'),
            'module_id' => new external_value(PARAM_INT, 'Feedback course module id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_TEXT, 'Feedback activity name'),
            'intro' => new external_value(PARAM_RAW, 'Feedback intro'),
            'intro_format' => new external_value(PARAM_INT, 'Moodle intro format'),
            'language' => new external_value(PARAM_TEXT, 'Forced activity language'),
            'anonymous' => new external_value(PARAM_INT, 'Whether feedback is anonymous'),
            'email_notification' => new external_value(PARAM_BOOL, 'Whether email notifications are enabled'),
            'multiple_submit' => new external_value(PARAM_BOOL, 'Whether multiple submissions are allowed'),
            'auto_numbering' => new external_value(PARAM_BOOL, 'Whether questions are auto-numbered'),
            'site_after_submit' => new external_value(PARAM_TEXT, 'Redirect URL after submit'),
            'page_after_submit' => new external_value(PARAM_RAW, 'Page after submit content'),
            'page_after_submit_format' => new external_value(PARAM_INT, 'Page after submit format'),
            'publish_stats' => new external_value(PARAM_BOOL, 'Whether stats are published'),
            'time_open' => new external_value(PARAM_INT, 'Opening timestamp'),
            'time_close' => new external_value(PARAM_INT, 'Closing timestamp'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification timestamp'),
            'completion_submit' => new external_value(PARAM_BOOL, 'Whether submission completes the activity'),
            'url' => new external_value(PARAM_URL, 'Feedback URL'),
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
