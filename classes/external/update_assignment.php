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
 * Update assignment external function.
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
use local_moodlia\operation\assignment_tools;
use local_moodlia\operation\update_assignment as update_assignment_operation;

/**
 * External API adapter for update_assignment.
 */
class update_assignment extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Assignment course module id'),
            'name' => new external_value(PARAM_TEXT, 'Assignment name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'intro' => new external_value(PARAM_RAW, 'Assignment description HTML', VALUE_DEFAULT, null, NULL_ALLOWED),
            'intro_format' => new external_value(
                PARAM_ALPHA,
                'Assignment description format: html or plain',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'activity' => new external_value(
                PARAM_RAW,
                'Assignment activity instructions HTML',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'activity_format' => new external_value(
                PARAM_ALPHA,
                'Assignment activity instructions format: html or plain',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'filename' => new external_value(PARAM_FILE, 'Optional editor filename', VALUE_DEFAULT, ''),
            'upload_reference' => new external_value(
                PARAM_RAW,
                'Legacy base64-encoded editor file content',
                VALUE_DEFAULT,
                ''
            ),
            'draft_item_id' => new external_value(PARAM_INT, 'Moodle user draft item id', VALUE_DEFAULT, 0),
            'file_area' => new external_value(
                PARAM_ALPHA,
                'Target editor file area: intro or activity',
                VALUE_DEFAULT,
                'intro'
            ),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string|null $name Name.
     * @param string|null $intro Intro.
     * @param string|null $introformat Introformat.
     * @param string|null $activity Activity.
     * @param string|null $activityformat Activityformat.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @param string $filearea Filearea.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $name = null,
        ?string $intro = null,
        ?string $introformat = null,
        ?string $activity = null,
        ?string $activityformat = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0,
        string $filearea = 'intro'
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'name' => $name,
            'intro' => $intro,
            'intro_format' => $introformat,
            'activity' => $activity,
            'activity_format' => $activityformat,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
            'draft_item_id' => $draftitemid,
            'file_area' => $filearea,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course((int) $params['course_id']);
        $cm = assignment_tools::get_assignment_module($course, (int) $params['module_id']);
        $modulecontext = \context_module::instance((int) $cm->id);
        self::validate_context($modulecontext);
        require_capability('moodle/course:manageactivities', $modulecontext);

        return update_assignment_operation::execute(
            (int) $params['course_id'],
            (int) $params['module_id'],
            $params['name'],
            $params['intro'],
            $params['intro_format'],
            $params['activity'],
            $params['activity_format'],
            $params['filename'],
            $params['upload_reference'],
            (int) $params['draft_item_id'],
            $params['file_area']
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $fields = get_course_assignments::assignment_summary_fields();
        $fields['uploaded_files'] = new external_multiple_structure(new external_single_structure([
            'file_id' => new external_value(PARAM_INT, 'Moodle stored file id'),
            'filename' => new external_value(PARAM_FILE, 'Stored filename'),
            'url' => new external_value(PARAM_URL, 'Download URL'),
            'filepath' => new external_value(PARAM_PATH, 'Stored file path'),
            'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
            'mimetype' => new external_value(PARAM_RAW, 'Detected MIME type'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification time'),
            'file_area' => new external_value(PARAM_ALPHA, 'Assignment editor file area'),
        ]));

        return new external_single_structure($fields);
    }
}
