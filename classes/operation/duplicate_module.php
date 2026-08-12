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
 * Duplicate module operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

use core_courseformat\formatactions;

/**
 * Duplicates a Moodle course module through Moodle core APIs.
 */
class duplicate_module {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Course module id.
     * @param int|null $sectionnumber Target course section number.
     * @param string|null $name Optional new duplicated module name.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, ?int $sectionnumber = null, ?string $name = null): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);
        $targetsectionid = null;

        if ($sectionnumber !== null) {
            $targetsection = section_tools::get_section($course, null, $sectionnumber);
            $targetsectionid = (int) $targetsection->id;
        }

        $duplicated = duplicate_module($course, $cm, $targetsectionid);
        if (!$duplicated) {
            throw new \moodle_exception('duplicatemodulenotcreated', 'local_moodlia');
        }

        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty.');
            }

            formatactions::cm($course->id)->rename((int) $duplicated->id, $name);
        }

        return module_tools::to_response($course, (int) $duplicated->id);
    }
}
