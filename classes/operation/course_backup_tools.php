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

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * Create a native Moodle course backup.
     *
     * @param int $courseid Courseid.
     * @param array $options Options.
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
     * @param int $fileid Fileid.
     * @param string $target Target.
     * @param int $targetcourseid Targetcourseid.
     * @param int $categoryid Categoryid.
     * @param string $fullname Fullname.
     * @param string $shortname Shortname.
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
        $deletecourseonfailure = $target === 'new_course';

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

            $precheckpassed = $controller->execute_precheck();
            $precheckresults = $precheckpassed ? [] : $controller->get_precheck_results();
            $warnings = self::normalise_precheck_notices(
                $precheckresults['warnings'] ?? [],
                'restore_precheck_warning'
            );
            $errors = self::normalise_precheck_notices(
                $precheckresults['errors'] ?? [],
                'restore_precheck_error'
            );
            if ($errors !== []) {
                throw new \moodle_exception(
                    'restoreprecheckfailed',
                    'local_moodlia',
                    '',
                    self::precheck_message($errors)
                );
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
        } catch (\Throwable $error) {
            if ($controller !== null) {
                $controller->destroy();
                $controller = null;
            }
            if ($deletecourseonfailure) {
                self::delete_failed_restore_course($courseid);
            }
            throw $error;
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
     * @param int $fileid Fileid.
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
     * @param \stored_file $file File.
     * @param int $courseid Courseid.
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
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function upload_backup_file(
        string $filename,
        string $uploadreference = '',
        int $draftitemid = 0
    ): array {
        global $USER;

        self::require_backup_api();

        $filename = clean_param(trim($filename), PARAM_FILE);
        if ($filename === '') {
            throw new \invalid_parameter_exception('filename is required.');
        }
        if (strtolower(substr($filename, -4)) !== '.mbz') {
            throw new \invalid_parameter_exception('filename must end with .mbz.');
        }

        $context = \context_user::instance((int) $USER->id);
        $draftfile = module_file_tools::prepare_user_draft_file(
            $filename,
            $uploadreference,
            $draftitemid,
            $context
        );
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

        try {
            if ($existing && !$existing->is_directory()) {
                $existing->replace_file_with($draftfile);
                $existing->set_timemodified(time());
                $file = $existing;
            } else {
                $file = $fs->create_file_from_storedfile($filerecord, $draftfile);
            }
        } finally {
            $draftfile->delete();
        }

        return self::backup_file_to_response($file, 0);
    }

    /**
     * List .mbz backup files available to the current user.
     *
     * @param int $courseid Courseid.
     * @param bool $includeprivate Includeprivate.
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
     * @param int $fileid Fileid.
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
     * @param \backup_controller $controller Controller.
     * @param array $options Options.
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
     * @param \base_controller $controller Controller.
     * @param string $name Name.
     * @param mixed $value Value.
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
     * @param string $target Target.
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
     * @param string $target Target.
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
     * @param string $target Target.
     * @param int $targetcourseid Targetcourseid.
     * @param int $categoryid Categoryid.
     * @param string $fullname Fullname.
     * @param string $shortname Shortname.
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
     * Convert Moodle restore precheck notices to the operation warning format.
     *
     * @param mixed $notices Notices.
     * @param string $code Code.
     * @return array
     */
    private static function normalise_precheck_notices($notices, string $code): array {
        $messages = [];
        self::collect_precheck_messages($notices, $messages);

        $result = [];
        foreach (array_values(array_unique($messages)) as $message) {
            $result[] = [
                'code' => $code,
                'message' => $message,
            ];
        }

        return $result;
    }

    /**
     * Collect readable messages from Moodle's mixed precheck result structure.
     *
     * @param mixed $value Value.
     * @param array $messages Messages.
     */
    private static function collect_precheck_messages($value, array &$messages): void {
        if (is_array($value)) {
            if (array_key_exists('message', $value)) {
                self::collect_precheck_messages($value['message'], $messages);
                return;
            }
            foreach ($value as $item) {
                self::collect_precheck_messages($item, $messages);
            }
            return;
        }

        if ($value instanceof \Throwable) {
            self::collect_precheck_messages($value->getMessage(), $messages);
            return;
        }
        if (is_object($value)) {
            if (isset($value->message)) {
                self::collect_precheck_messages($value->message, $messages);
                return;
            }
            if (method_exists($value, '__toString')) {
                self::collect_precheck_messages((string) $value, $messages);
                return;
            }
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if (!is_scalar($value) || is_bool($value) || $value === null) {
            return;
        }

        $message = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $message = preg_replace('/\s+/u', ' ', $message);
        $message = trim($message ?? '');
        if ($message !== '') {
            $messages[] = $message;
        }
    }

    /**
     * Convert normalised restore errors to a compact exception message.
     *
     * @param array $errors Errors.
     * @return string
     */
    private static function precheck_message(array $errors): string {
        $messages = array_column($errors, 'message');
        return $messages === [] ? 'Restore precheck failed.' : implode('; ', $messages);
    }

    /**
     * Remove a course created solely for a restore that did not complete.
     *
     * @param int $courseid Courseid.
     */
    private static function delete_failed_restore_course(int $courseid): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            return;
        }

        try {
            delete_course($course, false);
        } catch (\Throwable $cleanupfailure) {
            debugging(
                'MoodlIA could not remove the incomplete restore course: ' . $cleanupfailure->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}
