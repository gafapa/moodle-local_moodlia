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
 * Update section operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle course section through Moodle core APIs.
 */
class update_section {
    /**
     * Execute the operation.
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
        global $DB;

        $course = section_tools::get_course($courseid);
        $section = section_tools::get_section($course, $sectionid, $sectionnumber);

        $data = [];
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty when provided.');
            }
            $data['name'] = $name;
        }

        if ($summary !== null) {
            $data['summary'] = $summary;
            $data['summaryformat'] = $summaryformat === null
                ? (int) ($section->summaryformat ?? FORMAT_HTML)
                : course_tools::format_to_constant($summaryformat);
        } else if ($summaryformat !== null) {
            throw new \invalid_parameter_exception('summary is required when summary_format is provided.');
        }

        if ($visible !== null) {
            $data['visible'] = $visible ? 1 : 0;
        }

        $hasuploadreference = trim($uploadreference) !== '';
        $hasdraftitem = $draftitemid > 0;
        $hasfilename = trim($filename) !== '';
        $hasupload = $hasuploadreference || $hasdraftitem || $hasfilename;
        if ($hasupload && !$hasfilename) {
            throw new \invalid_parameter_exception('filename is required when attaching a section file.');
        }
        if ($hasupload && $hasuploadreference === $hasdraftitem) {
            throw new \invalid_parameter_exception(
                'Provide exactly one of upload_reference or draft_item_id when attaching a section file.'
            );
        }

        if (!$data && !$hasupload) {
            throw new \invalid_parameter_exception('At least one section field or file upload is required.');
        }

        $transaction = $DB->start_delegated_transaction();
        if ($data) {
            course_update_section($course, $section, $data);
        }
        $uploadedfiles = [];
        if ($hasupload) {
            $uploadedfiles[] = section_tools::attach_summary_file(
                $course,
                $section,
                $filename,
                $uploadreference,
                $draftitemid
            );
        }
        $transaction->allow_commit();

        $section = section_tools::reload_section($course, (int) $section->id);

        $response = section_tools::to_response($course, $section);
        $response['uploaded_files'] = $uploadedfiles;

        return $response;
    }
}
