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
 * View Glossary operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Registers a Moodle Glossary view through Moodle Glossary external APIs.
 */
class view_glossary {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $mode Mode.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $mode = 'letter'): array {
        glossary_tools::require_glossary_api();

        $course = course_tools::get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $result = \mod_glossary_external::view_glossary((int) $cm->instance, $mode);

        return [
            'module_id' => (int) $cm->id,
            'glossary_id' => (int) $cm->instance,
            'mode' => $mode,
            'viewed' => (bool) ($result['status'] ?? false),
            'warnings' => glossary_tools::warnings_to_response($result['warnings'] ?? []),
        ];
    }
}
