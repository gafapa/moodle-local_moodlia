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
 * Audit course completion operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Audits activity completion settings in a course.
 */
class audit_course_completion {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param bool $includeok Include non-issue module rows.
     * @return array
     */
    public static function execute(int $courseid, bool $includeok = false): array {
        $audit = completion_audit_tools::audit($courseid, $includeok);

        return [
            'course_id' => (int) $audit['course_id'],
            'issue_count' => (int) $audit['issue_count'],
            'repairable_count' => (int) $audit['repairable_count'],
            'issues_json' => course_workflow_tools::encode_json($audit['issues']),
            'ok_json' => course_workflow_tools::encode_json($audit['ok']),
        ];
    }
}
