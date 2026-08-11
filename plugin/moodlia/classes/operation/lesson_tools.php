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
 * Shared lesson helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle Lesson operations.
 */
class lesson_tools {
    /** Moodle Lesson content page type id. */
    private const CONTENT_PAGE_TYPE = 20;

    /** Moodle Lesson true/false question page type id. */
    private const TRUEFALSE_PAGE_TYPE = 2;

    /** Moodle Lesson multichoice question page type id. */
    private const MULTICHOICE_PAGE_TYPE = 3;

    /** Moodle Lesson short-answer question page type id. */
    private const SHORTANSWER_PAGE_TYPE = 1;

    /** Moodle Lesson numerical question page type id. */
    private const NUMERICAL_PAGE_TYPE = 8;

    /** Moodle Lesson essay question page type id. */
    private const ESSAY_PAGE_TYPE = 10;

    /** Moodle Lesson matching question page type id. */
    private const MATCHING_PAGE_TYPE = 5;

    /**
     * Load Moodle Lesson APIs.
     */
    public static function require_lesson_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/lesson/lib.php');
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');
        require_once($CFG->dirroot . '/mod/lesson/classes/external.php');
    }

    /**
     * Verify that a course module belongs to a Lesson activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_lesson_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'lesson') {
            throw new \invalid_parameter_exception('module_id must reference a lesson activity.');
        }

        return $cm;
    }

    /**
     * Return lesson instance data exposed through Moodle's Lesson external API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Lesson course module.
     * @return array
     */
    public static function get_lesson_instance_data(\stdClass $course, \cm_info $cm): array {
        self::require_lesson_api();

        $result = \mod_lesson_external::get_lessons_by_courses([(int) $course->id]);
        foreach (($result['lessons'] ?? []) as $lesson) {
            $lesson = (array) $lesson;
            if (
                (int) ($lesson['id'] ?? 0) === (int) $cm->instance ||
                (int) ($lesson['coursemodule'] ?? $lesson['cmid'] ?? $lesson['coursemoduleid'] ?? 0) === (int) $cm->id
            ) {
                return $lesson;
            }
        }

        throw new \invalid_parameter_exception('module_id must reference a visible lesson activity in the selected course.');
    }

    /**
     * Return a Lesson domain object with course and course-module context.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Lesson course module.
     * @return \lesson
     */
    public static function get_lesson_object(\stdClass $course, \cm_info $cm): \lesson {
        self::require_lesson_api();

        $data = (object) self::get_lesson_instance_data($course, $cm);
        $data->id = (int) $cm->instance;
        $data->course = (int) $course->id;
        $cmrecord = get_coursemodule_from_id('lesson', (int) $cm->id, (int) $course->id, false, MUST_EXIST);

        return new \lesson($data, $cmrecord, $course);
    }

    /**
     * Prepare Moodle page globals required by Lesson page component APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Lesson course module.
     * @return \stdClass Course-module record.
     */
    public static function prepare_page_context(\stdClass $course, \cm_info $cm): \stdClass {
        global $PAGE;

        $cmrecord = get_coursemodule_from_id('lesson', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $modulecontext = \context_module::instance((int) $cm->id);
        $PAGE->set_course($course);
        $PAGE->set_cm($cmrecord, $course);
        $PAGE->set_context($modulecontext);

        return $cmrecord;
    }

    /**
     * Return a Lesson page and verify ownership.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param \cm_info $cm Lesson course module.
     * @param int $pageid Lesson page id.
     * @return \lesson_page
     */
    public static function get_page(\lesson $lesson, \cm_info $cm, int $pageid): \lesson_page {
        if ($pageid <= 0) {
            throw new \invalid_parameter_exception('page_id must be a positive integer.');
        }

        $page = \lesson_page::load($pageid, $lesson);
        $properties = $page->properties();
        if ((int) ($properties->lessonid ?? 0) !== (int) $cm->instance) {
            throw new \invalid_parameter_exception('page_id must reference a page in the selected lesson module.');
        }

        return $page;
    }

    /**
     * Return a canonical response for one Lesson page.
     *
     * @param \cm_info $cm Lesson course module.
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function page_to_response(\cm_info $cm, \lesson_page $page): array {
        $properties = $page->properties();
        $answers = [];
        $answerids = [];
        $jumps = [];

        foreach ($page->get_answers() as $answer) {
            $answers[] = [
                'answer_id' => (int) ($answer->id ?? 0),
                'title' => (string) ($answer->answer ?? ''),
                'title_format' => (int) ($answer->answerformat ?? FORMAT_MOODLE),
                'response' => (string) ($answer->response ?? ''),
                'response_format' => (int) ($answer->responseformat ?? FORMAT_MOODLE),
                'jump_to' => (int) ($answer->jumpto ?? 0),
                'score' => (float) ($answer->score ?? 0),
            ];
            $answerids[] = (int) ($answer->id ?? 0);
            $jumps[] = (int) ($answer->jumpto ?? 0);
        }

        return [
            'page_id' => (int) ($properties->id ?? 0),
            'lesson_id' => (int) ($properties->lessonid ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'previous_page_id' => (int) ($properties->prevpageid ?? 0),
            'next_page_id' => (int) ($properties->nextpageid ?? 0),
            'question_type' => (int) ($properties->qtype ?? 0),
            'question_option' => (int) ($properties->qoption ?? 0),
            'layout' => (int) ($properties->layout ?? 0),
            'display' => (int) ($properties->display ?? 0),
            'display_in_menu_block' => (bool) ($properties->display ?? false),
            'type' => (int) ($page->type ?? 0),
            'type_id' => (int) ($page->typeid ?? ($properties->qtype ?? 0)),
            'type_string' => (string) ($page->typestring ?? ''),
            'title' => (string) ($properties->title ?? ''),
            'content' => (string) ($properties->contents ?? ''),
            'content_format' => (int) ($properties->contentsformat ?? FORMAT_HTML),
            'time_created' => (int) ($properties->timecreated ?? 0),
            'time_modified' => (int) ($properties->timemodified ?? 0),
            'answer_ids' => $answerids,
            'jumps' => $jumps,
            'files_count' => 0,
            'files_size_total' => 0,
            'branches_count' => count($answers),
            'branches' => $answers,
        ];
    }

    /**
     * Decode and validate Lesson content page branches.
     *
     * @param string $branchesjson JSON object or array.
     * @return array
     */
    public static function decode_branches(string $branchesjson): array {
        $decoded = json_decode($branchesjson, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('branches must be a JSON array or an object with a branches array.');
        }

        $items = self::is_list_array($decoded) ? $decoded : ($decoded['branches'] ?? null);
        if (!is_array($items) || !self::is_list_array($items) || count($items) === 0) {
            throw new \invalid_parameter_exception('branches must contain at least one branch.');
        }

        $branches = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new \invalid_parameter_exception('Each branch must be an object.');
            }
            $title = trim((string) ($item['title'] ?? $item['answer'] ?? ''));
            if ($title === '') {
                throw new \invalid_parameter_exception('Each branch title must be non-empty.');
            }
            $branches[] = [
                'title' => $title,
                'response' => (string) ($item['response'] ?? ''),
                'jump_to' => self::normalise_jump($item['jump_to'] ?? $item['jumpto'] ?? -1),
                'score' => (float) ($item['score'] ?? 0),
            ];
        }

        return $branches;
    }

    /**
     * Normalize the requested Lesson page type.
     *
     * @param string $pagetype Raw page type.
     * @return string
     */
    public static function normalise_page_type(string $pagetype): string {
        $normalized = strtolower(trim($pagetype));
        $aliases = [
            'branchtable' => 'content',
            'branch_table' => 'content',
            'true_false' => 'truefalse',
            'true-false' => 'truefalse',
            'multiple_choice' => 'multichoice',
            'multiple-choice' => 'multichoice',
            'short_answer' => 'shortanswer',
            'short-answer' => 'shortanswer',
            'match' => 'matching',
        ];
        $normalized = $aliases[$normalized] ?? $normalized;

        if (!in_array($normalized, ['content', 'essay', 'matching', 'truefalse', 'shortanswer', 'multichoice', 'numerical'], true)) {
            throw new \invalid_parameter_exception(
                'page_type must be content, essay, matching, truefalse, shortanswer, multichoice, or numerical.'
            );
        }

        return $normalized;
    }

    /**
     * Return whether a Lesson page is a supported content page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_content_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::CONTENT_PAGE_TYPE;
    }

    /**
     * Return whether a Lesson page is a supported true/false question page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_truefalse_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::TRUEFALSE_PAGE_TYPE;
    }

    /**
     * Return whether a Lesson page is a supported multichoice question page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_multichoice_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::MULTICHOICE_PAGE_TYPE;
    }

    /**
     * Return whether a Lesson page is a supported short-answer question page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_shortanswer_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::SHORTANSWER_PAGE_TYPE;
    }

    /**
     * Return whether a Lesson page is a supported numerical question page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_numerical_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::NUMERICAL_PAGE_TYPE;
    }

    /**
     * Return whether a Lesson page is a supported essay question page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_essay_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::ESSAY_PAGE_TYPE;
    }

    /**
     * Return whether a Lesson page is a supported matching question page.
     *
     * @param \stdClass $properties Page properties.
     * @return bool
     */
    public static function is_matching_page(\stdClass $properties): bool {
        return (int) ($properties->qtype ?? 0) === self::MATCHING_PAGE_TYPE;
    }

    /**
     * Decode and validate Lesson true/false answers.
     *
     * @param string $answersjson JSON object with correct/wrong answers or an answers array.
     * @return array
     */
    public static function decode_truefalse_answers(string $answersjson): array {
        $decoded = json_decode($answersjson, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('answers must be a JSON object.');
        }

        if (isset($decoded['correct']) || isset($decoded['wrong'])) {
            $items = [$decoded['correct'] ?? null, $decoded['wrong'] ?? null];
        } else {
            $items = self::is_list_array($decoded) ? $decoded : ($decoded['answers'] ?? null);
        }

        if (!is_array($items) || !self::is_list_array($items) || count($items) !== 2) {
            throw new \invalid_parameter_exception('answers must contain exactly two truefalse answers.');
        }

        $answers = [];
        $seen = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new \invalid_parameter_exception('Each truefalse answer must be an object.');
            }

            $answer = trim((string) ($item['answer'] ?? $item['title'] ?? $item['label'] ?? ''));
            if ($answer === '') {
                throw new \invalid_parameter_exception('Each truefalse answer text must be non-empty.');
            }

            $key = strtolower($answer);
            if (array_key_exists($key, $seen)) {
                throw new \invalid_parameter_exception('truefalse answer texts must be unique.');
            }
            $seen[$key] = true;

            $answers[] = [
                'answer' => $answer,
                'answer_format' => self::normalise_text_format($item['answer_format'] ?? $item['title_format'] ?? FORMAT_HTML, 'answer_format'),
                'response' => (string) ($item['response'] ?? ''),
                'response_format' => self::normalise_text_format($item['response_format'] ?? FORMAT_HTML, 'response_format'),
                'jump_to' => self::normalise_jump($item['jump_to'] ?? $item['jumpto'] ?? ($index === 0 ? -1 : 0), 'answer jump_to'),
                'score' => self::normalise_score($item['score'] ?? ($index === 0 ? 1 : 0)),
            ];
        }

        return $answers;
    }

    /**
     * Decode and validate Lesson multichoice answers.
     *
     * @param string $answersjson JSON object with answers and optional multi_answer.
     * @return array
     */
    public static function decode_multichoice_answers(string $answersjson): array {
        $decoded = json_decode($answersjson, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('answers must be a JSON object.');
        }

        $items = self::is_list_array($decoded) ? $decoded : ($decoded['answers'] ?? null);
        if (!is_array($items) || !self::is_list_array($items) || count($items) < 2) {
            throw new \invalid_parameter_exception('answers must contain at least two multichoice answers.');
        }

        $answers = [];
        $seen = [];
        $positive = 0;
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new \invalid_parameter_exception('Each multichoice answer must be an object.');
            }

            $answer = trim((string) ($item['answer'] ?? $item['title'] ?? $item['label'] ?? ''));
            if ($answer === '') {
                throw new \invalid_parameter_exception('Each multichoice answer text must be non-empty.');
            }

            $key = strtolower($answer);
            if (array_key_exists($key, $seen)) {
                throw new \invalid_parameter_exception('multichoice answer texts must be unique.');
            }
            $seen[$key] = true;

            $score = self::normalise_score($item['score'] ?? ($index === 0 ? 1 : 0));
            if ($score > 0) {
                $positive++;
            }

            $answers[] = [
                'answer' => $answer,
                'answer_format' => self::normalise_text_format($item['answer_format'] ?? $item['title_format'] ?? FORMAT_HTML, 'answer_format'),
                'response' => (string) ($item['response'] ?? ''),
                'response_format' => self::normalise_text_format($item['response_format'] ?? FORMAT_HTML, 'response_format'),
                'jump_to' => self::normalise_jump($item['jump_to'] ?? $item['jumpto'] ?? ($index === 0 ? -1 : 0), 'answer jump_to'),
                'score' => $score,
            ];
        }

        $multianswer = (bool) ($decoded['multi_answer'] ?? $decoded['multianswer'] ?? false);
        if (!$multianswer && $positive !== 1) {
            throw new \invalid_parameter_exception('Single-answer multichoice pages require exactly one positive-score answer.');
        }
        if ($multianswer && $positive === 0) {
            throw new \invalid_parameter_exception('Multi-answer multichoice pages require at least one positive-score answer.');
        }

        return [
            'multi_answer' => $multianswer,
            'answers' => $answers,
        ];
    }

    /**
     * Decode and validate Lesson short-answer answers.
     *
     * @param string $answersjson JSON object with answers and optional use_regular_expressions.
     * @return array
     */
    public static function decode_shortanswer_answers(string $answersjson): array {
        $decoded = json_decode($answersjson, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('answers must be a JSON object.');
        }

        $items = self::is_list_array($decoded) ? $decoded : ($decoded['answers'] ?? null);
        if (!is_array($items) || !self::is_list_array($items) || count($items) < 1) {
            throw new \invalid_parameter_exception('answers must contain at least one shortanswer answer.');
        }

        $answers = self::decode_open_question_answers($items, 'shortanswer');

        return [
            'use_regular_expressions' => (bool) ($decoded['use_regular_expressions'] ?? $decoded['use_regex'] ?? false),
            'answers' => $answers,
        ];
    }

    /**
     * Decode and validate Lesson numerical answers.
     *
     * @param string $answersjson JSON object with numerical answers.
     * @return array
     */
    public static function decode_numerical_answers(string $answersjson): array {
        $decoded = json_decode($answersjson, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('answers must be a JSON object.');
        }

        $items = self::is_list_array($decoded) ? $decoded : ($decoded['answers'] ?? null);
        if (!is_array($items) || !self::is_list_array($items) || count($items) < 1) {
            throw new \invalid_parameter_exception('answers must contain at least one numerical answer.');
        }

        $answers = self::decode_open_question_answers($items, 'numerical');
        foreach ($answers as $answer) {
            self::normalise_numerical_answer($answer['answer']);
        }

        return [
            'answers' => $answers,
        ];
    }

    /**
     * Decode and validate a Lesson essay grading definition.
     *
     * @param string $answersjson JSON object with jump and score settings.
     * @return array
     */
    public static function decode_essay_answers(string $answersjson): array {
        $decoded = json_decode($answersjson, true);
        if (!is_array($decoded) || self::is_list_array($decoded)) {
            throw new \invalid_parameter_exception('answers must be a JSON object for essay pages.');
        }

        return [
            'jump_to' => self::normalise_jump($decoded['jump_to'] ?? $decoded['jumpto'] ?? -1, 'essay jump_to'),
            'score' => self::normalise_score($decoded['score'] ?? 1),
        ];
    }

    /**
     * Decode and validate a Lesson matching definition.
     *
     * @param string $answersjson JSON object with responses and matching pairs.
     * @return array
     */
    public static function decode_matching_answers(string $answersjson): array {
        $decoded = json_decode($answersjson, true);
        if (!is_array($decoded) || self::is_list_array($decoded)) {
            throw new \invalid_parameter_exception('answers must be a JSON object for matching pages.');
        }

        $pairs = $decoded['pairs'] ?? null;
        if (!is_array($pairs) || !self::is_list_array($pairs) || count($pairs) < 2) {
            throw new \invalid_parameter_exception('matching answers must contain at least two pairs.');
        }

        $normalizedpairs = [];
        $seenprompts = [];
        $seenmatches = [];
        foreach ($pairs as $pair) {
            if (!is_array($pair)) {
                throw new \invalid_parameter_exception('Each matching pair must be an object.');
            }

            $prompt = trim((string) ($pair['prompt'] ?? $pair['answer'] ?? ''));
            $match = trim((string) ($pair['match'] ?? $pair['response'] ?? ''));
            if ($prompt === '' || $match === '') {
                throw new \invalid_parameter_exception('Each matching pair must contain non-empty prompt and match values.');
            }
            if ($match !== strip_tags($match)) {
                throw new \invalid_parameter_exception('Matching pair match values must be plain text.');
            }

            $promptkey = strtolower($prompt);
            $matchkey = strtolower($match);
            if (isset($seenprompts[$promptkey]) || isset($seenmatches[$matchkey])) {
                throw new \invalid_parameter_exception('Matching pair prompts and match values must be unique.');
            }
            $seenprompts[$promptkey] = true;
            $seenmatches[$matchkey] = true;

            $normalizedpairs[] = [
                'prompt' => $prompt,
                'prompt_format' => self::normalise_text_format(
                    $pair['prompt_format'] ?? $pair['answer_format'] ?? FORMAT_HTML,
                    'prompt_format'
                ),
                'match' => $match,
            ];
        }

        return [
            'correct_response' => (string) ($decoded['correct_response'] ?? ''),
            'correct_response_format' => self::normalise_text_format(
                $decoded['correct_response_format'] ?? FORMAT_HTML,
                'correct_response_format'
            ),
            'correct_jump_to' => self::normalise_jump(
                $decoded['correct_jump_to'] ?? $decoded['correct_jumpto'] ?? -1,
                'correct_jump_to'
            ),
            'correct_score' => self::normalise_score($decoded['correct_score'] ?? 1),
            'wrong_response' => (string) ($decoded['wrong_response'] ?? ''),
            'wrong_response_format' => self::normalise_text_format(
                $decoded['wrong_response_format'] ?? FORMAT_HTML,
                'wrong_response_format'
            ),
            'wrong_jump_to' => self::normalise_jump(
                $decoded['wrong_jump_to'] ?? $decoded['wrong_jumpto'] ?? 0,
                'wrong_jump_to'
            ),
            'wrong_score' => self::normalise_score($decoded['wrong_score'] ?? 0),
            'pairs' => $normalizedpairs,
        ];
    }

    /**
     * Decode shared open-question answer rows.
     *
     * @param array $items Answer rows.
     * @param string $label Page-type label for errors.
     * @return array
     */
    private static function decode_open_question_answers(array $items, string $label): array {
        $answers = [];
        $seen = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new \invalid_parameter_exception('Each ' . $label . ' answer must be an object.');
            }

            $answer = trim((string) ($item['answer'] ?? $item['title'] ?? $item['label'] ?? ''));
            if ($answer === '') {
                throw new \invalid_parameter_exception('Each ' . $label . ' answer text must be non-empty.');
            }

            $key = strtolower($answer);
            if (array_key_exists($key, $seen)) {
                throw new \invalid_parameter_exception($label . ' answer texts must be unique.');
            }
            $seen[$key] = true;

            $answers[] = [
                'answer' => $answer,
                'answer_format' => self::normalise_text_format($item['answer_format'] ?? $item['title_format'] ?? FORMAT_HTML, 'answer_format'),
                'response' => (string) ($item['response'] ?? ''),
                'response_format' => self::normalise_text_format($item['response_format'] ?? FORMAT_HTML, 'response_format'),
                'jump_to' => self::normalise_jump($item['jump_to'] ?? $item['jumpto'] ?? ($index === 0 ? -1 : 0), 'answer jump_to'),
                'score' => self::normalise_score($item['score'] ?? ($index === 0 ? 1 : 0)),
            ];
        }

        return $answers;
    }

    /**
     * Validate a Lesson numerical answer or inclusive range.
     *
     * @param string $answer Answer value or min:max range.
     */
    private static function normalise_numerical_answer(string $answer): void {
        $parts = explode(':', $answer);
        if (count($parts) > 2) {
            throw new \invalid_parameter_exception('numerical answers must be numbers or min:max ranges.');
        }

        foreach ($parts as $part) {
            if (!is_numeric(trim($part))) {
                throw new \invalid_parameter_exception('numerical answers must be numbers or min:max ranges.');
            }
        }

        if (count($parts) === 2 && (float) trim($parts[0]) > (float) trim($parts[1])) {
            throw new \invalid_parameter_exception('numerical answer range minimum must not be greater than maximum.');
        }
    }

    /**
     * Build Moodle Lesson page properties for a content page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $branches Normalized branch rows.
     * @param int $afterpageid Previous page id or 0 for first.
     * @param bool $displayinmenu Whether the page appears in Lesson menu.
     * @param bool $horizontal Whether branch buttons are horizontal.
     * @return \stdClass
     */
    public static function content_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $branches,
        int $afterpageid = 0,
        bool $displayinmenu = true,
        bool $horizontal = true
    ): \stdClass {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title must be non-empty.');
        }

        $properties = (object) [
            'title' => $title,
            'contents_editor' => [
                'text' => $content,
                'format' => $contentformat,
            ],
            'qtype' => self::CONTENT_PAGE_TYPE,
            'pageid' => max(0, $afterpageid),
            'answer_editor' => [],
            'response_editor' => [],
            'jumpto' => [],
            'score' => [],
        ];

        if ($horizontal) {
            $properties->layout = 1;
        }
        if ($displayinmenu) {
            $properties->display = 1;
        }

        $index = 0;
        foreach (array_slice($branches, 0, max(1, (int) $lesson->maxanswers)) as $branch) {
            $properties->answer_editor[$index] = $branch['title'];
            $properties->response_editor[$index] = [
                'text' => $branch['response'],
                'format' => FORMAT_HTML,
            ];
            $properties->jumpto[$index] = $branch['jump_to'];
            $properties->score[$index] = $branch['score'];
            $index++;
        }
        while ($index < (int) $lesson->maxanswers) {
            $properties->answer_editor[$index] = '';
            $properties->response_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->jumpto[$index] = 0;
            $properties->score[$index] = 0;
            $index++;
        }

        return $properties;
    }

    /**
     * Build Moodle Lesson page properties for a true/false question page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $answers Normalized true/false answers.
     * @param int $afterpageid Previous page id or 0 for first.
     * @return \stdClass
     */
    public static function truefalse_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $answers,
        int $afterpageid = 0
    ): \stdClass {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title must be non-empty.');
        }

        if (count($answers) !== 2) {
            throw new \invalid_parameter_exception('truefalse pages require exactly two answers.');
        }

        $properties = (object) [
            'title' => $title,
            'contents_editor' => [
                'text' => $content,
                'format' => $contentformat,
            ],
            'qtype' => self::TRUEFALSE_PAGE_TYPE,
            'pageid' => max(0, $afterpageid),
            'answer_editor' => [],
            'response_editor' => [],
            'jumpto' => [],
            'score' => [],
        ];

        foreach ($answers as $index => $answer) {
            $properties->answer_editor[$index] = [
                'text' => $answer['answer'],
                'format' => $answer['answer_format'],
            ];
            $properties->response_editor[$index] = [
                'text' => $answer['response'],
                'format' => $answer['response_format'],
            ];
            $properties->jumpto[$index] = $answer['jump_to'];
            $properties->score[$index] = $answer['score'];
        }
        for ($index = count($answers); $index < (int) $lesson->maxanswers; $index++) {
            $properties->answer_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->response_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->jumpto[$index] = 0;
            $properties->score[$index] = 0;
        }

        return $properties;
    }

    /**
     * Build Moodle Lesson page properties for a multichoice question page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $definition Normalized multichoice answers.
     * @param int $afterpageid Previous page id or 0 for first.
     * @return \stdClass
     */
    public static function multichoice_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $definition,
        int $afterpageid = 0
    ): \stdClass {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title must be non-empty.');
        }

        $answers = $definition['answers'] ?? [];
        if (count($answers) < 2) {
            throw new \invalid_parameter_exception('multichoice pages require at least two answers.');
        }
        if (count($answers) > (int) $lesson->maxanswers) {
            throw new \invalid_parameter_exception('multichoice answer count must not exceed the Lesson max_answers setting.');
        }

        $properties = (object) [
            'title' => $title,
            'contents_editor' => [
                'text' => $content,
                'format' => $contentformat,
            ],
            'qtype' => self::MULTICHOICE_PAGE_TYPE,
            'pageid' => max(0, $afterpageid),
            'answer_editor' => [],
            'response_editor' => [],
            'jumpto' => [],
            'score' => [],
        ];

        if (!empty($definition['multi_answer'])) {
            $properties->qoption = 1;
        }

        foreach ($answers as $index => $answer) {
            $properties->answer_editor[$index] = [
                'text' => $answer['answer'],
                'format' => $answer['answer_format'],
            ];
            $properties->response_editor[$index] = [
                'text' => $answer['response'],
                'format' => $answer['response_format'],
            ];
            $properties->jumpto[$index] = $answer['jump_to'];
            $properties->score[$index] = $answer['score'];
        }
        for ($index = count($answers); $index < (int) $lesson->maxanswers; $index++) {
            $properties->answer_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->response_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->jumpto[$index] = 0;
            $properties->score[$index] = 0;
        }

        return $properties;
    }

    /**
     * Build Moodle Lesson page properties for a short-answer question page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $definition Normalized short-answer definition.
     * @param int $afterpageid Previous page id or 0 for first.
     * @return \stdClass
     */
    public static function shortanswer_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $definition,
        int $afterpageid = 0
    ): \stdClass {
        return self::open_question_page_properties(
            $lesson,
            $title,
            $content,
            $contentformat,
            self::SHORTANSWER_PAGE_TYPE,
            $definition['answers'] ?? [],
            $afterpageid,
            !empty($definition['use_regular_expressions'])
        );
    }

    /**
     * Build Moodle Lesson page properties for a numerical question page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $definition Normalized numerical definition.
     * @param int $afterpageid Previous page id or 0 for first.
     * @return \stdClass
     */
    public static function numerical_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $definition,
        int $afterpageid = 0
    ): \stdClass {
        return self::open_question_page_properties(
            $lesson,
            $title,
            $content,
            $contentformat,
            self::NUMERICAL_PAGE_TYPE,
            $definition['answers'] ?? [],
            $afterpageid
        );
    }

    /**
     * Build Moodle Lesson page properties for an essay question page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $definition Normalized essay definition.
     * @param int $afterpageid Previous page id or 0 for first.
     * @return \stdClass
     */
    public static function essay_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $definition,
        int $afterpageid = 0
    ): \stdClass {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title must be non-empty.');
        }

        return (object) [
            'title' => $title,
            'contents_editor' => [
                'text' => $content,
                'format' => $contentformat,
            ],
            'qtype' => self::ESSAY_PAGE_TYPE,
            'pageid' => max(0, $afterpageid),
            'jumpto' => [
                (int) ($definition['jump_to'] ?? -1),
            ],
            'score' => [
                (float) ($definition['score'] ?? 1),
            ],
        ];
    }

    /**
     * Build Moodle Lesson page properties for a matching question page.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param array $definition Normalized matching definition.
     * @param int $afterpageid Previous page id or 0 for first.
     * @return \stdClass
     */
    public static function matching_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        array $definition,
        int $afterpageid = 0
    ): \stdClass {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title must be non-empty.');
        }

        $pairs = $definition['pairs'] ?? [];
        if (count($pairs) < 2) {
            throw new \invalid_parameter_exception('matching pages require at least two pairs.');
        }
        if (count($pairs) > (int) $lesson->maxanswers) {
            throw new \invalid_parameter_exception('matching pair count must not exceed the Lesson max_answers setting.');
        }

        $properties = (object) [
            'title' => $title,
            'contents_editor' => [
                'text' => $content,
                'format' => $contentformat,
            ],
            'qtype' => self::MATCHING_PAGE_TYPE,
            'pageid' => max(0, $afterpageid),
            'answer_editor' => [
                [
                    'text' => (string) ($definition['correct_response'] ?? ''),
                    'format' => (int) ($definition['correct_response_format'] ?? FORMAT_HTML),
                ],
                [
                    'text' => (string) ($definition['wrong_response'] ?? ''),
                    'format' => (int) ($definition['wrong_response_format'] ?? FORMAT_HTML),
                ],
            ],
            'response_editor' => ['', ''],
            'jumpto' => [
                (int) ($definition['correct_jump_to'] ?? -1),
                (int) ($definition['wrong_jump_to'] ?? 0),
            ],
            'score' => [
                (float) ($definition['correct_score'] ?? 1),
                (float) ($definition['wrong_score'] ?? 0),
            ],
        ];

        foreach ($pairs as $pair) {
            $properties->answer_editor[] = [
                'text' => $pair['prompt'],
                'format' => $pair['prompt_format'],
            ];
            $properties->response_editor[] = $pair['match'];
        }
        while (count($properties->answer_editor) < (int) $lesson->maxanswers + 2) {
            $properties->answer_editor[] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->response_editor[] = '';
        }

        return $properties;
    }

    /**
     * Build Moodle Lesson page properties for open-answer question pages.
     *
     * @param \lesson $lesson Lesson domain object.
     * @param string $title Page title.
     * @param string $content Page content.
     * @param int $contentformat Content format.
     * @param int $qtype Moodle Lesson question type.
     * @param array $answers Normalized answers.
     * @param int $afterpageid Previous page id or 0 for first.
     * @param bool $questionoption Optional question option flag.
     * @return \stdClass
     */
    private static function open_question_page_properties(
        \lesson $lesson,
        string $title,
        string $content,
        int $contentformat,
        int $qtype,
        array $answers,
        int $afterpageid = 0,
        bool $questionoption = false
    ): \stdClass {
        $title = trim($title);
        if ($title === '') {
            throw new \invalid_parameter_exception('title must be non-empty.');
        }
        if (count($answers) < 1) {
            throw new \invalid_parameter_exception('open-answer Lesson pages require at least one answer.');
        }
        if (count($answers) > (int) $lesson->maxanswers) {
            throw new \invalid_parameter_exception('answer count must not exceed the Lesson max_answers setting.');
        }

        $properties = (object) [
            'title' => $title,
            'contents_editor' => [
                'text' => $content,
                'format' => $contentformat,
            ],
            'qtype' => $qtype,
            'pageid' => max(0, $afterpageid),
            'answer_editor' => [],
            'response_editor' => [],
            'jumpto' => [],
            'score' => [],
        ];

        if ($questionoption) {
            $properties->qoption = 1;
        }

        foreach ($answers as $index => $answer) {
            $properties->answer_editor[$index] = [
                'text' => $answer['answer'],
                'format' => $answer['answer_format'],
            ];
            $properties->response_editor[$index] = [
                'text' => $answer['response'],
                'format' => $answer['response_format'],
            ];
            $properties->jumpto[$index] = $answer['jump_to'];
            $properties->score[$index] = $answer['score'];
        }
        for ($index = count($answers); $index < (int) $lesson->maxanswers; $index++) {
            $properties->answer_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->response_editor[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $properties->jumpto[$index] = 0;
            $properties->score[$index] = 0;
        }

        return $properties;
    }

    /**
     * Return current content-page branches for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function branches_from_page(\lesson_page $page): array {
        $branches = [];
        foreach ($page->get_answers() as $answer) {
            $title = (string) ($answer->answer ?? '');
            if ($title === '') {
                continue;
            }
            $branches[] = [
                'title' => $title,
                'response' => (string) ($answer->response ?? ''),
                'jump_to' => (int) ($answer->jumpto ?? 0),
                'score' => (float) ($answer->score ?? 0),
            ];
        }

        return $branches;
    }

    /**
     * Return current true/false answers for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function truefalse_answers_from_page(\lesson_page $page): array {
        $answers = [];
        foreach ($page->get_answers() as $answer) {
            $text = (string) ($answer->answer ?? '');
            if ($text === '') {
                continue;
            }
            $answers[] = [
                'answer' => $text,
                'answer_format' => (int) ($answer->answerformat ?? FORMAT_HTML),
                'response' => (string) ($answer->response ?? ''),
                'response_format' => (int) ($answer->responseformat ?? FORMAT_HTML),
                'jump_to' => (int) ($answer->jumpto ?? 0),
                'score' => (float) ($answer->score ?? 0),
            ];
        }

        if (count($answers) !== 2) {
            throw new \invalid_parameter_exception('Existing truefalse page must contain exactly two answers before update.');
        }

        return $answers;
    }

    /**
     * Return current multichoice answers for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function multichoice_answers_from_page(\lesson_page $page): array {
        $properties = $page->properties();
        $answers = [];
        foreach ($page->get_answers() as $answer) {
            $text = (string) ($answer->answer ?? '');
            if ($text === '') {
                continue;
            }
            $answers[] = [
                'answer' => $text,
                'answer_format' => (int) ($answer->answerformat ?? FORMAT_HTML),
                'response' => (string) ($answer->response ?? ''),
                'response_format' => (int) ($answer->responseformat ?? FORMAT_HTML),
                'jump_to' => (int) ($answer->jumpto ?? 0),
                'score' => (float) ($answer->score ?? 0),
            ];
        }

        if (count($answers) < 2) {
            throw new \invalid_parameter_exception('Existing multichoice page must contain at least two answers before update.');
        }

        return [
            'multi_answer' => (int) ($properties->qoption ?? 0) === 1,
            'answers' => $answers,
        ];
    }

    /**
     * Return current short-answer answers for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function shortanswer_answers_from_page(\lesson_page $page): array {
        $properties = $page->properties();

        return [
            'use_regular_expressions' => (int) ($properties->qoption ?? 0) === 1,
            'answers' => self::open_question_answers_from_page($page, 'shortanswer'),
        ];
    }

    /**
     * Return current numerical answers for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function numerical_answers_from_page(\lesson_page $page): array {
        return [
            'answers' => self::open_question_answers_from_page($page, 'numerical'),
        ];
    }

    /**
     * Return current essay settings for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function essay_answers_from_page(\lesson_page $page): array {
        $answers = array_values($page->get_answers());
        $answer = $answers[0] ?? null;
        if (!$answer) {
            throw new \invalid_parameter_exception('Existing essay page must contain its grading answer before update.');
        }

        return [
            'jump_to' => (int) ($answer->jumpto ?? -1),
            'score' => (float) ($answer->score ?? 1),
        ];
    }

    /**
     * Return current matching settings for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @return array
     */
    public static function matching_answers_from_page(\lesson_page $page): array {
        $answers = array_values($page->get_answers());
        if (count($answers) < 4) {
            throw new \invalid_parameter_exception('Existing matching page must contain two responses and at least two pairs.');
        }

        $correct = $answers[0];
        $wrong = $answers[1];
        $pairs = [];
        foreach (array_slice($answers, 2) as $answer) {
            $prompt = (string) ($answer->answer ?? '');
            $match = (string) ($answer->response ?? '');
            if ($prompt === '' && $match === '') {
                continue;
            }
            $pairs[] = [
                'prompt' => $prompt,
                'prompt_format' => (int) ($answer->answerformat ?? FORMAT_HTML),
                'match' => $match,
            ];
        }
        if (count($pairs) < 2) {
            throw new \invalid_parameter_exception('Existing matching page must contain at least two complete pairs.');
        }

        return [
            'correct_response' => (string) ($correct->answer ?? ''),
            'correct_response_format' => (int) ($correct->answerformat ?? FORMAT_HTML),
            'correct_jump_to' => (int) ($correct->jumpto ?? -1),
            'correct_score' => (float) ($correct->score ?? 1),
            'wrong_response' => (string) ($wrong->answer ?? ''),
            'wrong_response_format' => (int) ($wrong->answerformat ?? FORMAT_HTML),
            'wrong_jump_to' => (int) ($wrong->jumpto ?? 0),
            'wrong_score' => (float) ($wrong->score ?? 0),
            'pairs' => $pairs,
        ];
    }

    /**
     * Return current open-answer rows for update preservation.
     *
     * @param \lesson_page $page Lesson page object.
     * @param string $label Page-type label.
     * @return array
     */
    private static function open_question_answers_from_page(\lesson_page $page, string $label): array {
        $answers = [];
        foreach ($page->get_answers() as $answer) {
            $text = (string) ($answer->answer ?? '');
            if ($text === '') {
                continue;
            }
            $answers[] = [
                'answer' => $text,
                'answer_format' => (int) ($answer->answerformat ?? FORMAT_HTML),
                'response' => (string) ($answer->response ?? ''),
                'response_format' => (int) ($answer->responseformat ?? FORMAT_HTML),
                'jump_to' => (int) ($answer->jumpto ?? 0),
                'score' => (float) ($answer->score ?? 0),
            ];
        }

        if (count($answers) < 1) {
            throw new \invalid_parameter_exception('Existing ' . $label . ' page must contain at least one answer before update.');
        }

        return $answers;
    }

    /**
     * Normalize a Lesson jump target.
     *
     * @param mixed $value Raw jump value.
     * @param string $label Parameter label.
     * @return int
     */
    private static function normalise_jump($value, string $label = 'branch jump_to'): int {
        if (is_string($value)) {
            $map = [
                'this_page' => 0,
                'next_page' => -1,
                'previous_page' => -40,
                'end_of_lesson' => -9,
            ];
            $key = strtolower(trim($value));
            if (array_key_exists($key, $map)) {
                return $map[$key];
            }
        }

        if (!is_numeric($value)) {
            throw new \invalid_parameter_exception($label . ' must be an integer or supported jump name.');
        }

        return (int) $value;
    }

    /**
     * Normalize a Moodle text format.
     *
     * @param mixed $value Raw text format.
     * @param string $label Parameter label.
     * @return int
     */
    private static function normalise_text_format($value, string $label): int {
        if (!is_numeric($value)) {
            throw new \invalid_parameter_exception($label . ' must be an integer.');
        }

        $format = (int) $value;
        if ($format < 0) {
            throw new \invalid_parameter_exception($label . ' must be zero or greater.');
        }

        return $format;
    }

    /**
     * Normalize a Lesson answer score.
     *
     * @param mixed $value Raw score.
     * @return float
     */
    private static function normalise_score($value): float {
        if (!is_numeric($value)) {
            throw new \invalid_parameter_exception('answer score must be numeric.');
        }

        return (float) $value;
    }

    /**
     * Return whether an array has consecutive integer keys.
     *
     * @param array $value Array to inspect.
     * @return bool
     */
    private static function is_list_array(array $value): bool {
        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Return a canonical Lesson access information response.
     *
     * @param \cm_info $cm Lesson course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function access_information_to_response(\cm_info $cm, array $result): array {
        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'can_manage' => (bool) ($result['canmanage'] ?? false),
            'can_grade' => (bool) ($result['cangrade'] ?? false),
            'can_view_reports' => (bool) ($result['canviewreports'] ?? false),
            'review_mode' => (bool) ($result['reviewmode'] ?? false),
            'attempts_count' => (int) ($result['attemptscount'] ?? 0),
            'last_page_seen' => (int) ($result['lastpageseen'] ?? 0),
            'left_during_timed_session' => (bool) ($result['leftduringtimedsession'] ?? false),
            'first_page_id' => (int) ($result['firstpageid'] ?? 0),
            'prevent_access_reasons' => self::prevent_access_reasons_to_response($result['preventaccessreasons'] ?? []),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical Lesson pages response.
     *
     * @param \cm_info $cm Lesson course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function pages_to_response(\cm_info $cm, array $result): array {
        $pages = [];
        foreach (($result['pages'] ?? []) as $pageentry) {
            $item = (array) $pageentry;
            $page = (array) ($item['page'] ?? []);
            $pages[] = [
                'page_id' => (int) ($page['id'] ?? 0),
                'lesson_id' => (int) ($page['lessonid'] ?? $cm->instance),
                'module_id' => (int) $cm->id,
                'previous_page_id' => (int) ($page['prevpageid'] ?? 0),
                'next_page_id' => (int) ($page['nextpageid'] ?? 0),
                'question_type' => (int) ($page['qtype'] ?? 0),
                'question_option' => (int) ($page['qoption'] ?? 0),
                'layout' => (int) ($page['layout'] ?? 0),
                'display' => (int) ($page['display'] ?? 0),
                'display_in_menu_block' => (bool) ($page['displayinmenublock'] ?? false),
                'type' => (int) ($page['type'] ?? 0),
                'type_id' => (int) ($page['typeid'] ?? 0),
                'type_string' => (string) ($page['typestring'] ?? ''),
                'title' => (string) ($page['title'] ?? ''),
                'content' => (string) ($page['contents'] ?? ''),
                'content_format' => (int) ($page['contentsformat'] ?? FORMAT_HTML),
                'time_created' => (int) ($page['timecreated'] ?? 0),
                'time_modified' => (int) ($page['timemodified'] ?? 0),
                'answer_ids' => array_map('intval', $item['answerids'] ?? []),
                'jumps' => array_map('intval', $item['jumps'] ?? []),
                'files_count' => (int) ($item['filescount'] ?? 0),
                'files_size_total' => (int) ($item['filessizetotal'] ?? 0),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'count' => count($pages),
            'pages' => $pages,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical Lesson details response.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Lesson course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function lesson_details_to_response(\stdClass $course, \cm_info $cm, array $result): array {
        $lesson = (array) ($result['lesson'] ?? []);

        return [
            'lesson' => self::lesson_summary_to_response($course, $cm, $lesson),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical course Lesson listing response.
     *
     * @param \stdClass $course Moodle course.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function course_lessons_to_response(\stdClass $course, array $result): array {
        $lessons = [];
        foreach (($result['lessons'] ?? []) as $lessonentry) {
            $lessons[] = self::lesson_summary_to_response($course, null, (array) $lessonentry);
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($lessons),
            'lessons' => $lessons,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical Lesson possible jumps response.
     *
     * @param \cm_info $cm Lesson course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function possible_jumps_to_response(\cm_info $cm, array $result): array {
        $jumps = [];
        foreach (($result['jumps'] ?? []) as $jumpentry) {
            $jump = (array) $jumpentry;
            $jumps[] = [
                'page_id' => (int) ($jump['pageid'] ?? 0),
                'answer_id' => (int) ($jump['answerid'] ?? 0),
                'jump_to' => (int) ($jump['jumpto'] ?? 0),
                'calculated_jump' => (int) ($jump['calculatedjump'] ?? 0),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'count' => count($jumps),
            'jumps' => $jumps,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical Lesson summary.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info|null $cm Optional Lesson course module.
     * @param array $lesson Moodle Lesson summary exporter data.
     * @return array
     */
    public static function lesson_summary_to_response(\stdClass $course, ?\cm_info $cm, array $lesson): array {
        $introfiles = $lesson['introfiles'] ?? [];
        $mediafiles = $lesson['mediafiles'] ?? [];

        return [
            'module_id' => (int) ($lesson['coursemodule'] ?? ($cm ? $cm->id : 0)),
            'lesson_id' => (int) ($lesson['id'] ?? ($cm ? $cm->instance : 0)),
            'course_id' => (int) ($lesson['course'] ?? $course->id),
            'name' => (string) ($lesson['name'] ?? ''),
            'intro' => (string) ($lesson['intro'] ?? ''),
            'intro_format' => (int) ($lesson['introformat'] ?? FORMAT_MOODLE),
            'language' => (string) ($lesson['lang'] ?? ''),
            'grade' => (int) ($lesson['grade'] ?? 0),
            'practice' => (bool) ($lesson['practice'] ?? false),
            'allow_review' => (bool) ($lesson['modattempts'] ?? false),
            'use_password' => (bool) ($lesson['usepassword'] ?? false),
            'custom_scoring' => (bool) ($lesson['custom'] ?? false),
            'ongoing_score' => (bool) ($lesson['ongoing'] ?? false),
            'use_max_grade' => (bool) ($lesson['usemaxgrade'] ?? false),
            'max_answers' => (int) ($lesson['maxanswers'] ?? 0),
            'max_attempts' => (int) ($lesson['maxattempts'] ?? 0),
            'allow_question_retry' => (bool) ($lesson['review'] ?? false),
            'after_correct_answer' => (int) ($lesson['nextpagedefault'] ?? 0),
            'default_feedback' => (bool) ($lesson['feedback'] ?? false),
            'minimum_questions' => (int) ($lesson['minquestions'] ?? 0),
            'pages_to_show' => (int) ($lesson['maxpages'] ?? 0),
            'time_limit_seconds' => (int) ($lesson['timelimit'] ?? 0),
            'retakes_allowed' => (bool) ($lesson['retake'] ?? false),
            'activity_link' => (int) ($lesson['activitylink'] ?? 0),
            'slideshow' => (bool) ($lesson['slideshow'] ?? false),
            'slideshow_width' => (int) ($lesson['width'] ?? 0),
            'slideshow_height' => (int) ($lesson['height'] ?? 0),
            'slideshow_background' => (string) ($lesson['bgcolor'] ?? ''),
            'display_left_menu' => (bool) ($lesson['displayleft'] ?? false),
            'display_left_if' => (int) ($lesson['displayleftif'] ?? 0),
            'progress_bar' => (bool) ($lesson['progressbar'] ?? false),
            'available_from' => (int) ($lesson['available'] ?? 0),
            'deadline' => (int) ($lesson['deadline'] ?? 0),
            'time_modified' => (int) ($lesson['timemodified'] ?? 0),
            'completion_end_reached' => (bool) ($lesson['completionendreached'] ?? false),
            'completion_time_spent_seconds' => (int) ($lesson['completiontimespent'] ?? 0),
            'allow_offline_attempts' => (bool) ($lesson['allowofflineattempts'] ?? false),
            'intro_files_count' => is_array($introfiles) ? count($introfiles) : 0,
            'media_files_count' => is_array($mediafiles) ? count($mediafiles) : 0,
        ];
    }

    /**
     * Return a canonical Lesson user grade response.
     *
     * @param \cm_info $cm Lesson course module.
     * @param int $userid Moodle user id, or 0 when Moodle used the current user.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function user_grade_to_response(\cm_info $cm, int $userid, array $result): array {
        $hasgrade = $result['grade'] !== null && $result['grade'] !== '';
        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'user_id' => max(0, $userid),
            'has_grade' => $hasgrade,
            'grade' => $hasgrade ? (float) $result['grade'] : 0.0,
            'formatted_grade' => (string) ($result['formattedgrade'] ?? ''),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical Lesson user timers response.
     *
     * @param \cm_info $cm Lesson course module.
     * @param int $userid Moodle user id, or 0 when Moodle used the current user.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function user_timers_to_response(\cm_info $cm, int $userid, array $result): array {
        $timers = [];
        foreach (($result['timers'] ?? []) as $timer) {
            $item = (array) $timer;
            $timers[] = [
                'timer_id' => (int) ($item['id'] ?? 0),
                'lesson_id' => (int) ($item['lessonid'] ?? $cm->instance),
                'module_id' => (int) $cm->id,
                'user_id' => (int) ($item['userid'] ?? $userid),
                'start_time' => (int) ($item['starttime'] ?? 0),
                'lesson_time' => (int) ($item['lessontime'] ?? 0),
                'completed' => (bool) ($item['completed'] ?? false),
                'time_modified_offline' => (int) ($item['timemodifiedoffline'] ?? 0),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'user_id' => max(0, $userid),
            'count' => count($timers),
            'timers' => $timers,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical Lesson attempts overview response.
     *
     * @param \cm_info $cm Lesson course module.
     * @param int $groupid Moodle group id.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function attempts_overview_to_response(\cm_info $cm, int $groupid, array $result): array {
        $data = (array) ($result['data'] ?? []);
        $students = [];
        foreach (($data['students'] ?? []) as $student) {
            $item = (array) $student;
            $attempts = [];
            foreach (($item['attempts'] ?? []) as $attempt) {
                $attemptitem = (array) $attempt;
                $attempts[] = [
                    'attempt_number' => (int) ($attemptitem['try'] ?? 0),
                    'grade' => (float) ($attemptitem['grade'] ?? 0),
                    'time_start' => (int) ($attemptitem['timestart'] ?? 0),
                    'time_end' => (int) ($attemptitem['timeend'] ?? 0),
                    'end_time' => (int) ($attemptitem['end'] ?? 0),
                ];
            }
            $students[] = [
                'user_id' => (int) ($item['id'] ?? 0),
                'full_name' => (string) ($item['fullname'] ?? ''),
                'best_grade' => (float) ($item['bestgrade'] ?? 0),
                'attempts' => $attempts,
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'lesson_id' => (int) $cm->instance,
            'group_id' => max(0, $groupid),
            'lesson_scored' => (bool) ($data['lessonscored'] ?? false),
            'attempts_count' => (int) ($data['numofattempts'] ?? 0),
            'average_score' => (float) ($data['avescore'] ?? 0),
            'high_score' => (float) ($data['highscore'] ?? 0),
            'low_score' => (float) ($data['lowscore'] ?? 0),
            'average_time' => (int) ($data['avetime'] ?? 0),
            'high_time' => (int) ($data['hightime'] ?? 0),
            'low_time' => (int) ($data['lowtime'] ?? 0),
            'students' => $students,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Normalize Moodle warning payloads.
     *
     * @param array $warnings Moodle warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $mapped = [];
        foreach ($warnings as $warning) {
            $item = is_array($warning) ? $warning : (array) $warning;
            $mapped[] = [
                'item' => (string) ($item['item'] ?? ''),
                'item_id' => (int) ($item['itemid'] ?? 0),
                'warning_code' => (string) ($item['warningcode'] ?? ''),
                'message' => (string) ($item['message'] ?? ''),
            ];
        }

        return $mapped;
    }

    /**
     * Normalize Lesson prevent-access reasons.
     *
     * @param array $reasons Moodle prevent-access reasons.
     * @return array
     */
    private static function prevent_access_reasons_to_response(array $reasons): array {
        $mapped = [];
        foreach ($reasons as $reason) {
            $item = is_array($reason) ? $reason : (array) $reason;
            $data = $item['data'] ?? '';
            $encodeddata = json_encode($data, JSON_UNESCAPED_SLASHES);
            $mapped[] = [
                'reason' => (string) ($item['reason'] ?? ''),
                'data' => is_scalar($data) || $data === null ? (string) $data : ($encodeddata === false ? '' : $encodeddata),
                'message' => (string) ($item['message'] ?? ''),
            ];
        }

        return $mapped;
    }
}
