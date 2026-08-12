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
 * Allocate workshop submission operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Allocates a Moodle Workshop submission for assessment through Moodle Workshop APIs.
 */
class allocate_workshop_submission {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $submissionid Submissionid.
     * @param int $reviewerid Reviewerid.
     * @param int $weight Weight.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $submissionid, int $reviewerid = 0, int $weight = 1): array {
        global $USER;

        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        $submission = workshop_tools::get_submission($cm, $submissionid);
        $reviewerid = $reviewerid > 0 ? $reviewerid : (int) $USER->id;

        if (!has_capability('mod/workshop:peerassess', $modulecontext, $reviewerid)) {
            throw new \required_capability_exception($modulecontext, 'mod/workshop:peerassess', 'nopermissions', '');
        }

        $workshop = workshop_tools::get_workshop_object($course, $cm);
        $assessmentid = $workshop->add_allocation((object) ['id' => (int) $submission['submission_id']], $reviewerid, $weight);
        $created = true;

        if ((int) $assessmentid === \workshop::ALLOCATION_EXISTS) {
            $existing = $workshop->get_assessment_of_submission_by_user((int) $submission['submission_id'], $reviewerid);
            if (!$existing) {
                throw new \moodle_exception('invalidrecord', 'error', '', 'workshop assessment');
            }
            $assessmentid = (int) $existing->id;
            $created = false;
        }

        $assessment = workshop_tools::get_assessment($cm, (int) $assessmentid);
        $assessment['course_id'] = (int) $course->id;
        $assessment['created'] = $created;

        return $assessment;
    }
}
