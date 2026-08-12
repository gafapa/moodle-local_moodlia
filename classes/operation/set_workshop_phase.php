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
 * Set workshop phase operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Changes a Moodle Workshop phase through Moodle Workshop APIs.
 */
class set_workshop_phase {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param string $phase Public phase name.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $phase): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $workshop = workshop_tools::get_workshop_object($course, $cm);
        $phaseconstant = workshop_tools::phase_to_constant($phase);
        if (!$workshop->switch_phase($phaseconstant)) {
            throw new \invalid_parameter_exception('phase must reference a supported workshop phase.');
        }

        rebuild_course_cache($course->id, true);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'phase' => workshop_tools::phase_from_constant($phaseconstant),
            'phase_code' => $phaseconstant,
        ];
    }
}
