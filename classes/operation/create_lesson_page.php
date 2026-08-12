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
 * Create Lesson page operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle Lesson page through Moodle Lesson component APIs.
 */
class create_lesson_page {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $title Title.
     * @param string $content Content.
     * @param int $contentformat Contentformat.
     * @param string|null $branchesjson Branchesjson.
     * @param int $afterpageid Afterpageid.
     * @param bool $displayinmenu Displayinmenu.
     * @param bool $horizontal Horizontal.
     * @param string $pagetype Pagetype.
     * @param string|null $answersjson Answersjson.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $title,
        string $content,
        int $contentformat,
        ?string $branchesjson = null,
        int $afterpageid = 0,
        bool $displayinmenu = true,
        bool $horizontal = true,
        string $pagetype = 'content',
        ?string $answersjson = null
    ): array {
        lesson_tools::require_lesson_api();

        $course = course_tools::get_course($courseid);
        $cm = lesson_tools::get_lesson_module($course, $moduleid);
        $lesson = lesson_tools::get_lesson_object($course, $cm);
        lesson_tools::prepare_page_context($course, $cm);

        if ($afterpageid > 0) {
            lesson_tools::get_page($lesson, $cm, $afterpageid);
        }

        $context = \context_module::instance($cm->id);
        $pagetype = lesson_tools::normalise_page_type($pagetype);

        if ($pagetype === 'content') {
            if ($branchesjson === null || trim($branchesjson) === '') {
                throw new \invalid_parameter_exception('branches is required for content Lesson pages.');
            }
            if ($answersjson !== null && trim($answersjson) !== '') {
                throw new \invalid_parameter_exception('answers is only supported for Lesson question pages.');
            }

            $properties = lesson_tools::content_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_branches($branchesjson),
                $afterpageid,
                $displayinmenu,
                $horizontal
            );
        } else if ($pagetype === 'truefalse') {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($answersjson === null || trim($answersjson) === '') {
                throw new \invalid_parameter_exception('answers is required for truefalse Lesson pages.');
            }

            $properties = lesson_tools::truefalse_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_truefalse_answers($answersjson),
                $afterpageid
            );
        } else if ($pagetype === 'shortanswer') {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($answersjson === null || trim($answersjson) === '') {
                throw new \invalid_parameter_exception('answers is required for shortanswer Lesson pages.');
            }

            $properties = lesson_tools::shortanswer_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_shortanswer_answers($answersjson),
                $afterpageid
            );
        } else if ($pagetype === 'multichoice') {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($answersjson === null || trim($answersjson) === '') {
                throw new \invalid_parameter_exception('answers is required for multichoice Lesson pages.');
            }

            $properties = lesson_tools::multichoice_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_multichoice_answers($answersjson),
                $afterpageid
            );
        } else if ($pagetype === 'numerical') {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($answersjson === null || trim($answersjson) === '') {
                throw new \invalid_parameter_exception('answers is required for numerical Lesson pages.');
            }

            $properties = lesson_tools::numerical_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_numerical_answers($answersjson),
                $afterpageid
            );
        } else if ($pagetype === 'essay') {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($answersjson === null || trim($answersjson) === '') {
                throw new \invalid_parameter_exception('answers is required for essay Lesson pages.');
            }

            $properties = lesson_tools::essay_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_essay_answers($answersjson),
                $afterpageid
            );
        } else {
            if ($branchesjson !== null && trim($branchesjson) !== '') {
                throw new \invalid_parameter_exception('branches is only supported for content Lesson pages.');
            }
            if ($answersjson === null || trim($answersjson) === '') {
                throw new \invalid_parameter_exception('answers is required for matching Lesson pages.');
            }

            $properties = lesson_tools::matching_page_properties(
                $lesson,
                $title,
                $content,
                $contentformat,
                lesson_tools::decode_matching_answers($answersjson),
                $afterpageid
            );
        }

        $page = \lesson_page::create($properties, $lesson, $context, get_user_max_upload_file_size($context));
        rebuild_course_cache($course->id, true);

        return [
            'created' => true,
            'page' => lesson_tools::page_to_response($cm, $page),
        ];
    }
}
