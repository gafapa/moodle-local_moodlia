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
 * Get workshop assessment form definition external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_workshop_assessment_form_definition as get_workshop_assessment_form_definition_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for get_workshop_assessment_form_definition.
 */
class get_workshop_assessment_form_definition extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'assessment_id' => new external_value(PARAM_INT, 'Workshop assessment id'),
            'mode' => new external_value(PARAM_ALPHA, 'Form mode: assessment or preview', VALUE_DEFAULT, 'assessment'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $assessmentid, string $mode = 'assessment'): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'assessment_id' => $assessmentid,
            'mode' => $formmode,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'assessment_id' => $assessmentid,
            'mode' => $mode,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:view', $modulecontext);

        return get_workshop_assessment_form_definition_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $assessmentid,
            (string) $formmode
        );
    }

    /**
     * Return a warning structure.
     *
     * @return external_single_structure
     */
    private static function warning_structure(): external_single_structure {
        return new external_single_structure([
            'item' => new external_value(PARAM_TEXT, 'Warning item'),
            'item_id' => new external_value(PARAM_INT, 'Warning item id'),
            'warning_code' => new external_value(PARAM_TEXT, 'Warning code'),
            'message' => new external_value(PARAM_TEXT, 'Warning message'),
        ]);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'workshop_id' => new external_value(PARAM_INT, 'Workshop instance id'),
            'assessment_id' => new external_value(PARAM_INT, 'Workshop assessment id'),
            'mode' => new external_value(PARAM_ALPHA, 'Form mode'),
            'dimensions_count' => new external_value(PARAM_INT, 'Assessment dimension count'),
            'description_files_count' => new external_value(PARAM_INT, 'Description file count'),
            'options_json' => new external_value(PARAM_RAW, 'JSON encoded form options'),
            'fields_json' => new external_value(PARAM_RAW, 'JSON encoded form fields'),
            'current_json' => new external_value(PARAM_RAW, 'JSON encoded current form values'),
            'dimensions_json' => new external_value(PARAM_RAW, 'JSON encoded dimension metadata'),
            'warnings' => new external_multiple_structure(self::warning_structure()),
        ]);
    }
}
