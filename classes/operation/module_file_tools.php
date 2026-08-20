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
 * File helpers for Moodle module operations.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Isolates File API handling for folder and resource modules.
 */
class module_file_tools {
    /**
     * Create a user draft file for a Moodle resource module.
     *
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param \context $context Context.
     * @param int $coursebytes Coursebytes.
     * @param int $draftitemid Draftitemid.
     * @return int Draft item id.
     */
    public static function create_resource_draft_file(
        string $filename,
        string $uploadreference,
        \context $context,
        int $coursebytes = 0,
        int $draftitemid = 0
    ): int {
        $filename = clean_param(trim($filename), PARAM_FILE);
        if ($filename === '') {
            throw new \invalid_parameter_exception('options.filename is required for resource modules.');
        }

        $draftfile = self::prepare_user_draft_file(
            $filename,
            $uploadreference,
            $draftitemid,
            $context,
            $coursebytes
        );

        return (int) $draftfile->get_itemid();
    }

    /**
     * Resolve a streamed Moodle draft upload or create a legacy base64 draft.
     *
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @param \context $context Context.
     * @param int $coursebytes Coursebytes.
     * @param int $modulebytes Modulebytes.
     * @return \stored_file
     */
    public static function prepare_user_draft_file(
        string $filename,
        string $uploadreference,
        int $draftitemid,
        \context $context,
        int $coursebytes = 0,
        int $modulebytes = 0
    ): \stored_file {
        global $USER;

        $filename = clean_param(trim($filename), PARAM_FILE);
        if ($filename === '') {
            throw new \invalid_parameter_exception('filename is required.');
        }

        $hasreference = trim($uploadreference) !== '';
        $hasdraftitem = $draftitemid > 0;
        if ($hasreference === $hasdraftitem) {
            throw new \invalid_parameter_exception(
                'Provide exactly one of upload_reference or draft_item_id.'
            );
        }

        $usercontext = \context_user::instance((int) $USER->id);
        $fs = get_file_storage();
        if ($hasdraftitem) {
            $file = $fs->get_file(
                $usercontext->id,
                'user',
                'draft',
                $draftitemid,
                '/',
                $filename
            );
            if (!$file || $file->is_directory()) {
                throw new \invalid_parameter_exception(
                    'draft_item_id must reference the named file in the current user draft area.'
                );
            }
            self::require_file_size_within_moodle_upload_limit(
                (int) $file->get_filesize(),
                $context,
                $coursebytes,
                $modulebytes
            );
            return $file;
        }

        $content = base64_decode($uploadreference, true);
        if ($content === false) {
            throw new \invalid_parameter_exception('upload_reference must be base64-encoded file content.');
        }
        self::require_within_moodle_upload_limit($content, $context, $coursebytes, $modulebytes);

        $draftitemid = file_get_unused_draft_itemid();
        return $fs->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
    }

    /**
     * Enforce Moodle's configured upload limit without adding a MoodlIA limit.
     *
     * @param string $content Content.
     * @param \context $context Context.
     * @param int $coursebytes Coursebytes.
     * @param int $modulebytes Modulebytes.
     */
    public static function require_within_moodle_upload_limit(
        string $content,
        \context $context,
        int $coursebytes = 0,
        int $modulebytes = 0
    ): void {
        self::require_file_size_within_moodle_upload_limit(
            strlen($content),
            $context,
            $coursebytes,
            $modulebytes
        );
    }

    /**
     * Enforce Moodle's configured upload limit for a stored file.
     *
     * @param int $filesize Filesize.
     * @param \context $context Context.
     * @param int $coursebytes Coursebytes.
     * @param int $modulebytes Modulebytes.
     */
    public static function require_file_size_within_moodle_upload_limit(
        int $filesize,
        \context $context,
        int $coursebytes = 0,
        int $modulebytes = 0
    ): void {
        global $CFG;

        $maxbytes = get_user_max_upload_file_size(
            $context,
            (int) ($CFG->maxbytes ?? 0),
            max(0, $coursebytes),
            max(0, $modulebytes)
        );
        if ($maxbytes > 0 && $filesize > $maxbytes) {
            throw new \invalid_parameter_exception('File content exceeds Moodle\'s configured upload limit.');
        }
    }

