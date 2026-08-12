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
 * Get course details external function.
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
use local_moodlia\operation\course_tools;
use local_moodlia\operation\get_course_details as get_course_details_operation;

/**
 * External API adapter for get_course_details.
 */
class get_course_details extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $course_id Moodle course id.
     * @return array
     */
    public static function execute(int $course_id): array {
        ['course_id' => $courseid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = course_tools::get_course((int) $courseid);
        $coursecontext = \context_course::instance($course->id);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_course_details_operation::execute((int) $courseid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
            'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
            'category_id' => new external_value(PARAM_INT, 'Course category id'),
            'visible' => new external_value(PARAM_BOOL, 'Whether the course is visible'),
            'summary' => new external_value(PARAM_RAW, 'Rendered course summary'),
            'summary_format' => new external_value(PARAM_ALPHA, 'Course summary format'),
            'format' => new external_value(PARAM_PLUGIN, 'Moodle course format plugin name'),
            'enable_completion' => new external_value(PARAM_BOOL, 'Whether course completion tracking is enabled'),
            'start_date' => new external_value(PARAM_INT, 'Course start Unix timestamp'),
            'end_date' => new external_value(PARAM_INT, 'Course end Unix timestamp'),
            'url' => new external_value(PARAM_URL, 'Course URL'),
        ]);
    }
}
