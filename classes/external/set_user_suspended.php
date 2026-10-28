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

/**
 * Explicit suspension and reactivation endpoint.
 */
class set_user_suspended extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'suspended' => new external_value(PARAM_BOOL, 'True to suspend, false to reactivate'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $userid Userid.
     * @param bool $suspended Suspended.
     * @return array
     */
    public static function execute(int $userid, bool $suspended): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'user_id' => $userid, 'suspended' => $suspended,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/moodlia:useapi', $context);
        require_capability('moodle/user:update', $context);

        return \local_moodlia\operation\set_user_suspended::execute($params['user_id'], $params['suspended']);
    }

    /**
     * Execute returns.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): \core_external\external_single_structure {
        return admin_response::user_structure();
    }
}
