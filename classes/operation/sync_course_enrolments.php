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
 * Synchronise manual course enrolments.
 */
class sync_course_enrolments {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param array $enrolments Enrolments.
     * @param bool $unenrolmissing Unenrolmissing.
     * @return array
     */
    public static function execute(int $courseid, array $enrolments, bool $unenrolmissing = false): array {
        course_workflow_tools::validate_enrolments($enrolments);

        $desired = [];
        $enrolled = [];
        $unenrolled = [];
        $warnings = [];

        foreach ($enrolments as $enrolment) {
            $userid = (int) ($enrolment['user_id'] ?? 0);
            $role = (string) ($enrolment['role_archetype'] ?? 'student');
            $desired[$userid] = true;
            try {
                $enrolled[] = enrol_user::execute($courseid, $userid, $role);
            } catch (\Throwable $error) {
                $warnings[] = [
                    'type' => 'enrolment',
                    'user_id' => $userid,
                    'message' => $error->getMessage(),
                ];
            }
        }

        if ($unenrolmissing) {
            foreach (get_enrolled_users::execute($courseid)['users'] as $user) {
                $userid = (int) $user['user_id'];
                if (!isset($desired[$userid])) {
                    try {
                        $unenrolled[] = unenrol_user::execute($courseid, $userid);
                    } catch (\Throwable $error) {
                        $warnings[] = [
                            'type' => 'unenrolment',
                            'user_id' => $userid,
                            'message' => $error->getMessage(),
                        ];
                    }
                }
            }
        }

        return [
            'course_id' => $courseid,
            'enrolled_json' => course_workflow_tools::encode_json($enrolled),
            'unenrolled_json' => course_workflow_tools::encode_json($unenrolled),
            'warnings_json' => course_workflow_tools::encode_json($warnings),
        ];
    }
}
