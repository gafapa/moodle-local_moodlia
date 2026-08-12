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
 * Repair course completion operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Repairs activity completion settings in a course.
 */
class repair_course_completion {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $mode Mode.
     * @param bool $dryrun Dryrun.
     * @param bool $resetstates Resetstates.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $mode = 'book_view_only',
        bool $dryrun = true,
        bool $resetstates = true
    ): array {
        $repair = completion_audit_tools::repair($courseid, $mode, $dryrun, $resetstates);

        return [
            'course_id' => (int) $repair['course_id'],
            'mode' => (string) $repair['mode'],
            'dry_run' => (bool) $repair['dry_run'],
            'changed_count' => (int) $repair['changed_count'],
            'warning_count' => (int) $repair['warning_count'],
            'changes_json' => course_workflow_tools::encode_json($repair['changes']),
            'warnings_json' => course_workflow_tools::encode_json($repair['warnings']),
        ];
    }
}
