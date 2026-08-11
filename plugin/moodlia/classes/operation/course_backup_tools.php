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
 * Native Moodle course backup and restore helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Wraps Moodle backup and restore controllers behind a transport-safe API.
 */
class course_backup_tools {
    /** @var array Supported restore targets. */
    public const RESTORE_TARGETS = ['new_course', 'existing_add', 'existing_delete'];

    /**
     * Load Moodle backup APIs.
     */
    public static function require_backup_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * Create a native Moodle course backup.
     *
     * @param int $courseid Moodle course id.
     * @param array $options Backup options.
     * @return array
     */
    public static function backup_course(int $courseid, array $options): array {
        global $USER;

        self::require_backup_api();

        $course = course_tools::get_course($courseid);
        $controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            (int) $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            (int) $USER->id
        );

        try {
            self::apply_backup_options($controller, $options);
            $controller->execute_plan();
            $results = $controller->get_results();
            $file = $results['backup_destination'] ?? null;
            if (!$file instanceof \stored_file) {
                throw new \moodle_exception('backupmissingfile', 'backup');
            }

            return self::backup_file_to_response($file, (int) $course->id);
        } finally {
            $controller->destroy();
        }
    }

    /**
     * Restore a native Moodle backup.
     *
     * @param int $fileid Stored backup file id.
     * @param string $target Restore target.
     * @param int $targetcourseid Existing target course id.
     * @param int $categoryid New course category id.
     * @param string $fullname New course fullname.
     * @param string $shortname New course shortname.
     * @return array
     */
    public static function restore_course_backup(
        int $fileid,
        string $target,
        int $targetcourseid,
        int $categoryid,
        string $fullname,
        string $shortname
    ): array {
        global $USER;

        self::require_backup_api();

        $target = self::normalise_restore_target($target);
        $file = self::get_backup_file($fileid);
        $courseid = self::resolve_restore_course($target, $targetcourseid, $categoryid, $fullname, $shortname);
        $tempdir = \restore_controller::get_tempdir_name($courseid, (int) $USER->id);
        $path = make_backup_temp_directory($tempdir);
        $warnings = [];

        $controller = null;
        try {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $path);
            $controller = new \restore_controller(
                $tempdir,
                $courseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                (int) $USER->id,
                self::restore_target_constant($target)
            );

            $precheck = $controller->execute_precheck();
            if ($precheck !== true) {
                $warnings[] = [
                    'code' => 'restore_precheck',
                    'message' => self::precheck_message($precheck),
                ];
                throw new \moodle_exception('restoreprecheckfailed', 'backup');
            }

            $controller->execute_plan();
            rebuild_course_cache($courseid, true);
            $course = course_tools::get_course($courseid);

            return [
                'course_id' => (int) $course->id,
                'target' => $target,
                'restored' => true,
                'fullname' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
                'shortname' => (string) $course->shortname,
                'category_id' => (int) $course->category,
                'warnings_json' => course_workflow_tools::encode_json($warnings),
            ];
        } finally {
            if ($controller !== null) {
                $controller->destroy();
            }
            if (is_dir($path)) {
                fulldelete($path);
            }
        }
    }

    /**
     * Return a stored backup file owned by Moodle's file API.
     *
     * @param int $fileid Stored file id.
     * @return \stored_file
     */
    public static function get_backup_file(int $fileid): \stored_file {
        if ($fileid <= 0) {
            throw new \invalid_parameter_exception('backup_file_id must be a positive integer.');
        }

        $file = get_file_storage()->get_file_by_id($fileid);
        if (!$file || $file->is_directory()) {
            throw new \invalid_parameter_exception('backup_file_id must reference an existing Moodle backup file.');
        }

        $filename = $file->get_filename();
        if (strtolower(substr($filename, -4)) !== '.mbz') {
            throw new \invalid_parameter_exception('backup_file_id must reference a .mbz Moodle backup file.');
        }

        return $file;
    }

    /**
     * Return a canonical backup file response.
     *
     * @param \stored_file $file Stored file.
     * @param int $courseid Source course id.
     * @return array
     */
    public static function backup_file_to_response(\stored_file $file, int $courseid): array {
        $url = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        );

        return [
            'course_id' => $courseid,
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
     * Store an uploaded .mbz file in the current user's private files.
     *
     * @param string $filename Backup filename.
     * @param string $uploadreference Base64-encoded backup content.
     * @return array
     */
    public static function upload_backup_file(string $filename, string $uploadreference): array {
        global $USER;

        self::require_backup_api();

        $filename = clean_param(trim($filename), PARAM_FILE);
        if ($filename === '') {
            throw new \invalid_parameter_exception('filename is required.');
        }
        if (strtolower(substr($filename, -4)) !== '.mbz') {
            throw new \invalid_parameter_exception('filename must end with .mbz.');
        }

        $content = base64_decode($uploadreference, true);
        if ($content === false) {
            throw new \invalid_parameter_exception('upload_reference must be base64-encoded backup content.');
        }

        $maxbytes = 20 * 1024 * 1024;
        if (strlen($content) > $maxbytes) {
            throw new \invalid_parameter_exception('Backup content exceeds the 20 MB API limit.');
        }

        $context = \context_user::instance((int) $USER->id);
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'private',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $existing = $fs->get_file($context->id, 'user', 'private', 0, '/', $filename);

        if ($existing && !$existing->is_directory()) {
            $draftitemid = file_get_unused_draft_itemid();
            $draftfile = $fs->create_file_from_string([
                'contextid' => $context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => $filename,
            ], $content);

            try {
                $existing->replace_file_with($draftfile);
                $existing->set_timemodified(time());
                $file = $existing;
            } finally {
                $draftfile->delete();
            }
        } else {
            $file = $fs->create_file_from_string($filerecord, $content);
        }

        return self::backup_file_to_response($file, 0);
    }

    /**
     * List .mbz backup files available to the current user.
     *
     * @param int $courseid Optional course id for course backup area files.
     * @param bool $includeprivate Include current user's private .mbz files.
     * @return array
     */
    public static function list_backup_files(int $courseid = 0, bool $includeprivate = true): array {
        global $USER;

        self::require_backup_api();

        $files = [];
        $fs = get_file_storage();

        if ($includeprivate) {
            $usercontext = \context_user::instance((int) $USER->id);
            foreach (['backup', 'private'] as $userfilearea) {
                foreach ($fs->get_area_files($usercontext->id, 'user', $userfilearea, 0, 'timemodified DESC', false) as $file) {
                    if (!$file->is_directory() && self::is_backup_filename($file->get_filename())) {
                        $files[] = self::backup_file_to_response($file, 0);
                    }
                }
            }
        }

        if ($courseid > 0) {
            $course = course_tools::get_course($courseid);
            $coursecontext = \context_course::instance((int) $course->id);
            foreach ($fs->get_area_files($coursecontext->id, 'backup', 'course', false, 'timemodified DESC', false) as $file) {
                if (!$file->is_directory() && self::is_backup_filename($file->get_filename())) {
                    $files[] = self::backup_file_to_response($file, (int) $course->id);
                }
            }
        }

        return [
            'course_id' => max(0, $courseid),
            'count' => count($files),
            'files' => $files,
        ];
    }

    /**
     * Delete a stored .mbz backup file when the caller owns or can manage its context.
     *
     * @param int $fileid Stored file id.
     * @return array
     */
    public static function delete_backup_file(int $fileid): array {
        $file = self::get_backup_file($fileid);
        $filename = $file->get_filename();
        $file->delete();

        return [
            'file_id' => $fileid,
            'filename' => $filename,
            'deleted' => true,
        ];
    }

    /**
     * Return whether a filename looks like a Moodle backup.
     *
     * @param string $filename Filename.
     * @return bool
     */
    private static function is_backup_filename(string $filename): bool {
        return strtolower(substr($filename, -4)) === '.mbz';
    }

    /**
     * Apply safe backup options when the current Moodle version exposes them.
     *
     * @param \backup_controller $controller Backup controller.
     * @param array $options Backup options.
     */
    private static function apply_backup_options(\backup_controller $controller, array $options): void {
        $defaults = [
            'users' => false,
            'role_assignments' => false,
            'activities' => true,
            'blocks' => true,
            'filters' => true,
            'comments' => false,
            'badges' => true,
            'calendarevents' => true,
            'userscompletion' => false,
            'logs' => false,
            'grade_histories' => false,
        ];

        foreach ($defaults as $setting => $default) {
            $value = array_key_exists($setting, $options) ? (bool) $options[$setting] : $default;
            self::set_plan_setting($controller, $setting, $value ? 1 : 0);
        }

        $filename = clean_param(trim((string) ($options['filename'] ?? '')), PARAM_FILE);
        if ($filename !== '') {
            if (strtolower(substr($filename, -4)) !== '.mbz') {
                $filename .= '.mbz';
            }
            self::set_plan_setting($controller, 'filename', $filename);
        }
    }

    /**
     * Set a backup or restore plan setting when available.
     *
     * @param \base_controller $controller Backup or restore controller.
     * @param string $name Setting name.
     * @param mixed $value Setting value.
     */
    private static function set_plan_setting(\base_controller $controller, string $name, $value): void {
        try {
            $controller->get_plan()->get_setting($name)->set_value($value);
        } catch (\Exception $error) {
            return;
        }
    }

    /**
     * Validate restore target.
     *
     * @param string $target Restore target.
     * @return string
     */
    private static function normalise_restore_target(string $target): string {
        $target = clean_param(strtolower(trim($target)), PARAM_ALPHANUMEXT);
        if (!in_array($target, self::RESTORE_TARGETS, true)) {
            throw new \invalid_parameter_exception('target must be one of: ' . implode(', ', self::RESTORE_TARGETS) . '.');
        }

        return $target;
    }

    /**
     * Return Moodle restore target constant.
     *
     * @param string $target Public target.
     * @return int
     */
    private static function restore_target_constant(string $target): int {
        if ($target === 'new_course') {
            return \backup::TARGET_NEW_COURSE;
        }
        if ($target === 'existing_delete') {
            return \backup::TARGET_EXISTING_DELETING;
        }

        return \backup::TARGET_EXISTING_ADDING;
    }

    /**
     * Resolve or create the target course for restore.
     *
     * @param string $target Restore target.
     * @param int $targetcourseid Existing course id.
     * @param int $categoryid Category id for new courses.
     * @param string $fullname New course fullname.
     * @param string $shortname New course shortname.
     * @return int
     */
    private static function resolve_restore_course(
        string $target,
        int $targetcourseid,
        int $categoryid,
        string $fullname,
        string $shortname
    ): int {
        if ($target !== 'new_course') {
            if ($targetcourseid <= 0) {
                throw new \invalid_parameter_exception('target_course_id is required for existing course restore targets.');
            }
            return (int) course_tools::get_course($targetcourseid)->id;
        }

        if ($categoryid <= 0) {
            throw new \invalid_parameter_exception('category_id is required when target is new_course.');
        }
        $fullname = trim($fullname);
        $shortname = trim($shortname);
        if ($fullname === '') {
            throw new \invalid_parameter_exception('fullname is required when target is new_course.');
        }
        if ($shortname === '') {
            throw new \invalid_parameter_exception('shortname is required when target is new_course.');
        }

        return (int) \restore_dbops::create_new_course($fullname, $shortname, $categoryid);
    }

    /**
     * Convert a restore precheck result to a compact message.
     *
     * @param mixed $precheck Precheck result.
     * @return string
     */
    private static function precheck_message($precheck): string {
        if (is_string($precheck)) {
            return $precheck;
        }
        $encoded = json_encode($precheck, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? 'Restore precheck failed.' : $encoded;
    }
}
