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
 * Copy course structure from one course to another through a MoodlIA blueprint.
 */
class copy_course_structure {
    /**
     * Execute the operation.
     *
     * @param int $sourcecourseid Sourcecourseid.
     * @param int $targetcourseid Targetcourseid.
     * @param bool $includecontents Includecontents.
     * @param bool $includegroups Includegroups.
     * @return array
     */
    public static function execute(int $sourcecourseid, int $targetcourseid, bool $includecontents = true, bool $includegroups = false): array {
        if (!$includecontents && !$includegroups) {
            throw new \invalid_parameter_exception('At least one of include_contents or include_groups must be true.');
        }

        $blueprint = course_workflow_tools::export_blueprint($sourcecourseid, $includecontents, $includegroups);
        unset($blueprint['course'], $blueprint['publish_state'], $blueprint['enrolments']);
        $applied = course_workflow_tools::apply_to_course($targetcourseid, $blueprint);

        return [
            'source_course_id' => $sourcecourseid,
            'target_course_id' => $targetcourseid,
            'sections_json' => course_workflow_tools::encode_json($applied['sections']),
            'modules_json' => course_workflow_tools::encode_json($applied['modules']),
            'groups_json' => course_workflow_tools::encode_json($applied['groups']),
            'warnings_json' => course_workflow_tools::encode_json($applied['warnings']),
        ];
    }
}
