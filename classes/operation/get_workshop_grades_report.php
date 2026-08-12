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
 * Get workshop grades report operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads a Moodle Workshop grades report through Moodle external APIs.
 */
class get_workshop_grades_report {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param int $groupid Group id or 0.
     * @param string $sortby Sort field.
     * @param string $sortdirection Sort direction.
     * @param int $page Page number.
     * @param int $perpage Page size.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $groupid = 0,
        string $sortby = 'lastname',
        string $sortdirection = 'ASC',
        int $page = 0,
        int $perpage = 20
    ): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $sortby = self::normalise_sort_by($sortby);
        $sortdirection = self::normalise_sort_direction($sortdirection);
        $groupid = max(0, $groupid);
        $page = max(0, $page);
        $perpage = max(0, $perpage);

        $result = \mod_workshop_external::get_grades_report(
            (int) $cm->instance,
            $groupid,
            $sortby,
            $sortdirection,
            $page,
            $perpage
        );

        return [
            'course_id' => (int) $course->id,
        ] + workshop_tools::grades_report_to_response($cm, $result, $groupid, $sortby, $sortdirection, $page, $perpage);
    }

    /**
     * Validate and normalize a public report sort field.
     *
     * @param string $sortby Sort field.
     * @return string
     */
    private static function normalise_sort_by(string $sortby): string {
        $sortby = clean_param(strtolower(trim($sortby ?: 'lastname')), PARAM_ALPHA);
        $allowed = ['lastname', 'firstname', 'submissiontitle', 'submissionmodified', 'submissiongrade', 'gradinggrade'];
        if (!in_array($sortby, $allowed, true)) {
            throw new \invalid_parameter_exception(
                'sort_by must be one of: lastname, firstname, submissiontitle, submissionmodified, submissiongrade, gradinggrade.'
            );
        }

        return $sortby;
    }

    /**
     * Validate and normalize a public report sort direction.
     *
     * @param string $sortdirection Sort direction.
     * @return string
     */
    private static function normalise_sort_direction(string $sortdirection): string {
        $sortdirection = strtoupper(clean_param(trim($sortdirection ?: 'ASC'), PARAM_ALPHA));
        if (!in_array($sortdirection, ['ASC', 'DESC'], true)) {
            throw new \invalid_parameter_exception('sort_direction must be one of: ASC, DESC.');
        }

        return $sortdirection;
    }
}
