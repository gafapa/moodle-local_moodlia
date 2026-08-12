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
 * Create a Moodle system cohort.
 */
class create_cohort {
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
        admin_tools::require_cohort_api();

        $cohort = (object) [
            'contextid' => \context_system::instance()->id,
            'name' => trim($name),
            'idnumber' => trim($idnumber),
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'visible' => $visible ? 1 : 0,
        ];
        if ($cohort->name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        $cohortid = cohort_add_cohort($cohort);
        return admin_tools::cohort_to_response(admin_tools::get_cohort((int) $cohortid));
    }
}
