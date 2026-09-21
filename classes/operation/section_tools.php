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
 * Shared section helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for course section operations.
 */
class section_tools {
    /**
     * Load Moodle course APIs and return a course object.
     *
     * @param int $courseid Courseid.
     * @return \stdClass
     */
    public static function get_course(int $courseid): \stdClass {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('course_id must be a positive integer.');
        }

        return get_course($courseid);
    }

    /**
     * Resolve a section from either id or section number.
     *
     * @param \stdClass $course Course.
     * @param int|null $sectionid Sectionid.
     * @param int|null $sectionnumber Sectionnumber.
     * @return \section_info
     */
    public static function get_section(\stdClass $course, ?int $sectionid, ?int $sectionnumber): \section_info {
        if (!empty($sectionid)) {
            $section = get_fast_modinfo($course)->get_section_info_by_id($sectionid);
        } else if ($sectionnumber !== null && $sectionnumber >= 0) {
            $section = get_fast_modinfo($course)->get_section_info($sectionnumber);
        } else {
            throw new \invalid_parameter_exception('Either section_id or section_number is required.');
        }

        if (!$section) {
            throw new \moodle_exception('sectionnotexist');
        }

        return $section;
    }

    /**
     * Return the canonical section response shape.
     *
     * @param \stdClass $course Course.
     * @param \section_info $section Section.
     * @return array
     */
    public static function to_response(\stdClass $course, \section_info $section): array {
        return [
            'section_id' => (int) $section->id,
            'course_id' => (int) $course->id,
            'section_number' => (int) $section->section,
            'name' => get_section_name($course, $section),
            'summary' => self::render_summary($course, $section),
            'summary_raw' => (string) ($section->summary ?? ''),
            'summary_format' => course_tools::format_from_constant((int) ($section->summaryformat ?? FORMAT_HTML)),
            'summary_files' => self::summary_files_to_response($course, $section),
            'visible' => (bool) $section->visible,
        ];
    }

    /**
     * Render a section summary and resolve its existing section-file references.
     *
     * @param \stdClass $course Course.
     * @param \section_info $section Section.
     * @return string
     */
    public static function render_summary(\stdClass $course, \section_info $section): string {
        $coursecontext = \context_course::instance((int) $course->id);
        $summary = file_rewrite_pluginfile_urls(
            (string) ($section->summary ?? ''),
            'pluginfile.php',
            $coursecontext->id,
            'course',
            'section',
            (int) $section->id
        );

        return format_text(
            $summary,
            $section->summaryformat ?? FORMAT_HTML,
            [
                'context' => $coursecontext,
                // The API response must retain supported portable HTML elements.
                'clean' => false,
            ]
        );
    }

    /**
     * Attach a user draft file to a section summary file area.
     *
     * @param \stdClass $course Course.
     * @param \section_info $section Section.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function attach_summary_file(
        \stdClass $course,
        \section_info $section,
        string $filename,
        string $uploadreference,
        int $draftitemid
    ): array {
        $coursecontext = \context_course::instance((int) $course->id);
        $draftfile = module_file_tools::prepare_user_draft_file(
            $filename,
            $uploadreference,
            $draftitemid,
            $coursecontext,
            (int) ($course->maxbytes ?? 0)
        );
        $filestorage = get_file_storage();
        $filepath = $draftfile->get_filepath();
        $filename = $draftfile->get_filename();
        $existing = $filestorage->get_file(
            $coursecontext->id,
            'course',
            'section',
            (int) $section->id,
            $filepath,
            $filename
        );
        if ($existing && !$existing->is_directory()) {
            $existing->delete();
        }

        $storedfile = $filestorage->create_file_from_storedfile([
            'contextid' => $coursecontext->id,
            'component' => 'course',
            'filearea' => 'section',
            'itemid' => (int) $section->id,
            'filepath' => $filepath,
            'filename' => $filename,
        ], $draftfile);

        return self::summary_file_to_response($section, $storedfile);
    }

    /**
     * Attach every file in one user draft to a section summary file area.
     *
     * Existing files with matching paths are replaced and unrelated files are preserved.
     *
     * @param \stdClass $course Course.
     * @param \section_info $section Section.
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @param int $draftitemid Draftitemid.
     * @return array
     */
    public static function attach_summary_files(
        \stdClass $course,
        \section_info $section,
        string $filename,
        string $uploadreference,
        int $draftitemid
    ): array {
        global $USER;

        $coursecontext = \context_course::instance((int) $course->id);
        $sources = [];
        if ($draftitemid > 0) {
            $usercontext = \context_user::instance((int) $USER->id);
            $sources = array_values(get_file_storage()->get_area_files(
                $usercontext->id,
                'user',
                'draft',
                $draftitemid,
                'filepath, filename',
                false
            ));
            if (!$sources) {
                throw new \invalid_parameter_exception(
                    'draft_item_id must reference at least one file in the current user draft area.'
                );
            }
        } else {
            $sources[] = module_file_tools::prepare_user_draft_file(
                $filename,
                $uploadreference,
                0,
                $coursecontext,
                (int) ($course->maxbytes ?? 0)
            );
        }

        $filestorage = get_file_storage();
        $responses = [];
        foreach ($sources as $source) {
            module_file_tools::require_file_size_within_moodle_upload_limit(
                (int) $source->get_filesize(),
                $coursecontext,
                (int) ($course->maxbytes ?? 0)
            );
            $existing = $filestorage->get_file(
                $coursecontext->id,
                'course',
                'section',
                (int) $section->id,
                $source->get_filepath(),
                $source->get_filename()
            );
            if ($existing && !$existing->is_directory()) {
                $existing->delete();
            }
            $storedfile = $filestorage->create_file_from_storedfile([
                'contextid' => $coursecontext->id,
                'component' => 'course',
                'filearea' => 'section',
                'itemid' => (int) $section->id,
                'filepath' => $source->get_filepath(),
                'filename' => $source->get_filename(),
            ], $source);
            $responses[] = self::summary_file_to_response($section, $storedfile);
        }

        return $responses;
    }

    /**
     * Return every file owned by a section summary.
     *
     * @param \stdClass $course Course.
     * @param \section_info $section Section.
     * @return array
     */
    public static function summary_files_to_response(\stdClass $course, \section_info $section): array {
        $coursecontext = \context_course::instance((int) $course->id);
        $files = get_file_storage()->get_area_files(
            $coursecontext->id,
            'course',
            'section',
            (int) $section->id,
            'filepath, filename',
            false
        );

        return array_map(
            static fn(\stored_file $file): array => self::summary_file_to_response($section, $file),
            array_values($files)
        );
    }

    /**
     * Return the canonical section summary file response shape.
     *
     * @param \section_info $section Section.
     * @param \stored_file $file File.
     * @return array
     */
    private static function summary_file_to_response(\section_info $section, \stored_file $file): array {
        $url = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            'course',
            'section',
            (int) $section->id,
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
            'content_hash' => $file->get_contenthash(),
            'time_modified' => (int) $file->get_timemodified(),
        ];
    }

    /**
     * Reload a section after a write operation.
     *
     * @param \stdClass $course Course.
     * @param int $sectionid Sectionid.
     * @return \section_info
     */
    public static function reload_section(\stdClass $course, int $sectionid): \section_info {
        rebuild_course_cache($course->id, true);

        $section = get_fast_modinfo($course)->get_section_info_by_id($sectionid);
        if (!$section) {
            throw new \moodle_exception('sectionnotexist');
        }

        return $section;
    }
}
