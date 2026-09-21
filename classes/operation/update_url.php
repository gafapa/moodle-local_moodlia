<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Update URL resource operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a URL resource without recreating its course module identity.
 */
class update_url {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string|null $name Name.
     * @param string|null $externalurl Externalurl.
     * @param string|null $intro Intro.
     * @param string|null $introformat Introformat.
     * @param int|null $display Display.
     * @param bool|null $printintro Printintro.
     * @param int|null $popupwidth Popupwidth.
     * @param int|null $popupheight Popupheight.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $name = null,
        ?string $externalurl = null,
        ?string $intro = null,
        ?string $introformat = null,
        ?int $display = null,
        ?bool $printintro = null,
        ?int $popupwidth = null,
        ?int $popupheight = null,
        string $filename = '',
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        global $CFG, $DB;

        module_tools::require_module_api();
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/url/locallib.php');

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);
        if ($cm->modname !== 'url') {
            throw new \invalid_parameter_exception('module_id must reference a URL resource.');
        }
        $hasupload = trim($uploadreference) !== '' || $draftitemid > 0;
        self::validate_input($hasupload, $filename, $name, $externalurl, $intro, $introformat, $display, $printintro,
            $popupwidth, $popupheight);

        $rawcm = get_coursemodule_from_id('url', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $moduleinfo = get_moduleinfo_data($rawcm, $course);
        $rawcm = $moduleinfo[0];
        $moduledata = $moduleinfo[3];
        $url = $DB->get_record('url', ['id' => (int) $cm->instance], '*', MUST_EXIST);
        $displayoptions = empty($url->displayoptions) ? [] : (array) unserialize_array($url->displayoptions);

        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty when provided.');
            }
            $moduledata->name = $name;
        }
        $moduledata->externalurl = $externalurl ?? (string) $url->externalurl;
        $moduledata->display = $display ?? (int) $url->display;
        $moduledata->printintro = (int) ($printintro ?? (bool) ($displayoptions['printintro'] ?? false));
        $moduledata->popupwidth = $popupwidth ?? (int) ($displayoptions['popupwidth'] ?? 620);
        $moduledata->popupheight = $popupheight ?? (int) ($displayoptions['popupheight'] ?? 450);
        $parameters = empty($url->parameters) ? [] : (array) unserialize_array($url->parameters);
        $parameterindex = 0;
        foreach ($parameters as $parameter => $variable) {
            $moduledata->{'parameter_' . $parameterindex} = (string) $parameter;
            $moduledata->{'variable_' . $parameterindex} = (string) $variable;
            $parameterindex++;
        }

        $introcontent = $intro ?? (string) $url->intro;
        $resolvedformat = $introformat === null
            ? (int) $url->introformat
            : course_tools::format_to_constant($introformat);
        if ($hasupload) {
            $editor = module_file_tools::prepare_editor_draft(
                \context_module::instance((int) $cm->id),
                'mod_url',
                'intro',
                0,
                $introcontent,
                $filename,
                $uploadreference,
                $draftitemid,
                (int) ($course->maxbytes ?? 0)
            );
            $moduledata->introeditor = [
                'text' => $editor['content'],
                'format' => $resolvedformat,
                'itemid' => $editor['draft_item_id'],
            ];
        } else {
            $moduledata->introeditor['text'] = $introcontent;
            $moduledata->introeditor['format'] = $resolvedformat;
        }

        update_moduleinfo($rawcm, $moduledata, $course);
        rebuild_course_cache((int) $course->id, true);
        $updatedcm = module_tools::get_course_module($course, $moduleid);
        $details = simple_activity_tools::get_url_details($course, $updatedcm);

        return [
            'module_id' => (int) $updatedcm->id,
            'instance_id' => (int) $updatedcm->instance,
            'name' => (string) $updatedcm->name,
            'external_url' => (string) $details['external_url'],
            'intro' => (string) $details['intro'],
            'intro_format' => (int) $details['intro_format'],
            'display' => (int) $details['display'],
            'print_intro' => (bool) $details['print_intro'],
            'popup_width' => (int) $details['popup_width'],
            'popup_height' => (int) $details['popup_height'],
            'files' => $details['files'],
        ];
    }

    /** Validate partial URL input. *
     * @param bool $hasupload Hasupload.
     * @param string $filename Filename.
     * @param string|null $name Name.
     * @param string|null $externalurl Externalurl.
     * @param string|null $intro Intro.
     * @param string|null $introformat Introformat.
     * @param int|null $display Display.
     * @param bool|null $printintro Printintro.
     * @param int|null $popupwidth Popupwidth.
     * @param int|null $popupheight Popupheight./
    /**
     * Validate input.
     *
     * @param bool $hasupload Hasupload.
     * @param string $filename Filename.
     * @param string|null $name Name.
     * @param string|null $externalurl Externalurl.
     * @param string|null $intro Intro.
     * @param string|null $introformat Introformat.
     * @param int|null $display Display.
     * @param bool|null $printintro Printintro.
     * @param int|null $popupwidth Popupwidth.
     * @param int|null $popupheight Popupheight.
     * @return void
     */
    private static function validate_input(
        bool $hasupload,
        string $filename,
        ?string $name,
        ?string $externalurl,
        ?string $intro,
        ?string $introformat,
        ?int $display,
        ?bool $printintro,
        ?int $popupwidth,
        ?int $popupheight
    ): void {
        if ($hasupload && trim($filename) === '') {
            throw new \invalid_parameter_exception('filename is required when an upload is provided.');
        }
        if (!$hasupload && trim($filename) !== '') {
            throw new \invalid_parameter_exception('filename requires upload_reference or draft_item_id.');
        }
        if ($introformat !== null && $intro === null) {
            throw new \invalid_parameter_exception('intro is required when intro_format is provided.');
        }
        if ($externalurl !== null && !preg_match('/^https?:\/\/[^\s]+$/i', trim($externalurl))) {
            throw new \invalid_parameter_exception('external_url must be an absolute http or https URL.');
        }
        if (($popupwidth !== null && $popupwidth < 1) || ($popupheight !== null && $popupheight < 1)) {
            throw new \invalid_parameter_exception('Popup dimensions must be positive integers.');
        }
        if ($name === null && $externalurl === null && $intro === null && $display === null && $printintro === null
                && $popupwidth === null && $popupheight === null && !$hasupload) {
            throw new \invalid_parameter_exception('At least one URL field or upload is required.');
        }
    }
}
