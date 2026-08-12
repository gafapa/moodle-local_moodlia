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
 * Update workshop submission external function.
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
use local_moodlia\operation\update_workshop_submission as update_workshop_submission_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for update_workshop_submission.
 */
class update_workshop_submission extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'submission_id' => new external_value(PARAM_INT, 'Workshop submission id'),
            'title' => new external_value(PARAM_TEXT, 'Submission title'),
            'content' => new external_value(PARAM_RAW, 'Submission content', VALUE_DEFAULT, ''),
            'content_format' => new external_value(PARAM_ALPHA, 'Content format: html or plain', VALUE_DEFAULT, 'html'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $submissionid Submissionid.
     * @param string $title Title.
     * @param string $content Content.
     * @param string $contentformat Contentformat.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $submissionid,
        string $title,
        string $content = '',
        string $contentformat = 'html'
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'submission_id' => $submissionid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'submission_id' => $submissionid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:submit', $modulecontext);

        return update_workshop_submission_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $submissionid,
            $title,
            $content,
            $contentformat
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_workshop_submissions::submission_structure();
    }
}
