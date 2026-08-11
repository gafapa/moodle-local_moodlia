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
 * Delete choice responses operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes current-user or explicitly permitted Choice responses through Moodle Choice external APIs.
 */
class delete_choice_responses {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $choicemoduleid Choice course module id.
     * @param string $responseidsjson Optional JSON array of response ids. Empty array deletes current-user responses.
     * @return array
     */
    public static function execute(int $courseid, int $choicemoduleid, string $responseidsjson = '[]'): array {
        choice_tools::require_choice_api();

        $course = course_tools::get_course($courseid);
        $cm = choice_tools::get_choice_module($course, $choicemoduleid);
        $responseids = choice_tools::decode_response_ids($responseidsjson);
        $result = \mod_choice_external::delete_choice_responses((int) $cm->instance, $responseids);

        return [
            'choice_id' => (int) $cm->instance,
            'choice_module_id' => (int) $cm->id,
            'deleted' => (bool) ($result['status'] ?? false),
            'response_ids' => json_encode($responseids, JSON_UNESCAPED_SLASHES),
            'warnings' => choice_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
