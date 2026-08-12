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

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\create_user as create_user_operation;

/**
 * Create user implementation.
 */
class create_user extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'username' => new external_value(PARAM_USERNAME, 'Username'),
            'firstname' => new external_value(PARAM_TEXT, 'First name'),
            'lastname' => new external_value(PARAM_TEXT, 'Last name'),
            'email' => new external_value(PARAM_EMAIL, 'Email address'),
            'password' => new external_value(PARAM_RAW, 'Initial password'),
            'auth' => new external_value(PARAM_ALPHANUMEXT, 'Authentication plugin', VALUE_DEFAULT, 'manual'),
            'suspended' => new external_value(PARAM_BOOL, 'Create the user suspended', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $username Username.
     * @param string $firstname Firstname.
     * @param string $lastname Lastname.
     * @param string $email Email.
     * @param string $password Password.
     * @param string $auth Auth.
     * @param bool $suspended Suspended.
     * @return array
     */
    public static function execute(
        string $username,
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        string $auth = 'manual',
        bool $suspended = false
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => $password,
            'auth' => $auth,
            'suspended' => $suspended,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        require_capability('moodle/user:create', $systemcontext);

        return create_user_operation::execute(
            $params['username'],
            $params['firstname'],
            $params['lastname'],
            $params['email'],
            $params['password'],
            $params['auth'],
            (bool) $params['suspended']
        );
    }

    /**
     * Execute returns.
     *
     * @return mixed
     */
    public static function execute_returns() {
        return admin_response::user_structure();
    }
}
