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
 * Search local, non-deleted accounts with bounded, stable pagination.
 */
class list_users {
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
        global $DB, $CFG;

        if ($offset < 0 || $limit < 1 || $limit > 100) {
            throw new \invalid_parameter_exception('offset must be non-negative and limit must be between 1 and 100.');
        }
        $where = ['deleted = 0', 'mnethostid = :hostid', 'id <> :guestid'];
        $params = ['hostid' => $CFG->mnet_localhost_id, 'guestid' => $CFG->siteguest];
        foreach (['username' => $username, 'email' => $email, 'auth' => $auth] as $field => $value) {
            if (trim($value) !== '') {
                $where[] = "$field = :$field";
                $params[$field] = trim($value);
            }
        }
        if ($suspended !== null) {
            $where[] = 'suspended = :suspended';
            $params['suspended'] = (int) $suspended;
        }
        if (trim($search) !== '') {
            $matches = [];
            foreach (['username', 'firstname', 'lastname', 'email'] as $field) {
                $key = 'search' . $field;
                $matches[] = $DB->sql_like($field, ':' . $key, false);
                $params[$key] = '%' . $DB->sql_like_escape(trim($search)) . '%';
            }
            $where[] = '(' . implode(' OR ', $matches) . ')';
        }
        $select = implode(' AND ', $where);
        $total = $DB->count_records_select('user', $select, $params);
        $fields = 'id, username, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, '
            . 'email, auth, suspended, deleted, confirmed, timemodified';
        $users = $DB->get_records_select('user', $select, $params, 'id ASC', $fields, $offset, $limit);
        return [
            'users' => array_values(array_map([admin_tools::class, 'user_to_response'], $users)),
            'total' => (int) $total,
            'offset' => $offset,
            'limit' => $limit,
        ];
    }
}
