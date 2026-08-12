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
 * Shared course helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for course operations.
 */
class course_tools {
    /**
     * Load Moodle course APIs.
     */
    public static function require_course_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
    }

    /**
     * Resolve a valid course category id.
     *
     * @param int $categoryid Requested category id, or 0 for Moodle default.
     * @return int
     */
    public static function resolve_category_id(int $categoryid = 0): int {
        self::require_course_api();

        if ($categoryid > 0) {
            \core_course_category::get($categoryid, MUST_EXIST, true);
            return $categoryid;
        }

        return (int) \core_course_category::get_default()->id;
    }

    /**
     * Load a Moodle course category.
     *
     * @param int $categoryid Moodle course category id.
     * @return \core_course_category
     */
    public static function get_category(int $categoryid): \core_course_category {
        self::require_course_api();

        if ($categoryid <= 0) {
            throw new \invalid_parameter_exception('category_id must be a positive integer.');
        }

        return \core_course_category::get($categoryid, MUST_EXIST, true);
    }

    /**
     * Return the canonical course category response shape.
     *
     * @param \core_course_category $category Moodle course category.
     * @return array
     */
    public static function category_to_response(\core_course_category $category): array {
        $url = new \moodle_url('/course/index.php', ['categoryid' => $category->id]);
        $coursecount = isset($category->coursecount) ? (int) $category->coursecount : 0;

        return [
            'category_id' => (int) $category->id,
            'name' => format_string($category->name, true, ['context' => \context_coursecat::instance($category->id)]),
            'parent_id' => (int) $category->parent,
            'visible' => (bool) $category->visible,
            'course_count' => $coursecount,
            'url' => $url->out(false),
        ];
    }

    /**
     * Load a Moodle course.
     *
     * @param int $courseid Moodle course id.
     * @return \stdClass
     */
    public static function get_course(int $courseid): \stdClass {
        self::require_course_api();

        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('course_id must be a positive integer.');
        }

        return get_course($courseid);
    }

    /**
     * Return the canonical course response shape.
     *
     * @param \stdClass $course Moodle course.
     * @return array
     */
    public static function to_response(\stdClass $course): array {
        $context = \context_course::instance($course->id);

        return [
            'course_id' => (int) $course->id,
            'shortname' => format_string($course->shortname, true, ['context' => $context]),
            'fullname' => format_string($course->fullname, true, ['context' => $context]),
            'category_id' => (int) $course->category,
            'visible' => (bool) $course->visible,
            'summary' => format_text((string) ($course->summary ?? ''), (int) ($course->summaryformat ?? FORMAT_HTML), [
                'context' => $context,
                'overflowdiv' => true,
            ]),
            'summary_format' => self::format_from_constant((int) ($course->summaryformat ?? FORMAT_HTML)),
            'format' => (string) ($course->format ?? ''),
            'enable_completion' => (bool) ($course->enablecompletion ?? false),
            'start_date' => (int) ($course->startdate ?? 0),
            'end_date' => (int) ($course->enddate ?? 0),
            'url' => course_get_url($course)->out(false),
        ];
    }

    /**
     * Convert a public text format name to a Moodle format constant.
     *
     * @param string $format Public format name.
     * @return int
     */
    public static function format_to_constant(string $format): int {
        $format = clean_param($format ?: 'html', PARAM_ALPHA);
        if ($format === 'html') {
            return FORMAT_HTML;
        }
        if ($format === 'plain') {
            return FORMAT_PLAIN;
        }

        throw new \invalid_parameter_exception('summary_format must be one of: html, plain.');
    }

    /**
     * Convert a Moodle text format constant to a public format name.
     *
     * @param int $format Moodle format constant.
     * @return string
     */
    public static function format_from_constant(int $format): string {
        return $format === FORMAT_PLAIN ? 'plain' : 'html';
    }

    /**
     * Validate a course format plugin name.
     *
     * @param string $format Moodle course format plugin name.
     * @return string
     */
    public static function normalise_course_format(string $format): string {
        $format = clean_param(trim($format ?: 'topics'), PARAM_PLUGIN);
        if ($format === '') {
            throw new \invalid_parameter_exception('format cannot be empty.');
        }

        $formats = \core_component::get_plugin_list('format');
        if (!array_key_exists($format, $formats)) {
            throw new \invalid_parameter_exception('format must reference an installed Moodle course format.');
        }

        return $format;
    }

    /**
     * Validate public course date fields.
     *
     * @param int $startdate Course start timestamp.
     * @param int $enddate Course end timestamp, or 0.
     */
    public static function validate_course_dates(int $startdate, int $enddate): void {
        if ($startdate < 0) {
            throw new \invalid_parameter_exception('start_date must be zero or a positive Unix timestamp.');
        }
        if ($enddate < 0) {
            throw new \invalid_parameter_exception('end_date must be zero or a positive Unix timestamp.');
        }
        if ($enddate > 0 && $startdate > 0 && $enddate <= $startdate) {
            throw new \invalid_parameter_exception('end_date must be greater than start_date when both are provided.');
        }
    }
}
