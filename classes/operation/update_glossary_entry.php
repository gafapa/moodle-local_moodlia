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
 * Update glossary entry operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle glossary entry through Moodle external APIs.
 */
class update_glossary_entry {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Glossary course module id.
     * @param int $entryid Glossary entry id.
     * @param string|null $concept Updated concept.
     * @param string|null $definition Updated definition.
     * @param string $definitionformat Public definition format.
     * @param array $options Entry options.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $entryid,
        ?string $concept = null,
        ?string $definition = null,
        string $definitionformat = 'html',
        array $options = []
    ): array {
        glossary_tools::require_glossary_api();

        $course = course_tools::get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $existing = glossary_tools::get_entry($cm, $entryid);

        if ($concept === null && $definition === null && !$options) {
            throw new \invalid_parameter_exception('At least one of concept, definition, or options is required.');
        }

        $concept = $concept === null ? (string) $existing['concept'] : trim($concept);
        if ($concept === '') {
            throw new \invalid_parameter_exception('concept cannot be empty.');
        }
        $definition = $definition === null ? (string) $existing['definition'] : $definition;

        \mod_glossary\external\update_entry::execute(
            $entryid,
            $concept,
            $definition,
            glossary_tools::format_to_constant($definitionformat),
            glossary_tools::options_to_external($options)
        );

        $entry = glossary_tools::get_entry($cm, $entryid);
        return glossary_tools::entry_to_response($cm, $entry);
    }
}
