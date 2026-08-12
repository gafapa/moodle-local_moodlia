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
 * Create Lesson page external function.
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
use local_moodlia\operation\create_lesson_page as create_lesson_page_operation;
use local_moodlia\operation\lesson_tools;

/**
 * External API adapter for create_lesson_page.
 */
class create_lesson_page extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'title' => new external_value(PARAM_RAW, 'Lesson page title'),
            'content' => new external_value(PARAM_RAW, 'Lesson page content'),
            'content_format' => new external_value(PARAM_INT, 'Moodle content format', VALUE_DEFAULT, FORMAT_HTML),
            'branches' => new external_value(PARAM_RAW, 'Optional JSON object with a branches array for content pages', VALUE_DEFAULT, null, NULL_ALLOWED),
            'after_page_id' => new external_value(PARAM_INT, 'Insert after this Lesson page id, or 0 for first', VALUE_DEFAULT, 0),
            'display_in_menu' => new external_value(PARAM_BOOL, 'Whether the page appears in the Lesson menu', VALUE_DEFAULT, true),
            'horizontal' => new external_value(PARAM_BOOL, 'Whether branch buttons use horizontal layout', VALUE_DEFAULT, true),
            'page_type' => new external_value(
                PARAM_ALPHA,
                'Lesson page type: content, essay, matching, multichoice, numerical, shortanswer, or truefalse',
                VALUE_DEFAULT,
                'content'
            ),
            'answers' => new external_value(PARAM_RAW, 'Optional JSON object with answer definitions for question pages', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Lesson course module id.
     * @param string $title Lesson page title.
     * @param string $content Lesson page content.
     * @param int $contentformat Moodle text format.
     * @param string $branches JSON branch definitions.
     * @param int $afterpageid Previous page id.
     * @param bool $displayinmenu Menu display setting.
     * @param bool $horizontal Branch layout setting.
     * @param string $pagetype Lesson page type.
     * @param string|null $answers JSON answer definitions.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $title,
        string $content,
        int $contentformat,
        ?string $branches = null,
        int $afterpageid = 0,
        bool $displayinmenu = true,
        bool $horizontal = true,
        string $pagetype = 'content',
        ?string $answers = null
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'branches' => $branchesjson,
            'after_page_id' => $afterpageid,
            'display_in_menu' => $displayinmenu,
            'horizontal' => $horizontal,
            'page_type' => $pagetype,
            'answers' => $answersjson,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'branches' => $branches,
            'after_page_id' => $afterpageid,
            'display_in_menu' => $displayinmenu,
            'horizontal' => $horizontal,
            'page_type' => $pagetype,
            'answers' => $answers,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $course = get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/lesson:manage', $modulecontext);

        return create_lesson_page_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (string) $title,
            (string) $content,
            (int) $contentformat,
            $branchesjson === null ? null : (string) $branchesjson,
            (int) $afterpageid,
            (bool) $displayinmenu,
            (bool) $horizontal,
            (string) $pagetype,
            $answersjson === null ? null : (string) $answersjson
        );
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'created' => new external_value(PARAM_BOOL, 'Whether the page was created'),
            'page' => lesson_page_response::page_structure(),
        ]);
    }
}
