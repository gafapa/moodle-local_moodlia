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
 * Update wiki page external function.
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
use local_moodlia\operation\update_wiki_page as update_wiki_page_operation;
use local_moodlia\operation\wiki_tools;

/**
 * External API adapter for update_wiki_page.
 */
class update_wiki_page extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Wiki course module id'),
            'page_id' => new external_value(PARAM_INT, 'Wiki page id'),
            'content' => new external_value(PARAM_RAW, 'Wiki page content'),
            'section' => new external_value(PARAM_RAW, 'Optional wiki page section title', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Wiki course module id.
     * @param int $pageid Wiki page id.
     * @param string $content Wiki page content.
     * @param string|null $section Optional wiki page section.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $pageid, string $content, ?string $section = null): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'page_id' => $pageid,
            'content' => $content,
            'section' => $section,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'page_id' => $pageid,
            'content' => $content,
            'section' => $section,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        $course = get_course($courseid);
        $cm = wiki_tools::get_wiki_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/wiki:editpage', $modulecontext);

        return update_wiki_page_operation::execute((int) $courseid, (int) $moduleid, (int) $pageid, $content, $section);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return create_wiki_page::page_returns();
    }
}
