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

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for course section operations.
 */
class section_tools {
    /**
     * Load Moodle course APIs and return a course object.
     *
     * @param int $courseid Moodle course id.
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
     * @param \stdClass $course Moodle course.
     * @param int|null $sectionid Course section id.
     * @param int|null $sectionnumber Course section number.
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
     * @param \stdClass $course Moodle course.
     * @param \section_info $section Course section.
     * @return array
     */
    public static function to_response(\stdClass $course, \section_info $section): array {
        $coursecontext = \context_course::instance((int) $course->id);

        return [
            'section_id' => (int) $section->id,
            'course_id' => (int) $course->id,
            'section_number' => (int) $section->section,
            'name' => get_section_name($course, $section),
            'summary' => format_text(
                $section->summary ?? '',
                $section->summaryformat ?? FORMAT_HTML,
                ['context' => $coursecontext]
            ),
            'visible' => (bool) $section->visible,
        ];
    }

    /**
     * Reload a section after a write operation.
     *
     * @param \stdClass $course Moodle course.
     * @param int $sectionid Course section id.
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
