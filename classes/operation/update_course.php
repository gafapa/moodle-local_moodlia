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
 * Update course operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle course through Moodle core APIs.
 */
class update_course {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string|null $fullname Fullname.
     * @param string|null $shortname Shortname.
     * @param bool|null $visible Visible.
     * @param string|null $summary Summary.
     * @param string|null $summaryformat Summaryformat.
     * @param string|null $format Format.
     * @param bool|null $enablecompletion Enablecompletion.
     * @param int|null $categoryid Categoryid.
     * @param int|null $startdate Startdate.
     * @param int|null $enddate Enddate.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?string $fullname = null,
        ?string $shortname = null,
        ?bool $visible = null,
        ?string $summary = null,
        ?string $summaryformat = null,
        ?string $format = null,
        ?bool $enablecompletion = null,
        ?int $categoryid = null,
        ?int $startdate = null,
        ?int $enddate = null
    ): array {
        $course = course_tools::get_course($courseid);

        $data = (object) ['id' => $course->id];
        $haschanges = false;

        if ($fullname !== null) {
            $fullname = trim($fullname);
            if ($fullname === '') {
                throw new \invalid_parameter_exception('fullname cannot be empty when provided.');
            }
            $data->fullname = $fullname;
            $haschanges = true;
        }

        if ($shortname !== null) {
            $shortname = trim($shortname);
            if ($shortname === '') {
                throw new \invalid_parameter_exception('shortname cannot be empty when provided.');
            }
            $data->shortname = $shortname;
            $haschanges = true;
        }

        if ($visible !== null) {
            $data->visible = $visible ? 1 : 0;
            $haschanges = true;
        }

        if ($summary !== null) {
            $data->summary = $summary;
            $data->summaryformat = course_tools::format_to_constant($summaryformat ?? 'html');
            $haschanges = true;
        } else if ($summaryformat !== null) {
            throw new \invalid_parameter_exception('summary is required when summary_format is provided.');
        }

        if ($format !== null) {
            $data->format = course_tools::normalise_course_format($format);
            $haschanges = true;
        }

        if ($enablecompletion !== null) {
            $data->enablecompletion = $enablecompletion ? 1 : 0;
            $haschanges = true;
        }

        if ($categoryid !== null) {
            $category = course_tools::get_category($categoryid);
            $data->category = (int) $category->id;
            $haschanges = true;
        }

        $nextstartdate = $startdate ?? (int) ($course->startdate ?? 0);
        $nextenddate = $enddate ?? (int) ($course->enddate ?? 0);
        if ($startdate !== null || $enddate !== null) {
            course_tools::validate_course_dates($nextstartdate, $nextenddate);
            $data->startdate = $nextstartdate;
            $data->enddate = $nextenddate;
            $haschanges = true;
        }

        if (!$haschanges) {
            throw new \invalid_parameter_exception('At least one course field is required.');
        }

        update_course($data);
        rebuild_course_cache($course->id, true);

        foreach ((array) $data as $field => $value) {
            if ($field !== 'id') {
                $course->$field = $value;
            }
        }

        return course_tools::to_response($course);
    }
}
