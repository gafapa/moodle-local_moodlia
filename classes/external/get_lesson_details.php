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
 * Get Lesson details external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_lesson_details as get_lesson_details_operation;
use local_moodlia\operation\lesson_tools;

/**
 * External API adapter for get_lesson_details.
 */
class get_lesson_details extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'password' => new external_value(PARAM_RAW, 'Optional lesson password', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Lesson course module id.
     * @param string $password Optional lesson password.
     * @return array
     */
    public static function execute(int $course_id, int $module_id, string $password = ''): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'password' => $password,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'password' => $password,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/lesson:view', $modulecontext);

        return get_lesson_details_operation::execute((int) $courseid, (int) $moduleid, (string) $password);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'lesson' => self::lesson_summary_structure(),
            'warnings' => get_lesson_access_information::warnings_structure(),
        ]);
    }

    /**
     * Shared Lesson summary return structure.
     *
     * @return external_single_structure
     */
    public static function lesson_summary_structure(): external_single_structure {
        return new external_single_structure([
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'lesson_id' => new external_value(PARAM_INT, 'Lesson instance id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_RAW, 'Lesson name'),
            'intro' => new external_value(PARAM_RAW, 'Rendered Lesson introduction'),
            'intro_format' => new external_value(PARAM_INT, 'Moodle intro format'),
            'language' => new external_value(PARAM_RAW, 'Forced activity language or empty string'),
            'grade' => new external_value(PARAM_INT, 'Maximum grade'),
            'practice' => new external_value(PARAM_BOOL, 'Whether this is a practice Lesson'),
            'allow_review' => new external_value(PARAM_BOOL, 'Whether review is allowed'),
            'use_password' => new external_value(PARAM_BOOL, 'Whether a Lesson password is enabled'),
            'custom_scoring' => new external_value(PARAM_BOOL, 'Whether custom scoring is enabled'),
            'ongoing_score' => new external_value(PARAM_BOOL, 'Whether ongoing score is displayed'),
            'use_max_grade' => new external_value(PARAM_BOOL, 'Whether the best grade is used across attempts'),
            'max_answers' => new external_value(PARAM_INT, 'Maximum answers per page'),
            'max_attempts' => new external_value(PARAM_INT, 'Maximum attempts per question'),
            'allow_question_retry' => new external_value(PARAM_BOOL, 'Whether question retry is allowed'),
            'after_correct_answer' => new external_value(PARAM_INT, 'Moodle action after a correct answer setting'),
            'default_feedback' => new external_value(PARAM_BOOL, 'Whether default feedback is displayed'),
            'minimum_questions' => new external_value(PARAM_INT, 'Minimum number of questions'),
            'pages_to_show' => new external_value(PARAM_INT, 'Number of pages to show'),
            'time_limit_seconds' => new external_value(PARAM_INT, 'Lesson time limit in seconds'),
            'retakes_allowed' => new external_value(PARAM_BOOL, 'Whether retakes are allowed'),
            'activity_link' => new external_value(PARAM_INT, 'Linked activity id or 0'),
            'slideshow' => new external_value(PARAM_BOOL, 'Whether slideshow mode is enabled'),
            'slideshow_width' => new external_value(PARAM_INT, 'Slideshow width'),
            'slideshow_height' => new external_value(PARAM_INT, 'Slideshow height'),
            'slideshow_background' => new external_value(PARAM_RAW, 'Slideshow background color'),
            'display_left_menu' => new external_value(PARAM_BOOL, 'Whether the left menu is displayed'),
            'display_left_if' => new external_value(PARAM_INT, 'Minimum grade needed to display the left menu'),
            'progress_bar' => new external_value(PARAM_BOOL, 'Whether the progress bar is displayed'),
            'available_from' => new external_value(PARAM_INT, 'Available-from timestamp'),
            'deadline' => new external_value(PARAM_INT, 'Deadline timestamp'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification timestamp'),
            'completion_end_reached' => new external_value(PARAM_BOOL, 'Whether completion requires reaching the end'),
            'completion_time_spent_seconds' => new external_value(PARAM_INT, 'Required time spent for completion'),
            'allow_offline_attempts' => new external_value(PARAM_BOOL, 'Whether offline attempts are allowed'),
            'intro_files_count' => new external_value(PARAM_INT, 'Number of intro files'),
            'media_files_count' => new external_value(PARAM_INT, 'Number of media files'),
        ]);
    }
}
