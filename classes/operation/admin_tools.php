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
 * Shared administration helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle users, cohorts, and role operations.
 */
class admin_tools {
    /**
     * Load Moodle user APIs.
     */
    public static function require_user_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
    }

    /**
     * Load Moodle cohort APIs.
     */
    public static function require_cohort_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /**
     * Load Moodle role APIs.
     */
    public static function require_role_api(): void {
        global $CFG;

        require_once($CFG->libdir . '/accesslib.php');
    }

    /**
     * Load a Moodle user.
     *
     * @param int $userid Moodle user id.
     * @return \stdClass
     */
    public static function get_user(int $userid): \stdClass {
        if ($userid <= 0) {
            throw new \invalid_parameter_exception('user_id must be a positive integer.');
        }

        return \core_user::get_user($userid, '*', MUST_EXIST);
    }

    /**
     * Return the canonical user response shape.
     *
     * @param \stdClass $user Moodle user.
     * @return array
     */
    public static function user_to_response(\stdClass $user): array {
        return [
            'user_id' => (int) $user->id,
            'username' => (string) ($user->username ?? ''),
            'firstname' => (string) ($user->firstname ?? ''),
            'lastname' => (string) ($user->lastname ?? ''),
            'fullname' => fullname($user),
            'email' => (string) ($user->email ?? ''),
            'auth' => (string) ($user->auth ?? ''),
            'suspended' => (bool) ($user->suspended ?? false),
            'deleted' => (bool) ($user->deleted ?? false),
            'confirmed' => (bool) ($user->confirmed ?? false),
            'time_modified' => (int) ($user->timemodified ?? 0),
        ];
    }

    /**
     * Load a Moodle cohort.
     *
     * @param int $cohortid Moodle cohort id.
     * @return \stdClass
     */
    public static function get_cohort(int $cohortid): \stdClass {
        self::require_cohort_api();

        if ($cohortid <= 0) {
            throw new \invalid_parameter_exception('cohort_id must be a positive integer.');
        }

        $contextid = \context_system::instance()->id;
        $page = 0;
        $perpage = 100;

        do {
            $result = cohort_get_cohorts($contextid, $page, $perpage, '', false);
            $cohorts = $result['cohorts'] ?? [];
            if (isset($cohorts[$cohortid])) {
                return $cohorts[$cohortid];
            }
            foreach ($cohorts as $cohort) {
                if ((int) $cohort->id === $cohortid) {
                    return $cohort;
                }
            }
            $page++;
            $total = (int) ($result['totalcohorts'] ?? 0);
        } while (($page * $perpage) < $total);

        throw new \invalid_parameter_exception('Unknown cohort_id.');
    }

    /**
     * Return the canonical cohort response shape.
     *
     * @param \stdClass $cohort Moodle cohort.
     * @return array
     */
    public static function cohort_to_response(\stdClass $cohort): array {
        return [
            'cohort_id' => (int) $cohort->id,
            'context_id' => (int) $cohort->contextid,
            'name' => format_string($cohort->name, true),
            'idnumber' => (string) ($cohort->idnumber ?? ''),
            'description' => (string) ($cohort->description ?? ''),
            'description_format' => (int) ($cohort->descriptionformat ?? FORMAT_HTML),
            'visible' => (bool) ($cohort->visible ?? true),
            'time_created' => (int) ($cohort->timecreated ?? 0),
            'time_modified' => (int) ($cohort->timemodified ?? 0),
        ];
    }

    /**
     * Validate a supported course-role archetype and return its role id.
     *
     * @param \context_course $context Course context.
     * @param string $rolearchetype Role archetype.
     * @return int
     */
    public static function resolve_course_role_id(\context_course $context, string $rolearchetype): int {
        return enrolment_tools::resolve_role_id($context, $rolearchetype);
    }
}
