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

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use local_moodlia\operation\get_user_details as get_user_details_operation;

class get_user_details extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
        ]);
    }

    public static function execute(int $user_id): array {
        ['user_id' => $userid] = self::validate_parameters(self::execute_parameters(), ['user_id' => $user_id]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        require_capability('moodle/user:viewdetails', $systemcontext);

        return get_user_details_operation::execute((int) $userid);
    }

    public static function execute_returns() {
        return admin_response::user_structure();
    }
}
