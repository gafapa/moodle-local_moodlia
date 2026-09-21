<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Get Workshop grading form external function.
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
use local_moodlia\operation\get_workshop_grading_form as operation;

/**
 * External API adapter for get_workshop_grading_form.
 */
class get_workshop_grading_form extends external_api {
    /** @return external_function_parameters */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
        ]);
    }

    /**
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
        ]);
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);
        $modulecontext = \context_module::instance((int) $params['module_id']);
        self::validate_context($modulecontext);
        require_capability('mod/workshop:editdimensions', $modulecontext);

        return operation::execute((int) $params['course_id'], (int) $params['module_id']);
    }

    /** @return external_single_structure */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Workshop course module id'),
            'workshop_id' => new external_value(PARAM_INT, 'Workshop instance id'),
            'strategy' => new external_value(PARAM_PLUGIN, 'Workshop grading strategy'),
            'phase' => new external_value(PARAM_INT, 'Workshop phase'),
            'dimensions_count' => new external_value(PARAM_INT, 'Number of grading dimensions'),
            'definition_json' => new external_value(PARAM_RAW, 'Portable grading form definition as JSON'),
        ]);
    }
}
