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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Administrative account search over the shared transport boundary.
 */
class list_users extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'search' => new external_value(PARAM_RAW_TRIMMED, 'Literal partial username, name, or email', VALUE_DEFAULT, ''),
            'username' => new external_value(PARAM_RAW_TRIMMED, 'Exact username', VALUE_DEFAULT, ''),
            'email' => new external_value(PARAM_RAW_TRIMMED, 'Exact email address', VALUE_DEFAULT, ''),
            'auth' => new external_value(PARAM_ALPHANUMEXT, 'Authentication plugin', VALUE_DEFAULT, ''),
            'suspended' => new external_value(PARAM_BOOL, 'Optional suspension filter', VALUE_DEFAULT, null, NULL_ALLOWED),
            'offset' => new external_value(PARAM_INT, 'Number of matching users to skip', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Page size from 1 to 100', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $search Search.
     * @param string $username Username.
     * @param string $email Email.
     * @param string $auth Auth.
     * @param bool|null $suspended Suspended.
     * @param int $offset Offset.
     * @param int $limit Limit.
     * @return array
     */
    public static function execute(
        string $search = '',
        string $username = '',
        string $email = '',
        string $auth = '',
        ?bool $suspended = null,
        int $offset = 0,
        int $limit = 50
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'search' => $search, 'username' => $username, 'email' => $email, 'auth' => $auth,
            'suspended' => $suspended, 'offset' => $offset, 'limit' => $limit,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);
        require_capability('moodle/user:viewdetails', $context);
        require_capability('moodle/user:viewalldetails', $context);

        return \local_moodlia\operation\list_users::execute(
            $params['search'],
            $params['username'],
            $params['email'],
            $params['auth'],
            $params['suspended'],
            $params['offset'],
            $params['limit']
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(admin_response::user_structure()),
            'total' => new external_value(PARAM_INT, 'Total matching accounts'),
            'offset' => new external_value(PARAM_INT, 'Pagination offset'),
            'limit' => new external_value(PARAM_INT, 'Page size'),
        ]);
    }
}
