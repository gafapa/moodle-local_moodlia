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
use local_moodlia\operation\update_cohort as update_cohort_operation;

/**
 * Update cohort implementation.
 */
class update_cohort extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cohort_id' => new external_value(PARAM_INT, 'Moodle cohort id'),
            'name' => new external_value(PARAM_TEXT, 'Cohort name', VALUE_DEFAULT, null, NULL_ALLOWED),
            'idnumber' => new external_value(PARAM_RAW, 'Cohort idnumber', VALUE_DEFAULT, null, NULL_ALLOWED),
            'description' => new external_value(PARAM_RAW, 'Cohort description', VALUE_DEFAULT, null, NULL_ALLOWED),
            'visible' => new external_value(PARAM_BOOL, 'Whether the cohort is visible', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $cohortid Cohort id.
     * @param string|null $name Name.
     * @param string|null $idnumber Idnumber.
     * @param string|null $description Description.
     * @param bool|null $visible Visible.
     * @return array
     */
    public static function execute(
        int $cohortid,
        ?string $name = null,
        ?string $idnumber = null,
        ?string $description = null,
        ?bool $visible = null
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cohort_id' => $cohortid,
            'name' => $name,
            'idnumber' => $idnumber,
            'description' => $description,
            'visible' => $visible,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        require_capability('moodle/cohort:manage', $systemcontext);

        return update_cohort_operation::execute((int) $params['cohort_id'], $params);
    }

    /**
     * Execute returns.
     *
     * @return mixed
     */
    public static function execute_returns() {
        return admin_response::cohort_structure();
    }
}
