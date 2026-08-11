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
 * Set Workshop grading form external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\set_workshop_grading_form as set_workshop_grading_form_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for set_workshop_grading_form.
 */
class set_workshop_grading_form extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'strategy' => new external_value(PARAM_PLUGIN, 'Workshop grading strategy. Supports accumulative, comments, numerrors, and rubric'),
            'definition' => new external_value(PARAM_RAW, 'JSON object with strategy dimensions'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @param int $module_id Workshop course module id.
     * @param string $strategy Workshop grading strategy.
     * @param string $definition JSON definition.
     * @return array
     */
    public static function execute(int $course_id, int $module_id, string $strategy, string $definition): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'strategy' => $strategy,
            'definition' => $definitionjson,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'strategy' => $strategy,
            'definition' => $definition,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:editdimensions', $modulecontext);

        return set_workshop_grading_form_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (string) $strategy,
            (string) $definitionjson
        );
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
            'strategy' => new external_value(PARAM_PLUGIN, 'Workshop grading strategy'),
            'updated' => new external_value(PARAM_BOOL, 'Whether the grading form was updated'),
            'dimensions_count' => new external_value(PARAM_INT, 'Saved dimension count'),
            'dimensions_json' => new external_value(PARAM_RAW, 'Saved dimension metadata as JSON'),
        ]);
    }
}
