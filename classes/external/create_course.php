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
 * Create course external function.
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
use local_moodlia\operation\course_tools;
use local_moodlia\operation\create_course as create_course_operation;

/**
 * External API adapter for create_course.
 */
class create_course extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
            'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
            'category_id' => new external_value(PARAM_INT, 'Course category id, or 0 for Moodle default', VALUE_DEFAULT, 0),
            'visible' => new external_value(PARAM_BOOL, 'Whether the course is visible', VALUE_DEFAULT, true),
            'summary' => new external_value(PARAM_RAW, 'Course summary', VALUE_DEFAULT, ''),
            'summary_format' => new external_value(PARAM_ALPHA, 'Course summary format: html or plain', VALUE_DEFAULT, 'html'),
            'course_format' => new external_value(PARAM_PLUGIN, 'Moodle course format plugin name', VALUE_DEFAULT, 'topics'),
            'enable_completion' => new external_value(PARAM_BOOL, 'Whether course completion tracking is enabled', VALUE_DEFAULT, false),
            'start_date' => new external_value(PARAM_INT, 'Course start Unix timestamp', VALUE_DEFAULT, 0),
            'end_date' => new external_value(PARAM_INT, 'Course end Unix timestamp, or 0', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param string $fullname Fullname.
     * @param string $shortname Shortname.
     * @param int $categoryid Categoryid.
     * @param bool $visible Visible.
     * @param string $summary Summary.
     * @param string $summaryformat Summaryformat.
     * @param string $courseformat Courseformat.
     * @param bool $enablecompletion Enablecompletion.
     * @param int $startdate Startdate.
     * @param int $enddate Enddate.
     * @return array
     */
    public static function execute(
        string $fullname,
        string $shortname,
        int $categoryid = 0,
        bool $visible = true,
        string $summary = '',
        string $summaryformat = 'html',
        string $courseformat = 'topics',
        bool $enablecompletion = false,
        int $startdate = 0,
        int $enddate = 0
    ): array {
        [
            'fullname' => $fullname,
            'shortname' => $shortname,
            'category_id' => $categoryid,
            'visible' => $visible,
            'summary' => $summary,
            'summary_format' => $summaryformat,
            'course_format' => $courseformat,
            'enable_completion' => $enablecompletion,
            'start_date' => $startdate,
            'end_date' => $enddate,
        ] = self::validate_parameters(self::execute_parameters(), [
            'fullname' => $fullname,
            'shortname' => $shortname,
            'category_id' => $categoryid,
            'visible' => $visible,
            'summary' => $summary,
            'summary_format' => $summaryformat,
            'course_format' => $courseformat,
            'enable_completion' => $enablecompletion,
            'start_date' => $startdate,
            'end_date' => $enddate,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $categoryid = course_tools::resolve_category_id((int) $categoryid);
        $categorycontext = \context_coursecat::instance($categoryid);
        self::validate_context($categorycontext);
        require_capability('moodle/course:create', $categorycontext);

        return create_course_operation::execute(
            $fullname,
            $shortname,
            $categoryid,
            (bool) $visible,
            $summary,
            $summaryformat,
            $courseformat,
            (bool) $enablecompletion,
            (int) $startdate,
            (int) $enddate
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
