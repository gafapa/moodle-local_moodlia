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
 * Create glossary entry operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle glossary entry through Moodle external APIs.
 */
class create_glossary_entry {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $concept Concept.
     * @param string $definition Definition.
     * @param string $definitionformat Definitionformat.
     * @param array $options Options.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $concept,
        string $definition,
        string $definitionformat = 'html',
        array $options = []
    ): array {
        glossary_tools::require_glossary_api();

        $course = course_tools::get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $concept = trim($concept);
        if ($concept === '') {
            throw new \invalid_parameter_exception('concept is required.');
        }

        $result = \mod_glossary_external::add_entry(
            (int) $cm->instance,
            $concept,
            $definition,
            glossary_tools::format_to_constant($definitionformat),
            glossary_tools::options_to_external($options)
        );

        $entry = glossary_tools::get_entry($cm, (int) $result['entryid']);
        return glossary_tools::entry_to_response($cm, $entry);
    }
}
