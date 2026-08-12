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
 * Submit choice response operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Submits the current user's response to a Moodle choice activity.
 */
class submit_choice_response {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $choicemoduleid Choice course module id.
     * @param string $optionidsjson JSON array of choice option ids.
     * @return array
     */
    public static function execute(int $courseid, int $choicemoduleid, string $optionidsjson): array {
        choice_tools::require_choice_api();
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = choice_tools::get_choice_module($course, $choicemoduleid);
        $optionids = choice_tools::decode_option_ids($optionidsjson);
        \mod_choice_external::submit_choice_response((int) $cm->instance, $optionids);

        return [
            'choice_id' => (int) $cm->instance,
            'choice_module_id' => (int) $cm->id,
            'submitted' => true,
            'option_ids' => $optionidsjson,
        ];
    }
}
