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

defined('MOODLE_INTERNAL') || die();

/**
 * Create a Moodle user through Moodle's user API.
 */
class create_user {
    public static function execute(
        string $username,
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        string $auth = 'manual',
        bool $suspended = false
    ): array {
        global $CFG;

        admin_tools::require_user_api();

        $user = (object) [
            'username' => \core_text::strtolower(trim($username)),
            'firstname' => trim($firstname),
            'lastname' => trim($lastname),
            'email' => trim($email),
            'password' => $password,
            'auth' => trim($auth) ?: 'manual',
            'confirmed' => 1,
            'suspended' => $suspended ? 1 : 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];

        if ($user->username === '' || $user->firstname === '' || $user->lastname === '' || $user->email === '') {
            throw new \invalid_parameter_exception('username, firstname, lastname, and email are required.');
        }
        if ($password === '') {
            throw new \invalid_parameter_exception('password is required.');
        }

        $userid = user_create_user($user, true, true);
        return admin_tools::user_to_response(admin_tools::get_user((int) $userid));
    }
}
