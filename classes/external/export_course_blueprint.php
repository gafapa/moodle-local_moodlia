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
 * MoodlIA plugin implementation.
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
use local_moodlia\operation\export_course_blueprint as export_course_blueprint_operation;

/**
 * External API adapter for export_course_blueprint.
 */
class export_course_blueprint extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'include_contents' => new external_value(PARAM_BOOL, 'Include section and module shells', VALUE_DEFAULT, true),
            'include_groups' => new external_value(PARAM_BOOL, 'Include course groups', VALUE_DEFAULT, true),
        ]);
    }

    public static function execute(int $course_id, bool $include_contents = true, bool $include_groups = true): array {
        [
            'course_id' => $courseid,
            'include_contents' => $includecontents,
            'include_groups' => $includegroups,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'include_contents' => $include_contents,
            'include_groups' => $include_groups,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return export_course_blueprint_operation::execute((int) $courseid, (bool) $includecontents, (bool) $includegroups);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'blueprint_json' => new external_value(PARAM_RAW, 'JSON-encoded portable MoodlIA course blueprint'),
        ]);
    }
}
