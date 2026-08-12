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
 * Get course choices external function.
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
use local_moodlia\operation\get_course_choices as get_course_choices_operation;

/**
 * External API adapter for get_course_choices.
 */
class get_course_choices extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
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

        return get_course_choices_operation::execute((int) $courseid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'count' => new external_value(PARAM_INT, 'Returned Choice count'),
            'choices' => new external_multiple_structure(self::choice_summary_structure()),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Shared Choice summary structure.
     *
     * @return external_single_structure
     */
    public static function choice_summary_structure(): external_single_structure {
        return new external_single_structure([
            'choice_id' => new external_value(PARAM_INT, 'Choice instance id'),
            'choice_module_id' => new external_value(PARAM_INT, 'Choice course module id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_RAW, 'Choice activity name'),
            'intro' => new external_value(PARAM_RAW, 'Choice activity intro'),
            'intro_format' => new external_value(PARAM_INT, 'Moodle intro format'),
            'publish_anonymous' => new external_value(PARAM_BOOL, 'Whether responses are published anonymously'),
            'show_results' => new external_value(PARAM_INT, 'Moodle show-results setting'),
            'display' => new external_value(PARAM_INT, 'Moodle display setting'),
            'allow_update' => new external_value(PARAM_BOOL, 'Whether users may update their response'),
            'allow_multiple' => new external_value(PARAM_BOOL, 'Whether multiple options may be selected'),
            'show_unanswered' => new external_value(PARAM_BOOL, 'Whether unanswered users are shown'),
            'include_inactive' => new external_value(PARAM_BOOL, 'Whether inactive users are included'),
            'limit_answers' => new external_value(PARAM_BOOL, 'Whether option limits are enabled'),
            'time_open' => new external_value(PARAM_INT, 'Opening timestamp'),
            'time_close' => new external_value(PARAM_INT, 'Closing timestamp'),
            'show_preview' => new external_value(PARAM_BOOL, 'Whether preview before opening is shown'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification timestamp'),
            'completion_submit' => new external_value(PARAM_BOOL, 'Whether submission completes the activity'),
            'show_available' => new external_value(PARAM_BOOL, 'Whether available spaces are shown'),
        ]);
    }

    /**
     * Shared warning structure.
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
