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
 * Update workshop assessment operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Updates a Moodle Workshop assessment through Moodle external APIs.
 */
class update_workshop_assessment {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param int $assessmentid Workshop assessment id.
     * @param string $datajson JSON array of name/value rows accepted by Moodle's workshop external API.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $assessmentid, string $datajson): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $assessment = workshop_tools::get_assessment($cm, $assessmentid);
        $data = json_decode($datajson, true);
        if (!is_array($data)) {
            throw new \invalid_parameter_exception('data_json must be a JSON array of objects with name and value fields.');
        }

        $rows = [];
        foreach ($data as $row) {
            if (!is_array($row) || !array_key_exists('name', $row) || !array_key_exists('value', $row)) {
                throw new \invalid_parameter_exception('Each data_json row must contain name and value fields.');
            }
            $rows[] = [
                'name' => (string) $row['name'],
                'value' => is_scalar($row['value']) || $row['value'] === null
                    ? (string) $row['value']
                    : workshop_tools::json_value($row['value']),
            ];
        }

        $result = \mod_workshop_external::update_assessment((int) $assessment['assessment_id'], $rows);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'assessment_id' => (int) $assessment['assessment_id'],
            'updated' => (bool) ($result['status'] ?? false),
            'raw_grade' => workshop_tools::optional_float((array) $result, 'rawgrade'),
            'warnings' => workshop_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
