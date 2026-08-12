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
 * MoodlIA plugin implementation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Apply a portable MoodlIA blueprint to an existing course.
 */
class apply_course_blueprint {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param array $blueprint Blueprint.
     * @return array
     */
    public static function execute(int $courseid, array $blueprint): array {
        $applied = course_workflow_tools::apply_to_course($courseid, $blueprint);

        return [
            'course_id' => $courseid,
            'sections_json' => course_workflow_tools::encode_json($applied['sections']),
            'modules_json' => course_workflow_tools::encode_json($applied['modules']),
            'groups_json' => course_workflow_tools::encode_json($applied['groups']),
            'enrolments_json' => course_workflow_tools::encode_json($applied['enrolments']),
            'warnings_json' => course_workflow_tools::encode_json($applied['warnings']),
        ];
    }
}
