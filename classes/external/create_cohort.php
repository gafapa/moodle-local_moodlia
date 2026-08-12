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
use local_moodlia\operation\create_cohort as create_cohort_operation;

/**
 * Create cohort implementation.
 */
class create_cohort extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Cohort name'),
            'idnumber' => new external_value(PARAM_RAW, 'Cohort idnumber', VALUE_DEFAULT, ''),
            'description' => new external_value(PARAM_RAW, 'Cohort description', VALUE_DEFAULT, ''),
            'visible' => new external_value(PARAM_BOOL, 'Whether the cohort is visible', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param string $name Name.
     * @param string $idnumber Idnumber.
     * @param string $description Description.
     * @param bool $visible Visible.
     * @return array
     */
    public static function execute(string $name, string $idnumber = '', string $description = '', bool $visible = true): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'name' => $name,
            'idnumber' => $idnumber,
            'description' => $description,
            'visible' => $visible,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        require_capability('moodle/cohort:manage', $systemcontext);

        return create_cohort_operation::execute($params['name'], $params['idnumber'], $params['description'], (bool) $params['visible']);
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
