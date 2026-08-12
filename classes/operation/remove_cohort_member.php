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
 * Remove a user from a Moodle cohort.
 */
class remove_cohort_member {
    /**
     * Execute the operation.
     *
     * @param int $cohortid Cohortid.
     * @param int $userid Userid.
     * @return array
     */
    public static function execute(int $cohortid, int $userid): array {
        admin_tools::require_cohort_api();
        admin_tools::get_cohort($cohortid);
        admin_tools::get_user($userid);

        cohort_remove_member($cohortid, $userid);
        return [
            'cohort_id' => $cohortid,
            'user_id' => $userid,
            'member' => false,
        ];
    }
}
