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

/**
 * Reads a Moodle Workshop grades report through Moodle external APIs.
 */
class get_workshop_grades_report {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $groupid Groupid.
     * @param string $sortby Sortby.
     * @param string $sortdirection Sortdirection.
     * @param int $page Page.
     * @param int $perpage Perpage.
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
        global $USER;

        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $sortby = self::normalise_sort_by($sortby);
        $sortdirection = self::normalise_sort_direction($sortdirection);
        $groupid = max(0, $groupid);
        $page = max(0, $page);
        $perpage = max(0, $perpage);

        $context = \context_module::instance((int) $cm->id);
        require_capability('mod/workshop:viewallassessments', $context);
        $workshop = workshop_tools::get_workshop_object($course, $cm);
        if (!$groupid && groups_get_activity_groupmode($cm)) {
            $groupid = (int) groups_get_activity_group($cm);
        }
        if (($groupid || groups_get_activity_groupmode($cm)) && !groups_group_visible($groupid, $course, $cm)) {
            throw new \moodle_exception('notingroup');
        }

        $data = null;
        if ($workshop->phase >= \workshop::PHASE_SUBMISSION) {
            $data = $workshop->prepare_grading_report_data($USER->id, $groupid, $page, $perpage, $sortby, $sortdirection);
        }
        // Core's report exporter references an undefined $tr during assessment.
        // Use its domain data and apply the same phase and name visibility rules.
        foreach (($data->grades ?? []) as $row) {
            if ($workshop->phase < \workshop::PHASE_EVALUATION) {
                unset($row->submissiongrade, $row->submissiongradeover, $row->gradinggrade);
            }
            if ($workshop->phase === \workshop::PHASE_SUBMISSION) {
                unset($row->submissiongradeoverby, $row->submissionpublished, $row->reviewedby, $row->reviewerof);
            }
            if (!has_capability('mod/workshop:viewreviewernames', $context)) {
                foreach (($row->reviewedby ?? []) as $review) {
                    $review->userid = 0;
                }
            }
            if (!has_capability('mod/workshop:viewauthornames', $context)) {
                foreach (($row->reviewerof ?? []) as $review) {
                    $review->userid = 0;
                }
            }
        }
        $result = ['report' => $data ?? (object) ['grades' => [], 'totalcount' => 0]];

        return [
            'course_id' => (int) $course->id,
        ] + workshop_tools::grades_report_to_response($cm, $result, $groupid, $sortby, $sortdirection, $page, $perpage);
    }

    /**
     * Validate and normalize a public report sort field.
     *
     * @param string $sortby Sortby.
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
     * @param string $sortdirection Sortdirection.
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
