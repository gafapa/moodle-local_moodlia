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
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\add_cohort_member as add_cohort_member_operation;

/**
 * Add cohort member implementation.
 */
class add_cohort_member extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cohort_id' => new external_value(PARAM_INT, 'Moodle cohort id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $cohortid Cohort id.
     * @param int $userid User id.
     * @return array
     */
    public static function execute(int $cohortid, int $userid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cohort_id' => $cohortid,
            'user_id' => $userid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        require_capability('moodle/cohort:manage', $systemcontext);

        return add_cohort_member_operation::execute((int) $params['cohort_id'], (int) $params['user_id']);
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return self::membership_structure();
    }

    /**
     * Membership structure.
     *
     * @return external_single_structure
     */
    public static function membership_structure(): external_single_structure {
        return new external_single_structure([
            'cohort_id' => new external_value(PARAM_INT, 'Moodle cohort id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'member' => new external_value(PARAM_BOOL, 'Whether the user is a cohort member'),
        ]);
    }
}
