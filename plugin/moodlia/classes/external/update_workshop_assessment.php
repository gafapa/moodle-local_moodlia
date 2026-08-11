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
 * Update workshop assessment external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\update_workshop_assessment as update_workshop_assessment_operation;
use local_moodlia\operation\workshop_tools;

/**
 * External API adapter for update_workshop_assessment.
 */
class update_workshop_assessment extends external_api {
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
            'data_json' => new external_value(PARAM_RAW, 'JSON array of Moodle workshop assessment name/value rows'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @return array
     */
    public static function execute(int $course_id, int $module_id, int $assessment_id, string $data_json): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'assessment_id' => $assessmentid,
            'data_json' => $datajson,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'assessment_id' => $assessment_id,
            'data_json' => $data_json,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:peerassess', $modulecontext);

        return update_workshop_assessment_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $assessmentid,
            (string) $datajson
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
            'updated' => new external_value(PARAM_BOOL, 'Whether the assessment was updated'),
            'raw_grade' => new external_value(PARAM_FLOAT, 'Raw percentage grade'),
            'warnings' => new external_multiple_structure(self::warning_structure()),
        ]);
    }
}
