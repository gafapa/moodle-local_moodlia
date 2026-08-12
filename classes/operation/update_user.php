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
 * Update a Moodle user through Moodle's user API.
 */
class update_user {
    /**
     * Execute the operation.
     *
     * @param int $userid Userid.
     * @param array $patch Patch.
     * @return array
     */
    public static function execute(int $userid, array $patch): array {
        admin_tools::require_user_api();
        admin_tools::get_user($userid);

        $user = (object) ['id' => $userid];
        foreach (['firstname', 'lastname', 'email', 'auth'] as $field) {
            if (array_key_exists($field, $patch) && $patch[$field] !== null) {
                $user->{$field} = trim((string) $patch[$field]);
            }
        }
        if (array_key_exists('password', $patch) && $patch['password'] !== null && (string) $patch['password'] !== '') {
            $user->password = (string) $patch['password'];
        }
        if (array_key_exists('suspended', $patch) && $patch['suspended'] !== null) {
            $user->suspended = (bool) $patch['suspended'] ? 1 : 0;
        }

        user_update_user($user, property_exists($user, 'password'), true);
        return admin_tools::user_to_response(admin_tools::get_user($userid));
    }
}