    /**
     * Verify that a course module belongs to a folder activity.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return \cm_info
     */
    public static function get_folder_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'folder') {
            throw new \invalid_parameter_exception('module_id must reference a folder activity.');
        }

        return $cm;
    }

    /**
     * Verify that a course module belongs to a file resource.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return \cm_info
     */
    public static function get_resource_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'resource') {
            throw new \invalid_parameter_exception('module_id must reference a resource activity.');
        }

        return $cm;
    }

    /**
     * Return the canonical folder file response shape.
     *
     * @param \cm_info $cm Cm.
     * @param \stored_file $file File.
     * @return array
     */
    public static function folder_file_download_to_response(\cm_info $cm, \stored_file $file): array {
        $context = \context_module::instance($cm->id);
        $url = \moodle_url::make_pluginfile_url(
            $context->id,
            'mod_folder',
            'content',
            0,
            $file->get_filepath(),
            $file->get_filename(),
            false
        );

        return [
            'file_id' => (int) $file->get_id(),
            'filename' => $file->get_filename(),
            'url' => $url->out(false),
        ];
    }

    /**
     * Return the canonical folder file list item response shape.
     *
     * @param \cm_info $cm Cm.
     * @param \stored_file $file File.
     * @return array
     */
    public static function folder_file_to_response(\cm_info $cm, \stored_file $file): array {
        $response = self::folder_file_download_to_response($cm, $file);

        return $response + [
            'filepath' => $file->get_filepath(),
            'filesize' => (int) $file->get_filesize(),
            'mimetype' => (string) ($file->get_mimetype() ?? ''),
            'time_modified' => (int) $file->get_timemodified(),
        ];
    }

    /**
     * Return files stored inside a folder activity.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_folder_files(\cm_info $cm): array {
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_folder', 'content', 0, 'filepath, filename', false);
        $responses = [];

        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $responses[] = self::folder_file_to_response($cm, $file);
            }
        }

        return $responses;
    }

    /**
     * Return the canonical resource file response shape.
     *
     * @param \cm_info $cm Cm.
     * @param \stored_file $file File.
     * @return array
     */
    public static function resource_file_to_response(\cm_info $cm, \stored_file $file): array {
        $context = \context_module::instance($cm->id);
        $url = \moodle_url::make_pluginfile_url(
            $context->id,
            'mod_resource',
            'content',
            0,
            $file->get_filepath(),
            $file->get_filename(),
            false
        );

        return [
            'file_id' => (int) $file->get_id(),
            'filename' => $file->get_filename(),
            'url' => $url->out(false),
            'filepath' => $file->get_filepath(),
            'filesize' => (int) $file->get_filesize(),
            'mimetype' => (string) ($file->get_mimetype() ?? ''),
            'time_modified' => (int) $file->get_timemodified(),
        ];
    }

    /**
     * Return files stored inside a resource activity.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_resource_files(\cm_info $cm): array {
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'filepath, filename', false);
        $responses = [];

        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $responses[] = self::resource_file_to_response($cm, $file);
            }
        }

        return $responses;
    }

    /**
     * Find a file inside a resource activity.
     *
     * @param \cm_info $cm Cm.
     * @param int|null $fileid Fileid.
     * @param string|null $path Path.
     * @return \stored_file
     */
    public static function get_resource_file(\cm_info $cm, ?int $fileid = null, ?string $path = null): \stored_file {
        return self::get_module_file($cm, 'mod_resource', $fileid, $path);
    }

    /**
     * Find a file inside a folder activity.
     *
     * @param \cm_info $cm Cm.
     * @param int|null $fileid Fileid.
     * @param string|null $path Path.
     * @return \stored_file
     */
    public static function get_folder_file(\cm_info $cm, ?int $fileid = null, ?string $path = null): \stored_file {
        return self::get_module_file($cm, 'mod_folder', $fileid, $path);
    }

    /**
     * Find a file inside a module content file area.
     *
     * @param \cm_info $cm Cm.
     * @param string $component Component.
     * @param int|null $fileid Fileid.
     * @param string|null $path Path.
     * @return \stored_file
     */
    private static function get_module_file(
        \cm_info $cm,
        string $component,
        ?int $fileid = null,
        ?string $path = null
    ): \stored_file {
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();

        if (!empty($fileid)) {
            $file = $fs->get_file_by_id($fileid);
            if (
                $file &&
                (int) $file->get_contextid() === (int) $context->id &&
                $file->get_component() === $component &&
                $file->get_filearea() === 'content' &&
                (int) $file->get_itemid() === 0 &&
                !$file->is_directory()
            ) {
                return $file;
            }
            throw new \moodle_exception('filenotfound');
        }

        $path = trim((string) $path);
        if ($path === '') {
            throw new \invalid_parameter_exception('Either file_id or path is required.');
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $parts = explode('/', $path);
        $filename = clean_param(array_pop($parts), PARAM_FILE);
        $filepath = '/' . implode('/', array_filter($parts, static fn($part) => $part !== '')) . '/';
        $filepath = $filepath === '//' ? '/' : $filepath;

        $file = $fs->get_file($context->id, $component, 'content', 0, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            throw new \moodle_exception('filenotfound');
        }

        return $file;
    }
}
