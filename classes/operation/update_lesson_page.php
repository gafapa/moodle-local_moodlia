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
 * Update Lesson page operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a supported Moodle Lesson page through Moodle Lesson component APIs.
 */
class update_lesson_page {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Lesson course module id.
     * @param int $pageid Lesson page id.
     * @param string|null $title Optional page title.
     * @param string|null $content Optional page content.
     * @param int|null $contentformat Optional Moodle text format.
     * @param string|null $branchesjson Optional JSON branch definitions.
     * @param bool|null $displayinmenu Optional menu display setting.
     * @param bool|null $horizontal Optional branch layout setting.
     * @param string|null $answersjson Optional JSON answer definitions for question pages.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $pageid,
        ?string $title = null,
        ?string $content = null,
        ?int $contentformat = null,
        ?string $branchesjson = null,
        ?bool $displayinmenu = null,
        ?bool $horizontal = null,
        ?string $answersjson = null
    ): array {
        lesson_tools::require_lesson_api();

        $course = course_tools::get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, $moduleid);
        $lesson = lesson_tools::get_lesson_object($course, $cm);
        lesson_tools::prepare_page_context($course, $cm);
        $page = lesson_tools::get_page($lesson, $cm, $pageid);
        $current = $page->properties();

        if (
            !lesson_tools::is_content_page($current) &&
            !lesson_tools::is_truefalse_page($current) &&
            !lesson_tools::is_shortanswer_page($current) &&
            !lesson_tools::is_multichoice_page($current) &&
            !lesson_tools::is_numerical_page($current) &&
            !lesson_tools::is_essay_page($current) &&
            !lesson_tools::is_matching_page($current)
        ) {
            throw new \invalid_parameter_exception(
                'Only Lesson content, essay, matching, truefalse, shortanswer, multichoice, and numerical pages are supported for update_lesson_page.'
            );
        }

        if (
            $title === null &&
            $content === null &&
            $contentformat === null &&
            $branchesjson === null &&
            $displayinmenu === null &&
            $horizontal === null &&
            $answersjson === null
        ) {
            throw new \invalid_parameter_exception('At least one page field is required.');
        }

        if (lesson_tools::is_content_page($current)) {
            if ($answersjson !== null && trim($answersjson) !== '') {
                throw new \invalid_parameter_exception('answers is only supported for Lesson question pages.');
            }

            $branches = $branchesjson === null
                ? lesson_tools::branches_from_page($page)
                : lesson_tools::decode_branches($branchesjson);

            $properties = lesson_tools::content_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $branches,
                0,
                $displayinmenu ?? ((int) ($current->display ?? 0) === 1),
                $horizontal ?? ((int) ($current->layout ?? 0) === 1)
            );
        } else if (lesson_tools::is_truefalse_page($current)) {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($displayinmenu !== null || $horizontal !== null) {
                throw new \invalid_parameter_exception('display_in_menu and horizontal are only supported for content Lesson pages.');
            }

            $answers = $answersjson === null
                ? lesson_tools::truefalse_answers_from_page($page)
                : lesson_tools::decode_truefalse_answers($answersjson);

            $properties = lesson_tools::truefalse_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $answers,
                0
            );
        } else if (lesson_tools::is_shortanswer_page($current)) {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($displayinmenu !== null || $horizontal !== null) {
                throw new \invalid_parameter_exception('display_in_menu and horizontal are only supported for content Lesson pages.');
            }

            $answers = $answersjson === null
                ? lesson_tools::shortanswer_answers_from_page($page)
                : lesson_tools::decode_shortanswer_answers($answersjson);

            $properties = lesson_tools::shortanswer_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $answers,
                0
            );
        } else if (lesson_tools::is_multichoice_page($current)) {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($displayinmenu !== null || $horizontal !== null) {
                throw new \invalid_parameter_exception('display_in_menu and horizontal are only supported for content Lesson pages.');
            }

            $answers = $answersjson === null
                ? lesson_tools::multichoice_answers_from_page($page)
                : lesson_tools::decode_multichoice_answers($answersjson);

            $properties = lesson_tools::multichoice_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $answers,
                0
            );
        } else if (lesson_tools::is_numerical_page($current)) {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($displayinmenu !== null || $horizontal !== null) {
                throw new \invalid_parameter_exception('display_in_menu and horizontal are only supported for content Lesson pages.');
            }

            $answers = $answersjson === null
                ? lesson_tools::numerical_answers_from_page($page)
                : lesson_tools::decode_numerical_answers($answersjson);

            $properties = lesson_tools::numerical_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $answers,
                0
            );
        } else if (lesson_tools::is_essay_page($current)) {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($displayinmenu !== null || $horizontal !== null) {
                throw new \invalid_parameter_exception('display_in_menu and horizontal are only supported for content Lesson pages.');
            }

            $answers = $answersjson === null
                ? lesson_tools::essay_answers_from_page($page)
                : lesson_tools::decode_essay_answers($answersjson);

            $properties = lesson_tools::essay_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $answers,
                0
            );
        } else {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($displayinmenu !== null || $horizontal !== null) {
                throw new \invalid_parameter_exception('display_in_menu and horizontal are only supported for content Lesson pages.');
            }

            $answers = $answersjson === null
                ? lesson_tools::matching_answers_from_page($page)
                : lesson_tools::decode_matching_answers($answersjson);

            $properties = lesson_tools::matching_page_properties(
                $lesson,
                $title ?? (string) ($current->title ?? ''),
                $content ?? (string) ($current->contents ?? ''),
                $contentformat ?? (int) ($current->contentsformat ?? FORMAT_HTML),
                $answers,
                0
            );
        }

        $context = \context_module::instance($cm->id);
        $page->update($properties, $context, get_user_max_upload_file_size($context));
        rebuild_course_cache($course->id, true);

        return [
            'updated' => true,
            'page' => lesson_tools::page_to_response($cm, \lesson_page::load($pageid, $lesson)),
        ];
    }
}
