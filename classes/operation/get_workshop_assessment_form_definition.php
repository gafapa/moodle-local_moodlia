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
 * Get workshop assessment form definition operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns Moodle Workshop assessment form data through Moodle external APIs.
 */
class get_workshop_assessment_form_definition {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $assessmentid Assessmentid.
     * @param string $mode Mode.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $assessmentid, string $mode = 'assessment'): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $assessment = workshop_tools::get_assessment($cm, $assessmentid);
        $mode = clean_param($mode ?: 'assessment', PARAM_ALPHA);
        if (!in_array($mode, ['assessment', 'preview'], true)) {
            throw new \invalid_parameter_exception('mode must be one of: assessment, preview.');
        }

        $result = \mod_workshop_external::get_assessment_form_definition((int) $assessment['assessment_id'], $mode);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'assessment_id' => (int) $assessment['assessment_id'],
            'mode' => $mode,
            'dimensions_count' => (int) ($result['dimenssionscount'] ?? $result['dimensionscount'] ?? 0),
            'description_files_count' => count((array) ($result['descriptionfiles'] ?? [])),
            'options_json' => workshop_tools::json_value($result['options'] ?? []),
            'fields_json' => workshop_tools::json_value($result['fields'] ?? []),
            'current_json' => workshop_tools::json_value($result['current'] ?? []),
            'dimensions_json' => workshop_tools::json_value($result['dimensionsinfo'] ?? []),
            'warnings' => workshop_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
