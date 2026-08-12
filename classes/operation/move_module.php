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
 * Move module operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Moves a Moodle course module through Moodle core APIs.
 */
class move_module {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Course module id.
     * @param int $sectionnumber Target course section number.
     * @param int|null $beforemoduleid Module id before which the moved module should be inserted.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $sectionnumber, ?int $beforemoduleid = null): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);
        $targetsection = section_tools::get_section($course, null, $sectionnumber);
        $beforecm = null;

        if ($beforemoduleid !== null) {
            if ($beforemoduleid === $moduleid) {
                throw new \invalid_parameter_exception('before_module_id cannot reference the moved module.');
            }

            $beforecm = module_tools::get_course_module($course, $beforemoduleid);
            if ((int) $beforecm->sectionnum !== (int) $targetsection->section) {
                throw new \invalid_parameter_exception('before_module_id must reference a module in the target section.');
            }
        }

        moveto_module($cm, $targetsection, $beforecm);
        rebuild_course_cache($course->id, true);

        return module_tools::to_response($course, (int) $cm->id);
    }
}
