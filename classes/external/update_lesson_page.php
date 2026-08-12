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
 * Update Lesson page external function.
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
use local_moodlia\operation\lesson_tools;
use local_moodlia\operation\update_lesson_page as update_lesson_page_operation;

/**
 * External API adapter for update_lesson_page.
 */
class update_lesson_page extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Lesson course module id'),
            'page_id' => new external_value(PARAM_INT, 'Lesson page id'),
            'title' => new external_value(PARAM_RAW, 'Optional Lesson page title', VALUE_DEFAULT, null, NULL_ALLOWED),
            'content' => new external_value(PARAM_RAW, 'Optional Lesson page content', VALUE_DEFAULT, null, NULL_ALLOWED),
            'content_format' => new external_value(PARAM_INT, 'Optional Moodle content format', VALUE_DEFAULT, null, NULL_ALLOWED),
            'branches' => new external_value(PARAM_RAW, 'Optional JSON object with a branches array', VALUE_DEFAULT, null, NULL_ALLOWED),
            'display_in_menu' => new external_value(PARAM_BOOL, 'Optional Lesson menu display setting', VALUE_DEFAULT, null, NULL_ALLOWED),
            'horizontal' => new external_value(PARAM_BOOL, 'Optional branch layout setting', VALUE_DEFAULT, null, NULL_ALLOWED),
            'answers' => new external_value(PARAM_RAW, 'Optional JSON object with answer definitions for question pages', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $pageid Pageid.
     * @param string|null $title Title.
     * @param string|null $content Content.
     * @param int|null $contentformat Contentformat.
     * @param string|null $branches Branches.
     * @param bool|null $displayinmenu Displayinmenu.
     * @param bool|null $horizontal Horizontal.
     * @param string|null $answers Answers.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $pageid,
        ?string $title = null,
        ?string $content = null,
        ?int $contentformat = null,
        ?string $branches = null,
        ?bool $displayinmenu = null,
        ?bool $horizontal = null,
        ?string $answers = null
    ): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'page_id' => $pageid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'branches' => $branchesjson,
            'display_in_menu' => $displayinmenu,
            'horizontal' => $horizontal,
            'answers' => $answersjson,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'page_id' => $pageid,
            'title' => $title,
            'content' => $content,
            'content_format' => $contentformat,
            'branches' => $branches,
            'display_in_menu' => $displayinmenu,
            'horizontal' => $horizontal,
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

        return update_lesson_page_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $pageid,
            $title === null ? null : (string) $title,
            $content === null ? null : (string) $content,
            $contentformat === null ? null : (int) $contentformat,
            $branchesjson === null ? null : (string) $branchesjson,
            $displayinmenu === null ? null : (bool) $displayinmenu,
            $horizontal === null ? null : (bool) $horizontal,
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
            'updated' => new external_value(PARAM_BOOL, 'Whether the page was updated'),
            'page' => lesson_page_response::page_structure(),
        ]);
    }
}
