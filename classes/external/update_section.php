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
 * Update section external function.
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
use local_moodlia\operation\update_section as update_section_operation;

/**
 * External API adapter for update_section.
 */
class update_section extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'section_id' => new external_value(PARAM_INT, 'Course section id', VALUE_DEFAULT, null, NULL_ALLOWED),
            'section_number' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, null, NULL_ALLOWED),
            'name' => new external_value(PARAM_TEXT, 'Section name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'summary' => new external_value(PARAM_RAW, 'Section summary', VALUE_DEFAULT, null, NULL_ALLOWED),
            'summary_format' => new external_value(
                PARAM_ALPHA,
                'Section summary format: html or plain',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'visible' => new external_value(PARAM_BOOL, 'Whether the section is visible', VALUE_DEFAULT, null, NULL_ALLOWED),
            'filename' => new external_value(PARAM_FILE, 'Optional section summary filename', VALUE_DEFAULT, ''),
            'upload_reference' => new external_value(
                PARAM_RAW,
                'Legacy base64-encoded section summary file content',
                VALUE_DEFAULT,
                ''
            ),
            'draft_item_id' => new external_value(PARAM_INT, 'Moodle user draft item id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int|null $sectionid Sectionid.
     * @param int|null $sectionnumber Sectionnumber.
     * @param string|null $name Name.
     * @param string|null $summary Summary.
     * @param string|null $summaryformat Summaryformat.
     * @param bool|null $visible Visible.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?int $sectionid = null,
        ?int $sectionnumber = null,
        ?string $name = null,
        ?string $summary = null,
        ?string $summaryformat = null,
        ?bool $visible = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        [
            'course_id' => $courseid,
            'section_id' => $sectionid,
            'section_number' => $sectionnumber,
            'name' => $name,
            'summary' => $summary,
            'summary_format' => $summaryformat,
            'visible' => $sectionvisible,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
            'draft_item_id' => $draftitemid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'section_id' => $sectionid,
            'section_number' => $sectionnumber,
            'name' => $name,
            'summary' => $summary,
            'summary_format' => $summaryformat,
            'visible' => $visible,
            'filename' => $filename,
            'upload_reference' => $uploadreference,
            'draft_item_id' => $draftitemid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:update', $coursecontext);

        return update_section_operation::execute(
            (int) $courseid,
            $sectionid === null ? null : (int) $sectionid,
            $sectionnumber === null ? null : (int) $sectionnumber,
            $name,
            $summary,
            $summaryformat,
            $sectionvisible === null ? null : (bool) $sectionvisible,
            $filename,
            $uploadreference,
            (int) $draftitemid
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'section_id' => new external_value(PARAM_INT, 'Moodle course section id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'section_number' => new external_value(PARAM_INT, 'Course section number'),
            'name' => new external_value(PARAM_TEXT, 'Resolved section name'),
            'summary' => new external_value(PARAM_RAW, 'Rendered section summary'),
            'summary_format' => new external_value(PARAM_ALPHA, 'Section summary format'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the section is visible'),
            'uploaded_files' => new external_multiple_structure(new external_single_structure([
                'file_id' => new external_value(PARAM_INT, 'Moodle stored file id'),
                'filename' => new external_value(PARAM_FILE, 'Stored filename'),
                'url' => new external_value(PARAM_URL, 'Download URL'),
                'filepath' => new external_value(PARAM_PATH, 'Stored file path'),
                'filesize' => new external_value(PARAM_INT, 'File size in bytes'),
                'mimetype' => new external_value(PARAM_RAW, 'Detected MIME type'),
                'time_modified' => new external_value(PARAM_INT, 'Last modification time'),
            ])),
        ]);
    }
}
