<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Update Text and media content operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Text and media activity without recreating its course module identity.
 */
class update_label {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string|null $content Content.
     * @param string|null $contentformat Contentformat.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $content = null,
        ?string $contentformat = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        global $CFG, $DB;

        module_tools::require_module_api();
        require_once($CFG->dirroot . '/course/modlib.php');

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);
        if ($cm->modname !== 'label') {
            throw new \invalid_parameter_exception('module_id must reference a Text and media activity.');
        }
        $hasupload = trim($uploadreference) !== '' || $draftitemid > 0;
        self::validate_upload($hasupload, $filename, $content, $contentformat);

        $rawcm = get_coursemodule_from_id('label', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $moduleinfo = get_moduleinfo_data($rawcm, $course);
        $rawcm = $moduleinfo[0];
        $moduledata = $moduleinfo[3];
        $label = $DB->get_record('label', ['id' => (int) $cm->instance], '*', MUST_EXIST);
        $labelcontent = $content ?? (string) $label->intro;
        $labelformat = $contentformat === null
            ? (int) $label->introformat
            : course_tools::format_to_constant($contentformat);

        if ($hasupload) {
            $editor = module_file_tools::prepare_editor_draft(
                \context_module::instance((int) $cm->id),
                'mod_label',
                'intro',
                0,
                $labelcontent,
                $filename,
                $uploadreference,
                $draftitemid,
                (int) ($course->maxbytes ?? 0)
            );
            $moduledata->introeditor = [
                'text' => $editor['content'],
                'format' => $labelformat,
                'itemid' => $editor['draft_item_id'],
            ];
        } else {
            $moduledata->introeditor['text'] = $labelcontent;
            $moduledata->introeditor['format'] = $labelformat;
        }

        update_moduleinfo($rawcm, $moduledata, $course);
        rebuild_course_cache((int) $course->id, true);
        $updatedcm = module_tools::get_course_module($course, $moduleid);
        $details = simple_activity_tools::get_label_details($course, $updatedcm);

        return [
            'module_id' => (int) $updatedcm->id,
            'instance_id' => (int) $updatedcm->instance,
            'content' => (string) $details['content'],
            'content_format' => (int) $details['content_format'],
            'files' => $details['files'],
        ];
    }

    /**
     * Validate partial editor input.
     *
     * @param bool $hasupload Hasupload.
     * @param string $filename Filename.
     * @param string|null $content Content.
     * @param string|null $contentformat Contentformat.
     */
    private static function validate_upload(
        bool $hasupload,
        string $filename,
        ?string $content,
        ?string $contentformat
    ): void {
        if ($hasupload && trim($filename) === '') {
            throw new \invalid_parameter_exception('filename is required when an upload is provided.');
        }
        if (!$hasupload && trim($filename) !== '') {
            throw new \invalid_parameter_exception('filename requires upload_reference or draft_item_id.');
        }
        if ($contentformat !== null && $content === null) {
            throw new \invalid_parameter_exception('content is required when content_format is provided.');
        }
        if ($content === null && !$hasupload) {
            throw new \invalid_parameter_exception('content or an upload is required.');
        }
    }
}
