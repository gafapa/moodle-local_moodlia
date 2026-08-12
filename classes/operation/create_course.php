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
 * Create course operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle course through Moodle core APIs.
 */
class create_course {
    /**
     * Execute the operation.
     *
     * @param string $fullname Course full name.
     * @param string $shortname Course short name.
     * @param int $categoryid Course category id, or 0 for Moodle default.
     * @param bool $visible Whether the course is visible.
     * @param string $summary Course summary.
     * @param string $summaryformat Public summary format.
     * @param string $format Course format plugin name.
     * @param bool $enablecompletion Whether course completion tracking is enabled.
     * @param int $startdate Course start timestamp.
     * @param int $enddate Course end timestamp, or 0.
     * @return array
     */
    public static function execute(
        string $fullname,
        string $shortname,
        int $categoryid = 0,
        bool $visible = true,
        string $summary = '',
        string $summaryformat = 'html',
        string $format = 'topics',
        bool $enablecompletion = false,
        int $startdate = 0,
        int $enddate = 0
    ): array {
        $fullname = trim($fullname);
        $shortname = trim($shortname);

        if ($fullname === '') {
            throw new \invalid_parameter_exception('fullname is required.');
        }
        if ($shortname === '') {
            throw new \invalid_parameter_exception('shortname is required.');
        }

        $categoryid = course_tools::resolve_category_id($categoryid);
        $format = course_tools::normalise_course_format($format);
        $summaryformatconstant = course_tools::format_to_constant($summaryformat);
        course_tools::validate_course_dates($startdate, $enddate);

        $data = (object) [
            'fullname' => $fullname,
            'shortname' => $shortname,
            'category' => $categoryid,
            'visible' => $visible ? 1 : 0,
            'summary' => $summary,
            'summaryformat' => $summaryformatconstant,
            'format' => $format,
            'enablecompletion' => $enablecompletion ? 1 : 0,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'numsections' => 1,
        ];

        $course = create_course($data);

        return course_tools::to_response($course);
    }
}
