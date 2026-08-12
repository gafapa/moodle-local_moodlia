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
 * View choice operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Registers a Moodle Choice view through Moodle Choice external APIs.
 */
class view_choice {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $choicemoduleid Choicemoduleid.
     * @return array
     */
    public static function execute(int $courseid, int $choicemoduleid): array {
        choice_tools::require_choice_api();

        $course = course_tools::get_course($courseid);
        $cm = choice_tools::get_choice_module($course, $choicemoduleid);
        $result = \mod_choice_external::view_choice((int) $cm->instance);

        return [
            'choice_id' => (int) $cm->instance,
            'choice_module_id' => (int) $cm->id,
            'viewed' => (bool) ($result['status'] ?? false),
            'warnings' => choice_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
