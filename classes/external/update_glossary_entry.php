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
 * Update glossary entry external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\glossary_tools;
use local_moodlia\operation\module_tools;
use local_moodlia\operation\update_glossary_entry as update_glossary_entry_operation;

/**
 * External API adapter for update_glossary_entry.
 */
class update_glossary_entry extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'entry_id' => new external_value(PARAM_INT, 'Glossary entry id'),
            'concept' => new external_value(PARAM_TEXT, 'Glossary entry concept', VALUE_DEFAULT, null, NULL_ALLOWED),
            'definition' => new external_value(PARAM_RAW, 'Glossary entry definition', VALUE_DEFAULT, null, NULL_ALLOWED),
            'definition_format' => new external_value(PARAM_ALPHA, 'Definition format: html or plain', VALUE_DEFAULT, 'html'),
            'options' => new external_value(PARAM_RAW, 'JSON-encoded entry options', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $entryid Entryid.
     * @param string|null $concept Concept.
     * @param string|null $definition Definition.
     * @param string $definitionformat Definitionformat.
     * @param string $options Options.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $entryid,
        ?string $concept = null,
        ?string $definition = null,
        string $definitionformat = 'html',
        string $options = '{}'
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'entry_id' => $entryid,
            'concept' => $concept,
            'definition' => $definition,
            'definition_format' => $definitionformat,
            'options' => $options,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'entry_id' => $entryid,
            'concept' => $concept,
            'definition' => $definition,
            'definition_format' => $definitionformat,
            'options' => $options,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/glossary:write', $modulecontext);

        return update_glossary_entry_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $entryid,
            $concept,
            $definition,
            $definitionformat,
            module_tools::decode_options($options)
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return create_glossary_entry::entry_returns();
    }
}
