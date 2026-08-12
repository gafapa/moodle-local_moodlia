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
 * Evaluate workshop assessment operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Evaluates a Moodle Workshop assessment through Moodle external APIs.
 */
class evaluate_workshop_assessment {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $assessmentid Assessmentid.
     * @param string $feedbacktext Feedbacktext.
     * @param string $feedbackformat Feedbackformat.
     * @param int $weight Weight.
     * @param string $gradinggradeover Gradinggradeover.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $assessmentid,
        string $feedbacktext = '',
        string $feedbackformat = 'html',
        int $weight = 1,
        string $gradinggradeover = ''
    ): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $assessment = workshop_tools::get_assessment($cm, $assessmentid);
        $format = workshop_tools::format_to_constant($feedbackformat ?: 'html');
        $weight = max(0, $weight);
        $result = \mod_workshop_external::evaluate_assessment(
            (int) $assessment['assessment_id'],
            $feedbacktext,
            $format,
            $weight,
            $gradinggradeover
        );

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'assessment_id' => (int) $assessment['assessment_id'],
            'evaluated' => (bool) ($result['status'] ?? false),
            'warnings' => workshop_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
