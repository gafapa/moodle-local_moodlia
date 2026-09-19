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
 * Update assignment operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates assignment authoring content through Moodle's module update API.
 */
class update_assignment {
    /**
     * Execute the operation.
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
        global $DB;

        module_tools::require_module_api();
        assignment_tools::require_assignment_api();

        $course = course_tools::get_course($courseid);
        $cm = assignment_tools::get_assignment_module($course, $moduleid);

        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty when provided.');
            }
        }
        if ($intro === null && $introformat !== null) {
            throw new \invalid_parameter_exception('intro is required when intro_format is provided.');
        }
        if ($activity === null && $activityformat !== null) {
            throw new \invalid_parameter_exception('activity is required when activity_format is provided.');
        }

        $filearea = clean_param(trim($filearea), PARAM_ALPHA);
        if (!in_array($filearea, ['intro', 'activity'], true)) {
            throw new \invalid_parameter_exception('file_area must be one of: intro, activity.');
        }

        $hasuploadreference = trim($uploadreference) !== '';
        $hasdraftitem = $draftitemid > 0;
        $hasfilename = trim($filename) !== '';
        $hasupload = $hasuploadreference || $hasdraftitem || $hasfilename;
        if ($hasupload && !$hasfilename) {
            throw new \invalid_parameter_exception('filename is required when attaching an assignment file.');
        }
        if ($hasupload && $hasuploadreference === $hasdraftitem) {
            throw new \invalid_parameter_exception(
                'Provide exactly one of upload_reference or draft_item_id when attaching an assignment file.'
            );
        }
        if ($name === null && $intro === null && $activity === null && !$hasupload) {
            throw new \invalid_parameter_exception('At least one assignment field or file upload is required.');
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            [$rawcm, $moduledata] = assignment_tools::prepare_update_data($course, $cm);

            if ($name !== null) {
                $moduledata->name = $name;
            }
            if ($intro !== null) {
                $moduledata->introeditor['text'] = $intro;
                $moduledata->introeditor['format'] = $introformat === null
                    ? (int) ($moduledata->introformat ?? FORMAT_HTML)
                    : course_tools::format_to_constant($introformat);
            }

            if ($activity !== null || ($hasupload && $filearea === 'activity')) {
                assignment_tools::prepare_activity_editor($moduledata, $cm);
                if ($activity !== null) {
                    $moduledata->activityeditor['text'] = $activity;
                    $moduledata->activityeditor['format'] = $activityformat === null
                        ? (int) ($moduledata->activityformat ?? FORMAT_HTML)
                        : course_tools::format_to_constant($activityformat);
                }
            }

            if ($hasupload) {
                $targetdraftitemid = $filearea === 'activity'
                    ? (int) $moduledata->activityeditor['itemid']
                    : (int) $moduledata->introeditor['itemid'];
                assignment_tools::copy_upload_to_editor_draft(
                    $course,
                    $cm,
                    $filename,
                    $uploadreference,
                    $draftitemid,
                    $targetdraftitemid
                );
            }

            update_moduleinfo($rawcm, $moduledata, $course);
            rebuild_course_cache((int) $course->id, true);

            $updatedcm = assignment_tools::get_assignment_module($course, $moduleid);
            $response = assignment_tools::assignment_summary_to_response($course, $updatedcm);
            $response['uploaded_files'] = $hasupload
                ? [assignment_tools::get_editor_file_response($updatedcm, $filearea, $filename)]
                : [];

            $transaction->allow_commit();

            return $response;
        } catch (\dml_write_exception $exception) {
            $publicexception = assignment_tools::assignment_update_write_exception(
                $exception,
                (int) $course->id,
                $moduleid
            );
            $transaction->rollback($publicexception);
        }
    }
}
