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

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Shared external response structures for administration operations.
 */
class admin_response {
    /**
     * User structure.
     *
     * @return external_single_structure
     */
    public static function user_structure(): external_single_structure {
        return new external_single_structure([
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'username' => new external_value(PARAM_RAW, 'Username'),
            'firstname' => new external_value(PARAM_RAW, 'First name'),
            'lastname' => new external_value(PARAM_RAW, 'Last name'),
            'fullname' => new external_value(PARAM_RAW, 'Full name'),
            'email' => new external_value(PARAM_RAW, 'Email address'),
            'auth' => new external_value(PARAM_ALPHANUMEXT, 'Authentication plugin'),
            'suspended' => new external_value(PARAM_BOOL, 'Whether the user is suspended'),
            'deleted' => new external_value(PARAM_BOOL, 'Whether the user is deleted'),
            'confirmed' => new external_value(PARAM_BOOL, 'Whether the user is confirmed'),
            'time_modified' => new external_value(PARAM_INT, 'Last modified timestamp'),
        ]);
    }

    /**
     * Cohort structure.
     *
     * @return external_single_structure
     */
    public static function cohort_structure(): external_single_structure {
        return new external_single_structure([
            'cohort_id' => new external_value(PARAM_INT, 'Moodle cohort id'),
            'context_id' => new external_value(PARAM_INT, 'Cohort context id'),
            'name' => new external_value(PARAM_RAW, 'Cohort name'),
            'idnumber' => new external_value(PARAM_RAW, 'Cohort idnumber'),
            'description' => new external_value(PARAM_RAW, 'Cohort description'),
            'description_format' => new external_value(PARAM_INT, 'Description format'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the cohort is visible'),
            'time_created' => new external_value(PARAM_INT, 'Creation timestamp'),
            'time_modified' => new external_value(PARAM_INT, 'Last modified timestamp'),
        ]);
    }

    /**
     * Role assignment structure.
     *
     * @param string $statusfield Statusfield.
     * @return external_single_structure
     */
    public static function role_assignment_structure(string $statusfield): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'user_id' => new external_value(PARAM_INT, 'Moodle user id'),
            'role_id' => new external_value(PARAM_INT, 'Moodle role id'),
            'role_archetype' => new external_value(PARAM_ALPHANUMEXT, 'Role archetype'),
            $statusfield => new external_value(PARAM_BOOL, 'Role assignment status'),
            'roles' => new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'Assigned role shortname')),
        ]);
    }
}
