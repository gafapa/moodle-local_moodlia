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
 * Shared question helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

use core_question\category_manager;
use core_question\local\bank\question_version_status;
use mod_quiz\quiz_settings;
use qbank_managecategories\helper as category_helper;

/**
 * Helper methods for question bank and quiz operations.
 */
class question_tools {
    /**
     * BANK SCOPE COURSE SHARED.
     */
    public const BANK_SCOPE_COURSE_SHARED = 'course_shared';
    /**
     * BANK SCOPE QUIZ PRIVATE.
     */
    public const BANK_SCOPE_QUIZ_PRIVATE = 'quiz_private';

    /**
     * Load Moodle question APIs.
     */
    public static function require_question_api(): void {
        global $CFG;

        module_tools::require_module_api();
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/engine/bank.php');
        require_once($CFG->dirroot . '/mod/qbank/lib.php');
    }

    /**
     * Load Moodle quiz APIs.
     */
    public static function require_quiz_api(): void {
        global $CFG;

        self::require_question_api();
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/classes/external.php');
    }

    /**
     * Return a question category manager.
     *
     * @return category_manager
     */
    public static function category_manager(): category_manager {
        self::require_question_api();

        return new category_manager();
    }

    /**
     * Return the default question category for a course.
     *
     * @param int $courseid Courseid.
     * @return \stdClass
     */
    public static function get_course_default_category(int $courseid): \stdClass {
        self::require_question_api();

        $course = course_tools::get_course($courseid);
        $cmid = self::get_or_create_course_qbank_module($course);
        $context = \context_module::instance($cmid);

        $category = question_get_default_category($context->id, true);
        if (!$category) {
            throw new \moodle_exception('Could not resolve a default question category for the course question bank.');
        }

        return $category;
    }

    /**
     * Return the default question category for a resolved bank location.
     *
     * @param int $courseid Courseid.
     * @param string|null $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @return \stdClass
     */
    public static function get_default_category_for_bank(
        int $courseid,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null
    ): \stdClass {
        self::require_question_api();

        $location = self::resolve_question_bank_location($courseid, $bankscope, $questionbankmoduleid, $quizmoduleid);
        $category = question_get_default_category($location['context']->id, true);
        if (!$category) {
            throw new \moodle_exception('Could not resolve a default question category for the selected question bank.');
        }

        return $category;
    }

    /**
     * Resolve the Moodle context that owns a question bank.
     *
     * @param int $courseid Courseid.
     * @param string|null $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @return array
     */
    public static function resolve_question_bank_location(
        int $courseid,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null
    ): array {
        self::require_question_api();

        $course = course_tools::get_course($courseid);
        $scope = clean_param($bankscope ?: self::BANK_SCOPE_COURSE_SHARED, PARAM_ALPHANUMEXT);
        if (!in_array($scope, [self::BANK_SCOPE_COURSE_SHARED, self::BANK_SCOPE_QUIZ_PRIVATE], true)) {
            throw new \invalid_parameter_exception('bank_scope must be one of: course_shared, quiz_private.');
        }

        if ($scope === self::BANK_SCOPE_COURSE_SHARED) {
            if ($quizmoduleid !== null) {
                throw new \invalid_parameter_exception('quiz_module_id is only valid when bank_scope=quiz_private.');
            }

            $cmid = $questionbankmoduleid ?: self::get_or_create_course_qbank_module($course);
            $cm = module_tools::get_course_module($course, $cmid);
            if ($cm->modname !== 'qbank') {
                throw new \invalid_parameter_exception('question_bank_module_id must reference a question bank activity.');
            }

            return [
                'bank_scope' => self::BANK_SCOPE_COURSE_SHARED,
                'context' => \context_module::instance($cm->id),
                'question_bank_module_id' => (int) $cm->id,
                'quiz_module_id' => null,
            ];
        }

        if ($questionbankmoduleid !== null) {
            throw new \invalid_parameter_exception('question_bank_module_id is only valid when bank_scope=course_shared.');
        }

        if ($quizmoduleid === null || $quizmoduleid <= 0) {
            throw new \invalid_parameter_exception('quiz_module_id is required when bank_scope=quiz_private.');
        }

        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        return [
            'bank_scope' => self::BANK_SCOPE_QUIZ_PRIVATE,
            'context' => \context_module::instance($cm->id),
            'question_bank_module_id' => null,
            'quiz_module_id' => (int) $cm->id,
        ];
    }

    /**
     * Resolve an existing Moodle context that owns a question bank without creating modules.
     *
     * @param int $courseid Courseid.
     * @param string|null $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @return array|null
     */
    public static function resolve_existing_question_bank_location(
        int $courseid,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null
    ): ?array {
        self::require_question_api();

        $course = course_tools::get_course($courseid);
        $scope = clean_param($bankscope ?: self::BANK_SCOPE_COURSE_SHARED, PARAM_ALPHANUMEXT);
        if (!in_array($scope, [self::BANK_SCOPE_COURSE_SHARED, self::BANK_SCOPE_QUIZ_PRIVATE], true)) {
            throw new \invalid_parameter_exception('bank_scope must be one of: course_shared, quiz_private.');
        }

        if ($scope === self::BANK_SCOPE_COURSE_SHARED) {
            if ($quizmoduleid !== null) {
                throw new \invalid_parameter_exception('quiz_module_id is only valid when bank_scope=quiz_private.');
            }

            if ($questionbankmoduleid !== null) {
                $cm = module_tools::get_course_module($course, $questionbankmoduleid);
                if ($cm->modname !== 'qbank') {
                    throw new \invalid_parameter_exception('question_bank_module_id must reference a question bank activity.');
                }

                return [
                    'bank_scope' => self::BANK_SCOPE_COURSE_SHARED,
                    'context' => \context_module::instance($cm->id),
                    'question_bank_module_id' => (int) $cm->id,
                    'quiz_module_id' => null,
                ];
            }

            $modinfo = get_fast_modinfo($course);
            $qbanks = $modinfo->get_instances_of('qbank');
            if (empty($qbanks)) {
                return null;
            }

            $first = reset($qbanks);
            return [
                'bank_scope' => self::BANK_SCOPE_COURSE_SHARED,
                'context' => \context_module::instance($first->id),
                'question_bank_module_id' => (int) $first->id,
                'quiz_module_id' => null,
            ];
        }

        if ($questionbankmoduleid !== null) {
            throw new \invalid_parameter_exception('question_bank_module_id is only valid when bank_scope=course_shared.');
        }

        if ($quizmoduleid === null || $quizmoduleid <= 0) {
            throw new \invalid_parameter_exception('quiz_module_id is required when bank_scope=quiz_private.');
        }

        $cm = module_tools::get_quiz_module($course, $quizmoduleid);

        return [
            'bank_scope' => self::BANK_SCOPE_QUIZ_PRIVATE,
            'context' => \context_module::instance($cm->id),
            'question_bank_module_id' => null,
            'quiz_module_id' => (int) $cm->id,
        ];
    }

    /**
     * Return question banks visible inside a course.
     *
     * @param int $courseid Courseid.
     * @param bool $includequizprivate Includequizprivate.
     * @return array
     */
    public static function get_question_banks(int $courseid, bool $includequizprivate = true): array {
        self::require_question_api();

        $course = course_tools::get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $banks = [];

        foreach ($modinfo->get_instances_of('qbank') as $cm) {
            $context = \context_module::instance($cm->id);
            $banks[] = [
                'bank_scope' => self::BANK_SCOPE_COURSE_SHARED,
                'module_id' => (int) $cm->id,
                'question_bank_module_id' => (int) $cm->id,
                'quiz_module_id' => null,
                'name' => format_string($cm->name, true, ['context' => $context]),
                'context_id' => (int) $context->id,
                'visible' => (bool) $cm->visible,
                'url' => (new \moodle_url('/question/edit.php', ['cmid' => $cm->id]))->out(false),
            ];
        }

        if ($includequizprivate) {
            foreach ($modinfo->get_instances_of('quiz') as $cm) {
                $context = \context_module::instance($cm->id);
                $banks[] = [
                    'bank_scope' => self::BANK_SCOPE_QUIZ_PRIVATE,
                    'module_id' => (int) $cm->id,
                    'question_bank_module_id' => null,
                    'quiz_module_id' => (int) $cm->id,
                    'name' => format_string($cm->name, true, ['context' => $context]),
                    'context_id' => (int) $context->id,
                    'visible' => (bool) $cm->visible,
                    'url' => (new \moodle_url('/question/edit.php', ['cmid' => $cm->id]))->out(false),
                ];
            }
        }

        return $banks;
    }

    /**
     * Return question categories in a resolved question bank.
     *
     * @param int $courseid Courseid.
     * @param string|null $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @param bool $includetop Includetop.
     * @return array
     */
    public static function get_question_categories(
        int $courseid,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        bool $includetop = false
    ): array {
        self::require_question_api();

        $location = self::resolve_existing_question_bank_location(
            $courseid,
            $bankscope,
            $questionbankmoduleid,
            $quizmoduleid
        );
        if ($location === null) {
            return [];
        }

        $context = $location['context'];
        $categories = category_helper::get_categories_for_contexts((string) $context->id, 'parent, sortorder, name ASC', $includetop);
        $responses = [];

        foreach ($categories as $category) {
            $responses[] = [
                'category_id' => (int) $category->id,
                'name' => format_string($category->name, true, ['context' => $context]),
                'context_id' => (int) $context->id,
                'parent_id' => (int) ($category->parent ?? 0),
                'question_count' => (int) ($category->questioncount ?? 0),
                'is_top' => empty($category->parent),
                'bank_scope' => (string) $location['bank_scope'],
                'question_bank_module_id' => $location['question_bank_module_id'],
                'quiz_module_id' => $location['quiz_module_id'],
                'url' => (new \moodle_url('/question/edit.php', [
                    'cmid' => $location['question_bank_module_id'] ?: $location['quiz_module_id'],
                    'category' => ((int) $category->id) . ',' . ((int) $context->id),
                ]))->out(false),
            ];
        }

        return $responses;
    }

    /**
     * Get or create the course question bank module used to own question categories.
     *
     * @param \stdClass $course Course.
     * @return int Course module id.
     */
    public static function get_or_create_course_qbank_module(\stdClass $course): int {
        self::require_question_api();

        $modinfo = get_fast_modinfo($course);
        $qbanks = $modinfo->get_instances_of('qbank');
        if (!empty($qbanks)) {
            $first = reset($qbanks);
            return (int) $first->id;
        }

        require_capability('moodle/course:manageactivities', \context_course::instance($course->id));

        $prepared = prepare_new_moduleinfo_data($course, 'qbank', 0);
        $moduleinfo = $prepared[4];
        $moduleinfo->name = 'MoodlIA Question Bank';
        $moduleinfo->intro = '';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->showdescription = 0;

        $created = add_moduleinfo($moduleinfo, $course);
        rebuild_course_cache($course->id, true);

        return (int) $created->coursemodule;
    }

    /**
     * Return the canonical question category response shape.
     *
     * @param int $categoryid Categoryid.
     * @param string $name Name.
     * @param int $contextid Contextid.
     * @param array|null $location Location.
     * @return array
     */
    public static function category_to_response(
        int $categoryid,
        string $name,
        int $contextid,
        ?array $location = null
    ): array {
        $location = $location ?? [
            'bank_scope' => self::BANK_SCOPE_COURSE_SHARED,
            'question_bank_module_id' => null,
            'quiz_module_id' => null,
        ];

        return [
            'category_id' => $categoryid,
            'name' => format_string($name, true, ['context' => \context::instance_by_id($contextid)]),
            'context_id' => $contextid,
            'bank_scope' => (string) $location['bank_scope'],
            'question_bank_module_id' => $location['question_bank_module_id'],
            'quiz_module_id' => $location['quiz_module_id'],
        ];
    }

    /**
     * Decode JSON object parameters passed through Moodle REST.
     *
     * @param string $json Json.
     * @return array
     */
    public static function decode_options(string $json): array {
        return module_tools::decode_options($json);
    }

    /**
     * Build the form object expected by Moodle question types.
     *
     * @param int $categoryid Categoryid.
     * @param string $questiontype Questiontype.
     * @param string $name Name.
     * @param string $questiontext Questiontext.
     * @param array $options Options.
     * @return \stdClass
     */
    public static function build_question_form(
        int $categoryid,
        string $questiontype,
        string $name,
        string $questiontext,
        array $options
    ): \stdClass {
        self::require_question_api();

        $questiontype = self::to_moodle_question_type(clean_param($questiontype, PARAM_PLUGIN));
        $supportedtypes = [
            'truefalse',
            'shortanswer',
            'multichoice',
            'numerical',
            'essay',
            'match',
            'description',
            'randomsamatch',
            'gapselect',
            'ddwtos',
            'ordering',
            'multianswer',
            'ddmarker',
            'ddimageortext',
            'calculatedsimple',
            'calculated',
            'calculatedmulti',
        ];
        if (!in_array($questiontype, $supportedtypes, true)) {
            throw new \invalid_parameter_exception(
                'Only these question_type values are currently supported: ' .
                    implode(', ', $supportedtypes) . '.'
            );
        }

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        $form = (object) [
            'category' => (string) $categoryid,
            'name' => $name,
            'questiontext' => [
                'text' => $questiontext,
                'format' => FORMAT_HTML,
            ],
            'generalfeedback' => [
                'text' => (string) ($options['general_feedback'] ?? ''),
                'format' => FORMAT_HTML,
            ],
            'defaultmark' => (float) ($options['default_mark'] ?? 1),
            'penalty' => (float) ($options['penalty'] ?? 0.3333333),
            'status' => question_version_status::QUESTION_STATUS_READY,
            'idnumber' => null,
            'hint' => [],
            'hintclearwrong' => [],
            'hintshownumcorrect' => [],
        ];

        if ($questiontype === 'truefalse') {
            self::apply_truefalse_options($form, $options);
        } else if ($questiontype === 'shortanswer') {
            self::apply_shortanswer_options($form, $options);
        } else if ($questiontype === 'multichoice') {
            self::apply_multichoice_options($form, $options);
        } else if ($questiontype === 'numerical') {
            self::apply_numerical_options($form, $options);
        } else if ($questiontype === 'match') {
            self::apply_matching_options($form, $options);
        } else if ($questiontype === 'essay') {
            self::apply_essay_options($form, $options);
        } else if ($questiontype === 'description') {
            self::apply_description_options($form, $options);
        } else if ($questiontype === 'randomsamatch') {
            self::apply_randomsamatch_options($form, $options, $categoryid);
        } else if ($questiontype === 'gapselect') {
            self::apply_gapselect_options($form, $questiontext, $options);
        } else if ($questiontype === 'ddwtos') {
            self::apply_ddwtos_options($form, $questiontext, $options);
        } else if ($questiontype === 'ordering') {
            self::apply_ordering_options($form, $options);
        } else if ($questiontype === 'multianswer') {
            self::apply_multianswer_options($form);
        } else if ($questiontype === 'ddmarker') {
            self::apply_ddmarker_options($form, $options);
        } else if ($questiontype === 'ddimageortext') {
            self::apply_ddimageortext_options($form, $options);
        } else if ($questiontype === 'calculatedsimple') {
            self::apply_calculated_options($form, $questiontext, $options, 'calculatedsimple');
        } else if ($questiontype === 'calculatedmulti') {
            self::apply_calculated_options($form, $questiontext, $options, 'calculatedmulti');
        } else {
            self::apply_calculated_options($form, $questiontext, $options, 'calculated');
        }

        return $form;
    }

    /**
     * Save a Moodle question through the question type API.
     *
     * @param int|null $questionid Questionid.
     * @param int $categoryid Categoryid.
     * @param string $questiontype Questiontype.
     * @param string $name Name.
     * @param string $questiontext Questiontext.
     * @param array $options Options.
     * @return array
     */
    public static function save_question(
        ?int $questionid,
        int $categoryid,
        string $questiontype,
        string $name,
        string $questiontext,
        array $options
    ): array {
        self::require_question_api();

        $moodlequestiontype = self::to_moodle_question_type($questiontype);
        $form = self::build_question_form($categoryid, $moodlequestiontype, $name, $questiontext, $options);
        $question = (object) ['qtype' => $moodlequestiontype];

        if ($questionid !== null) {
            $existing = self::get_question($questionid);
            question_require_capability_on($existing, 'edit');
            $question->id = $questionid;
            $question->qtype = $existing->qtype;
            $form = self::build_question_form($categoryid, $existing->qtype, $name, $questiontext, $options);
            if ($question->qtype === 'calculated' || $question->qtype === 'calculatedmulti') {
                $form->id = $questionid;
            }
        }

        $qtype = \question_bank::get_qtype($question->qtype);
        $saved = $qtype->save_question($question, $form);
        if ($question->qtype === 'calculated' || $question->qtype === 'calculatedmulti') {
            self::persist_calculated_datasets($qtype, $saved, $form);
        }

        return self::question_to_response($saved);
    }

    /**
     * Persist calculated-question dataset items through Moodle's qtype API.
     *
     * @param mixed $qtype Qtype.
     * @param \stdClass $question Question.
     * @param \stdClass $form Form.
     */
    private static function persist_calculated_datasets($qtype, \stdClass $question, \stdClass $form): void {
        $desiredcount = max(0, (int) ($form->selectadd ?? 0));
        if ($desiredcount === 0) {
            return;
        }

        $datasetdefs = $qtype->get_dataset_definitions((int) $question->id, []);
        if ($datasetdefs === []) {
            throw new \moodle_exception('error', 'local_moodlia', '', null, 'Calculated question datasets were not created.');
        }

        $currentcount = null;
        foreach ($datasetdefs as $datasetdef) {
            $itemcount = (int) ($datasetdef->itemcount ?? 0);
            $currentcount = $currentcount === null ? $itemcount : min($currentcount, $itemcount);
        }
        $currentcount = $currentcount ?? 0;

        if ($currentcount < $desiredcount) {
            $generateform = clone($form);
            $generateform->addbutton = 1;
            $generateform->selectadd = (string) ($desiredcount - $currentcount);
            $generateform->nextpageparam = [];
            $qtype->save_dataset_items($question, $generateform);
        }

        $datasetdefs = $qtype->get_dataset_definitions((int) $question->id, []);
        $form->itemid = [];
        $index = 1;
        foreach ($form->definition as $defid) {
            if (!isset($datasetdefs[$defid])) {
                throw new \moodle_exception('error', 'local_moodlia', '', null, 'Calculated question dataset definition is missing.');
            }

            $itemnumber = (int) ceil($index / count($datasetdefs));
            $items = $qtype->get_database_dataset_items((int) $datasetdefs[$defid]->id);
            $form->itemid[$index] = isset($items[$itemnumber]) ? (int) $items[$itemnumber]->id : 0;
            $index++;
        }

        $qtype->save_dataset_items($question, $form);
    }

    /**
     * Convert public question type names to Moodle qtype names.
     *
     * @param string $questiontype Questiontype.
     * @return string Moodle question type.
     */
    private static function to_moodle_question_type(string $questiontype): string {
        return $questiontype === 'matching' ? 'match' : $questiontype;
    }

    /**
     * Convert Moodle qtype names to public contract names.
     *
     * @param string $questiontype Questiontype.
     * @return string Public question type.
     */
    private static function to_contract_question_type(string $questiontype): string {
        return $questiontype === 'match' ? 'matching' : $questiontype;
    }

    /**
     * Load a question through Moodle question APIs.
     *
     * @param int $questionid Questionid.
     * @return \stdClass
     */
    public static function get_question(int $questionid): \stdClass {
        self::require_question_api();

        if ($questionid <= 0) {
            throw new \invalid_parameter_exception('question_id must be a positive integer.');
        }

        $questions = question_load_questions([$questionid]);
        if (empty($questions[$questionid])) {
            throw new \moodle_exception('questiondoesnotexist', 'question');
        }

        return $questions[$questionid];
    }

    /**
     * Return and validate the context that owns a question category.
     *
     * @param int $categoryid Categoryid.
     * @param int $contextid Contextid.
     * @return \context
     */
    public static function require_question_category_context(int $categoryid, int $contextid): \context {
        self::require_question_api();

        if ($categoryid <= 0) {
            throw new \invalid_parameter_exception('category_id must be a positive integer.');
        }
        if ($contextid <= 0) {
            throw new \invalid_parameter_exception('context_id must be a positive integer.');
        }

        $context = \context::instance_by_id($contextid);
        self::validate_category_in_context($categoryid, $context);

        return $context;
    }

    /**
     * Validate that a category belongs to a question bank context.
     *
     * @param int $categoryid Categoryid.
     * @param \context $context Context.
     */
    public static function validate_category_in_context(int $categoryid, \context $context): void {
        self::require_question_api();

        $categories = category_helper::get_categories_for_contexts((string) $context->id, 'parent, sortorder, name ASC', true);
        foreach ($categories as $category) {
            if ((int) $category->id === $categoryid) {
                return;
            }
        }

        throw new \invalid_parameter_exception('category_id must belong to context_id.');
    }

    /**
     * Return the canonical question response shape.
     *
     * @param \stdClass $question Question.
     * @return array
     */
    public static function question_to_response(\stdClass $question): array {
        $entry = get_question_bank_entry((int) $question->id);

        return [
            'question_id' => (int) $question->id,
            'category_id' => (int) $entry->questioncategoryid,
            'question_type' => self::to_contract_question_type((string) $question->qtype),
            'name' => format_string($question->name),
        ];
    }

    /**
     * Return the canonical detailed question response shape.
     *
     * @param \stdClass $question Question.
     * @return array
     */
    public static function question_to_detailed_response(\stdClass $question): array {
        $response = self::question_to_response($question);
        $response['question_text'] = format_text(
            (string) ($question->questiontext ?? ''),
            (int) ($question->questiontextformat ?? FORMAT_HTML),
            ['para' => false]
        );
        $response['default_mark'] = (float) ($question->defaultmark ?? 0);

        return $response;
    }

    /**
     * Return ready questions in a question category through Moodle question APIs.
     *
     * @param int $categoryid Categoryid.
     * @return array
     */
    public static function get_questions(int $categoryid): array {
        self::require_question_api();

        if ($categoryid <= 0) {
            throw new \invalid_parameter_exception('category_id must be a positive integer.');
        }

        $questionids = \question_bank::get_finder()->get_questions_from_categories([$categoryid], null);
        if (empty($questionids)) {
            return [];
        }

        $questions = question_load_questions(array_values($questionids));
        $responses = [];
        foreach ($questionids as $questionid) {
            if (!empty($questions[$questionid])) {
                $responses[] = self::question_to_detailed_response($questions[$questionid]);
            }
        }

        return $responses;
    }

    /**
     * Return raw question objects from a question category.
     *
     * @param int $categoryid Categoryid.
     * @return \stdClass[]
     */
    public static function get_question_objects(int $categoryid): array {
        self::require_question_api();

        if ($categoryid <= 0) {
            throw new \invalid_parameter_exception('category_id must be a positive integer.');
        }

        $questionids = \question_bank::get_finder()->get_questions_from_categories([$categoryid], null);
        if (empty($questionids)) {
            return [];
        }

        $loaded = question_load_questions(array_values($questionids));
        $questions = [];
        foreach ($questionids as $questionid) {
            if (!empty($loaded[$questionid])) {
                $questions[] = $loaded[$questionid];
            }
        }

        return $questions;
    }

    /**
     * Convert a Moodle question to a portable MoodlIA blueprint entry.
     *
     * @param \stdClass $question Question.
     * @return array
     */
    public static function question_to_blueprint(\stdClass $question): array {
        $base = self::question_to_detailed_response($question);
        $type = (string) $base['question_type'];
        $options = self::question_options_to_blueprint($question, $type);

        return [
            'source_question_id' => (int) $question->id,
            'question_type' => $type,
            'name' => (string) $base['name'],
            'question_text' => (string) $base['question_text'],
            'options' => $options,
        ];
    }

    /**
     * Convert loaded Moodle question options to create_question-compatible options.
     *
     * @param \stdClass $question Question.
     * @param string $type Type.
     * @return array
     */
    private static function question_options_to_blueprint(\stdClass $question, string $type): array {
        $options = [
            'default_mark' => (float) ($question->defaultmark ?? 1),
            'general_feedback' => self::format_question_text((string) ($question->generalfeedback ?? ''), (int) ($question->generalfeedbackformat ?? FORMAT_HTML)),
        ];

        if ($type === 'description') {
            return $options;
        }

        if (!isset($question->options) || !is_object($question->options)) {
            throw new \invalid_parameter_exception("Question {$question->id} does not expose exportable {$type} options.");
        }

        $rawoptions = $question->options;
        if ($type === 'truefalse') {
            $options['correct_answer'] = isset($rawoptions->trueanswer) && (int) $rawoptions->trueanswer === (int) ($rawoptions->correctanswer ?? $rawoptions->trueanswer);
            $options['feedback_true'] = isset($rawoptions->trueanswer) ? self::answer_feedback_to_text($rawoptions->trueanswer) : '';
            $options['feedback_false'] = isset($rawoptions->falseanswer) ? self::answer_feedback_to_text($rawoptions->falseanswer) : '';
            return $options;
        }

        if (in_array($type, ['shortanswer', 'multichoice', 'numerical'], true)) {
            $answers = self::answers_to_blueprint($rawoptions->answers ?? []);
            if ($answers === []) {
                throw new \invalid_parameter_exception("Question {$question->id} has no exportable answers.");
            }
            $options['answers'] = $answers;

            if ($type === 'shortanswer') {
                $options['case_sensitive'] = !empty($rawoptions->usecase);
            } else if ($type === 'multichoice') {
                $options['single'] = !empty($rawoptions->single);
                $options['shuffle_answers'] = !empty($rawoptions->shuffleanswers);
                $options['answer_numbering'] = (string) ($rawoptions->answernumbering ?? 'abc');
                $options['correct_feedback'] = self::format_question_text(
                    (string) ($rawoptions->correctfeedback ?? ''),
                    (int) ($rawoptions->correctfeedbackformat ?? FORMAT_HTML)
                );
                $options['partially_correct_feedback'] = self::format_question_text(
                    (string) ($rawoptions->partiallycorrectfeedback ?? ''),
                    (int) ($rawoptions->partiallycorrectfeedbackformat ?? FORMAT_HTML)
                );
                $options['incorrect_feedback'] = self::format_question_text(
                    (string) ($rawoptions->incorrectfeedback ?? ''),
                    (int) ($rawoptions->incorrectfeedbackformat ?? FORMAT_HTML)
                );
            }

            return $options;
        }

        if ($type === 'matching') {
            $subquestions = [];
            foreach (($rawoptions->subquestions ?? []) as $subquestion) {
                $questiontext = self::format_question_text(
                    (string) ($subquestion->questiontext ?? ''),
                    (int) ($subquestion->questiontextformat ?? FORMAT_HTML)
                );
                $answertext = trim((string) ($subquestion->answertext ?? ''));
                if ($questiontext === '' || $answertext === '') {
                    continue;
                }
                $subquestions[] = [
                    'question' => $questiontext,
                    'answer' => $answertext,
                ];
            }
            if (count($subquestions) < 2) {
                throw new \invalid_parameter_exception("Question {$question->id} has no exportable matching pairs.");
            }
            $options['subquestions'] = $subquestions;
            $options['shuffle_answers'] = !empty($rawoptions->shuffleanswers);
            $options['correct_feedback'] = self::format_question_text(
                (string) ($rawoptions->correctfeedback ?? ''),
                (int) ($rawoptions->correctfeedbackformat ?? FORMAT_HTML)
            );
            $options['partially_correct_feedback'] = self::format_question_text(
                (string) ($rawoptions->partiallycorrectfeedback ?? ''),
                (int) ($rawoptions->partiallycorrectfeedbackformat ?? FORMAT_HTML)
            );
            $options['incorrect_feedback'] = self::format_question_text(
                (string) ($rawoptions->incorrectfeedback ?? ''),
                (int) ($rawoptions->incorrectfeedbackformat ?? FORMAT_HTML)
            );
            return $options;
        }

        if ($type === 'essay') {
            $options['response_format'] = (string) ($rawoptions->responseformat ?? 'editor');
            $options['response_field_lines'] = (int) ($rawoptions->responsefieldlines ?? 10);
            $options['attachments'] = (int) ($rawoptions->attachments ?? 0);
            $options['attachments_required'] = (int) ($rawoptions->attachmentsrequired ?? 0);
            $options['grader_info'] = self::format_question_text((string) ($rawoptions->graderinfo ?? ''), (int) ($rawoptions->graderinfoformat ?? FORMAT_HTML));
            $options['response_template'] = self::format_question_text((string) ($rawoptions->responsetemplate ?? ''), (int) ($rawoptions->responsetemplateformat ?? FORMAT_HTML));
            return $options;
        }

        throw new \invalid_parameter_exception("question_type={$type} is not exportable as a MoodlIA blueprint yet.");
    }

    /**
     * Convert a Moodle answers collection to public blueprint answers.
     *
     * @param mixed $answers Answers.
     * @return array
     */
    private static function answers_to_blueprint($answers): array {
        if (!is_array($answers)) {
            return [];
        }

        $result = [];
        foreach ($answers as $answer) {
            if (!is_object($answer)) {
                continue;
            }
            $entry = [
                'text' => (string) ($answer->answer ?? ''),
                'fraction' => (float) ($answer->fraction ?? 0),
                'feedback' => self::answer_feedback_to_text($answer),
            ];
            if (isset($answer->tolerance)) {
                $entry['tolerance'] = (string) $answer->tolerance;
            }
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Return formatted answer feedback text.
     *
     * @param mixed $answer Answer.
     * @return string
     */
    private static function answer_feedback_to_text($answer): string {
        if (!is_object($answer)) {
            return '';
        }

        return self::format_question_text((string) ($answer->feedback ?? ''), (int) ($answer->feedbackformat ?? FORMAT_HTML));
    }

    /**
     * Format question text consistently for blueprint output.
     *
     * @param string $text Text.
     * @param int $format Format.
     * @return string
     */
    private static function format_question_text(string $text, int $format): string {
        return format_text($text, $format, ['para' => false]);
    }

    /**
     * Move a question to another question category.
     *
     * @param int $courseid Courseid.
     * @param int $questionid Questionid.
     * @param int $targetcategoryid Targetcategoryid.
     * @param string|null $targetbankscope Targetbankscope.
     * @param int|null $targetquestionbankmoduleid Targetquestionbankmoduleid.
     * @param int|null $targetquizmoduleid Targetquizmoduleid.
     * @return array
     */
    public static function move_question(
        int $courseid,
        int $questionid,
        int $targetcategoryid,
        ?string $targetbankscope = null,
        ?int $targetquestionbankmoduleid = null,
        ?int $targetquizmoduleid = null
    ): array {
        self::require_question_api();

        if ($questionid <= 0) {
            throw new \invalid_parameter_exception('question_id must be a positive integer.');
        }
        if ($targetcategoryid <= 0) {
            throw new \invalid_parameter_exception('target_category_id must be a positive integer.');
        }

        $question = self::get_question($questionid);
        question_require_capability_on($question, 'move');
        $sourcecategoryid = self::question_to_response($question)['category_id'];

        $targetlocation = self::resolve_existing_question_bank_location(
            $courseid,
            $targetbankscope,
            $targetquestionbankmoduleid,
            $targetquizmoduleid
        );
        if ($targetlocation === null) {
            throw new \invalid_parameter_exception('No matching target question bank exists in the selected course.');
        }

        $targetcontext = $targetlocation['context'];
        require_capability('moodle/question:add', $targetcontext);

        $categories = self::get_question_categories(
            $courseid,
            $targetbankscope,
            $targetquestionbankmoduleid,
            $targetquizmoduleid,
            false
        );
        $categoryfound = false;
        foreach ($categories as $category) {
            if ((int) $category['category_id'] === $targetcategoryid) {
                $categoryfound = true;
                break;
            }
        }
        if (!$categoryfound) {
            throw new \invalid_parameter_exception('target_category_id must belong to the selected target question bank.');
        }

        $moved = question_move_questions_to_category([$questionid], $targetcategoryid);
        if (!$moved) {
            throw new \moodle_exception('error', 'local_moodlia', '', null, 'Could not move the question to the target category.');
        }

        $movedquestion = self::question_to_response(self::get_question($questionid));

        return [
            'question_id' => $questionid,
            'source_category_id' => $sourcecategoryid,
            'target_category_id' => (int) $movedquestion['category_id'],
            'target_context_id' => (int) $targetcontext->id,
            'target_bank_scope' => (string) $targetlocation['bank_scope'],
            'target_question_bank_module_id' => $targetlocation['question_bank_module_id'],
            'target_quiz_module_id' => $targetlocation['quiz_module_id'],
            'moved' => true,
        ];
    }

    /**
     * Delete or hide a Moodle question through Moodle question APIs.
     *
     * @param int $questionid Questionid.
     * @return array
     */
    public static function delete_question(int $questionid): array {
        self::require_question_api();

        $question = self::get_question($questionid);
        question_require_capability_on($question, 'edit');
        question_delete_question($questionid);

        return [
            'deleted' => true,
            'id' => $questionid,
        ];
    }

    /**
     * Add true/false-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_truefalse_options(\stdClass $form, array $options): void {
        $correct = (bool) ($options['correct_answer'] ?? true);
        $form->correctanswer = $correct ? 1 : 0;
        $form->feedbacktrue = [
            'text' => (string) ($options['feedback_true'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->feedbackfalse = [
            'text' => (string) ($options['feedback_false'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->showstandardinstruction = 1;
    }

    /**
     * Add short-answer-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_shortanswer_options(\stdClass $form, array $options): void {
        $answers = $options['answers'] ?? null;
        if (!is_array($answers) || $answers === []) {
            throw new \invalid_parameter_exception('options.answers is required for shortanswer questions.');
        }

        $form->usecase = !empty($options['case_sensitive']) ? 1 : 0;
        $form->answer = [];
        $form->fraction = [];
        $form->feedback = [];

        foreach ($answers as $index => $answer) {
            if (!is_array($answer)) {
                throw new \invalid_parameter_exception('Each shortanswer answer must be an object.');
            }

            $text = trim((string) ($answer['text'] ?? ''));
            if ($text === '') {
                throw new \invalid_parameter_exception('Each shortanswer answer requires text.');
            }

            $form->answer[$index] = $text;
            $form->fraction[$index] = (float) ($answer['fraction'] ?? ($index === 0 ? 1 : 0));
            $form->feedback[$index] = [
                'text' => (string) ($answer['feedback'] ?? ''),
                'format' => FORMAT_HTML,
            ];
        }
    }

    /**
     * Add multiple-choice-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_multichoice_options(\stdClass $form, array $options): void {
        $answers = $options['answers'] ?? null;
        if (!is_array($answers) || count($answers) < 2) {
            throw new \invalid_parameter_exception('options.answers must contain at least two answers for multichoice questions.');
        }

        $form->single = !empty($options['single']) ? 1 : 0;
        $form->shuffleanswers = array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1;
        $form->answernumbering = clean_param((string) ($options['answer_numbering'] ?? 'abc'), PARAM_ALPHANUMEXT);
        if ($form->answernumbering === '') {
            $form->answernumbering = 'abc';
        }
        $form->shownumcorrect = !empty($options['show_num_correct']) ? 1 : 0;
        $form->showstandardinstruction = array_key_exists('show_standard_instruction', $options)
            ? (int) !empty($options['show_standard_instruction'])
            : 1;

        $form->answer = [];
        $form->fraction = [];
        $form->feedback = [];

        $positivefractions = 0;
        $fractionsum = 0.0;
        foreach ($answers as $index => $answer) {
            if (!is_array($answer)) {
                throw new \invalid_parameter_exception('Each multichoice answer must be an object.');
            }

            $text = trim((string) ($answer['text'] ?? ''));
            if ($text === '') {
                throw new \invalid_parameter_exception('Each multichoice answer requires text.');
            }

            $fraction = (float) ($answer['fraction'] ?? 0);
            if ($fraction > 0) {
                $positivefractions++;
                $fractionsum += $fraction;
            }

            $form->answer[$index] = [
                'text' => $text,
                'format' => FORMAT_HTML,
            ];
            $form->fraction[$index] = $fraction;
            $form->feedback[$index] = [
                'text' => (string) ($answer['feedback'] ?? ''),
                'format' => FORMAT_HTML,
            ];
        }

        if ($positivefractions < 1) {
            throw new \invalid_parameter_exception('At least one multichoice answer must have a positive fraction.');
        }
        if ($form->single && $positivefractions !== 1) {
            throw new \invalid_parameter_exception('A single-answer multichoice question must have exactly one positive-fraction answer.');
        }
        if (!$form->single && abs($fractionsum - 1.0) > 0.00001) {
            throw new \invalid_parameter_exception('Multi-answer multichoice positive fractions must add up to 1.');
        }

        $form->correctfeedback = [
            'text' => (string) ($options['correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->partiallycorrectfeedback = [
            'text' => (string) ($options['partially_correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->incorrectfeedback = [
            'text' => (string) ($options['incorrect_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
    }

    /**
     * Add numerical-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_numerical_options(\stdClass $form, array $options): void {
        $answers = $options['answers'] ?? null;
        if (!is_array($answers) || $answers === []) {
            throw new \invalid_parameter_exception('options.answers is required for numerical questions.');
        }

        $form->answer = [];
        $form->fraction = [];
        $form->feedback = [];
        $form->tolerance = [];
        $form->unitrole = 3;
        $form->unitpenalty = 0;
        $form->unitgradingtypes = 0;
        $form->multichoicedisplay = 0;
        $form->unitsleft = 0;

        $positivefractions = 0;
        foreach ($answers as $index => $answer) {
            if (!is_array($answer)) {
                throw new \invalid_parameter_exception('Each numerical answer must be an object.');
            }

            $text = trim((string) ($answer['text'] ?? ''));
            if ($text === '') {
                throw new \invalid_parameter_exception('Each numerical answer requires text.');
            }
            if ($text !== '*' && !is_numeric($text)) {
                throw new \invalid_parameter_exception('Each numerical answer text must be numeric or "*".');
            }

            $fraction = (float) ($answer['fraction'] ?? ($index === 0 ? 1 : 0));
            if ($fraction > 0) {
                $positivefractions++;
            }

            $tolerance = (string) ($answer['tolerance'] ?? 0);
            if ($tolerance !== '' && !is_numeric($tolerance)) {
                throw new \invalid_parameter_exception('Each numerical answer tolerance must be numeric.');
            }

            $form->answer[$index] = $text;
            $form->fraction[$index] = $fraction;
            $form->tolerance[$index] = $tolerance;
            $form->feedback[$index] = [
                'text' => (string) ($answer['feedback'] ?? ''),
                'format' => FORMAT_HTML,
            ];
        }

        if ($positivefractions < 1) {
            throw new \invalid_parameter_exception('At least one numerical answer must have a positive fraction.');
        }
    }

    /**
     * Add calculated-question-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param string $questiontext Questiontext.
     * @param array $options Options.
     * @param string $questiontype Questiontype.
     */
    private static function apply_calculated_options(
        \stdClass $form,
        string $questiontext,
        array $options,
        string $questiontype
    ): void {
        $answers = $options['answers'] ?? null;
        if (!is_array($answers) || $answers === []) {
            throw new \invalid_parameter_exception("options.answers is required for {$questiontype} questions.");
        }

        $form->qtype = $questiontype;
        $form->synchronize = 0;
        $form->initialcategory = 1;
        $form->reload = 1;
        $form->mform_isexpanded_id_answerhdr = 1;
        $form->noanswers = count($answers);
        $form->answer = [];
        $form->fraction = [];
        $form->tolerance = [];
        $form->tolerancetype = [];
        $form->correctanswerlength = [];
        $form->correctanswerformat = [];
        $form->feedback = [];
        $form->unitrole = '3';
        $form->unitpenalty = 0.1;
        $form->unitgradingtypes = '1';
        $form->unitsleft = '0';
        $form->nounits = 1;
        $form->multiplier = ['1.0'];
        $form->single = $questiontype === 'calculatedmulti'
            ? (array_key_exists('single', $options) ? (int) !empty($options['single']) : 1)
            : 0;
        $form->answernumbering = clean_param((string) ($options['answer_numbering'] ?? 'abc'), PARAM_ALPHANUMEXT);
        if ($form->answernumbering === '') {
            $form->answernumbering = 'abc';
        }
        $form->shuffleanswers = $questiontype === 'calculatedmulti'
            ? (array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1)
            : 0;
        $form->shownumcorrect = !empty($options['show_num_correct']) ? 1 : 0;
        $form->showstandardinstruction = array_key_exists('show_standard_instruction', $options)
            ? (int) !empty($options['show_standard_instruction'])
            : 1;

        $positivefractions = 0;
        $fractionsum = 0.0;
        $formulas = [];
        foreach ($answers as $index => $answer) {
            if (!is_array($answer)) {
                throw new \invalid_parameter_exception("Each {$questiontype} answer must be an object.");
            }

            $formula = trim((string) ($answer['text'] ?? ($answer['formula'] ?? '')));
            if ($formula === '') {
                throw new \invalid_parameter_exception("Each {$questiontype} answer requires text or formula.");
            }

            $fraction = (float) ($answer['fraction'] ?? ($index === 0 ? 1 : 0));
            if ($fraction > 0) {
                $positivefractions++;
                $fractionsum += $fraction;
            }

            $tolerance = (string) ($answer['tolerance'] ?? 0.01);
            if ($tolerance !== '' && !is_numeric($tolerance)) {
                throw new \invalid_parameter_exception("Each {$questiontype} answer tolerance must be numeric.");
            }

            $tolerancetype = (int) ($answer['tolerance_type'] ?? 1);
            if (!in_array($tolerancetype, [1, 2, 3], true)) {
                throw new \invalid_parameter_exception("Each {$questiontype} answer tolerance_type must be 1, 2, or 3.");
            }

            $correctlength = (int) ($answer['correct_answer_length'] ?? 2);
            if ($correctlength < 0 || $correctlength > 9) {
                throw new \invalid_parameter_exception("Each {$questiontype} answer correct_answer_length must be between 0 and 9.");
            }

            $correctformat = (int) ($answer['correct_answer_format'] ?? 1);
            if (!in_array($correctformat, [1, 2], true)) {
                throw new \invalid_parameter_exception("Each {$questiontype} answer correct_answer_format must be 1 or 2.");
            }

            $form->answer[$index] = $questiontype === 'calculatedmulti'
                ? [
                    'text' => $formula,
                    'format' => FORMAT_HTML,
                ]
                : $formula;
            $form->fraction[$index] = $fraction;
            $form->tolerance[$index] = $tolerance;
            $form->tolerancetype[$index] = $tolerancetype;
            $form->correctanswerlength[$index] = $correctlength;
            $form->correctanswerformat[$index] = $correctformat;
            $form->feedback[$index] = [
                'text' => (string) ($answer['feedback'] ?? ''),
                'format' => FORMAT_HTML,
            ];
            $formulas[] = $formula;
        }

        if ($positivefractions < 1) {
            throw new \invalid_parameter_exception("At least one {$questiontype} answer must have a positive fraction.");
        }
        if ($questiontype === 'calculatedmulti' && $form->single && $positivefractions !== 1) {
            throw new \invalid_parameter_exception('A single-answer calculatedmulti question must have exactly one positive-fraction answer.');
        }
        if ($questiontype === 'calculatedmulti' && !$form->single && abs($fractionsum - 1.0) > 0.00001) {
            throw new \invalid_parameter_exception('Multi-answer calculatedmulti positive fractions must add up to 1.');
        }

        $form->correctfeedback = [
            'text' => (string) ($options['correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->partiallycorrectfeedback = [
            'text' => (string) ($options['partially_correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->incorrectfeedback = [
            'text' => (string) ($options['incorrect_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];

        $variables = self::build_calculated_variables($questiontext, $formulas, $options, $questiontype);
        $datasetvalues = self::build_calculated_dataset_values($variables, $options, $questiontype);
        self::apply_calculated_datasets($form, $variables, $datasetvalues);
    }

    /**
     * Build calculated dataset variable definitions.
     *
     * @param string $questiontext Questiontext.
     * @param array $formulas Formulas.
     * @param array $options Options.
     * @param string $questiontype Questiontype.
     * @return array
     */
    private static function build_calculated_variables(
        string $questiontext,
        array $formulas,
        array $options,
        string $questiontype
    ): array {
        $configured = $options['variables'] ?? null;
        $variables = [];

        if (is_array($configured) && $configured !== []) {
            foreach ($configured as $variable) {
                if (!is_array($variable)) {
                    throw new \invalid_parameter_exception("Each {$questiontype} variable must be an object.");
                }

                $name = self::clean_calculated_variable_name((string) ($variable['name'] ?? ''), $questiontype);
                $variables[$name] = [
                    'name' => $name,
                    'min' => self::validate_calculated_number($variable['min'] ?? 1, 'variable min', $questiontype),
                    'max' => self::validate_calculated_number($variable['max'] ?? 10, 'variable max', $questiontype),
                    'decimals' => self::validate_calculated_decimals($variable['decimals'] ?? 1, $questiontype),
                    'distribution' => self::validate_calculated_distribution($variable['distribution'] ?? 'uniform', $questiontype),
                ];
            }
        } else {
            $detected = self::detect_calculated_variable_names($questiontext, $formulas, $questiontype);
            foreach ($detected as $name) {
                $variables[$name] = [
                    'name' => $name,
                    'min' => 1.0,
                    'max' => 10.0,
                    'decimals' => 1,
                    'distribution' => self::validate_calculated_distribution('uniform', $questiontype),
                ];
            }
        }

        if ($variables === []) {
            throw new \invalid_parameter_exception("{$questiontype} questions require at least one dataset variable such as {a}.");
        }

        foreach ($variables as $variable) {
            if ($variable['max'] < $variable['min']) {
                throw new \invalid_parameter_exception("Each {$questiontype} variable max must be greater than or equal to min.");
            }
        }

        return array_values($variables);
    }

    /**
     * Detect dataset variable names from question text and answer formulas.
     *
     * @param string $questiontext Questiontext.
     * @param array $formulas Formulas.
     * @param string $questiontype Questiontype.
     * @return array
     */
    private static function detect_calculated_variable_names(string $questiontext, array $formulas, string $questiontype): array {
        $source = $questiontext . "\n" . implode("\n", $formulas);
        preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*)\}/', $source, $matches);
        $names = [];
        foreach ($matches[1] as $name) {
            $names[] = self::clean_calculated_variable_name($name, $questiontype);
        }

        return array_values(array_unique($names));
    }

    /**
     * Build dataset rows for calculated variables.
     *
     * @param array $variables Variables.
     * @param array $options Options.
     * @param string $questiontype Questiontype.
     * @return array
     */
    private static function build_calculated_dataset_values(array $variables, array $options, string $questiontype): array {
        $configured = $options['dataset_values'] ?? ($options['datasets'] ?? null);
        if (is_array($configured) && $configured !== []) {
            $rows = [];
            foreach ($configured as $row) {
                if (!is_array($row)) {
                    throw new \invalid_parameter_exception("Each {$questiontype} dataset_values item must be an object.");
                }

                $values = [];
                foreach ($variables as $variable) {
                    $name = $variable['name'];
                    if (!array_key_exists($name, $row)) {
                        throw new \invalid_parameter_exception("Each {$questiontype} dataset_values item must include {$name}.");
                    }
                    $values[$name] = self::validate_calculated_number($row[$name], "dataset value {$name}", $questiontype);
                }
                $rows[] = $values;
            }

            return $rows;
        }

        $count = (int) ($options['dataset_count'] ?? 10);
        if ($count < 1 || $count > 100) {
            throw new \invalid_parameter_exception("{$questiontype} dataset_count must be between 1 and 100.");
        }

        $rows = [];
        for ($index = 0; $index < $count; $index++) {
            $row = [];
            foreach ($variables as $variable) {
                $step = $count === 1 ? 0 : $index / ($count - 1);
                $value = $variable['min'] + (($variable['max'] - $variable['min']) * $step);
                $row[$variable['name']] = round($value, $variable['decimals']);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Apply calculated dataset definitions and items.
     *
     * @param \stdClass $form Form.
     * @param array $variables Variables.
     * @param array $datasetvalues Datasetvalues.
     */
    private static function apply_calculated_datasets(\stdClass $form, array $variables, array $datasetvalues): void {
        $form->calcmin = [];
        $form->calcmax = [];
        $form->calclength = [];
        $form->calcdistribution = [];
        $form->datasetdef = [];
        $form->defoptions = [];
        $form->number = [];
        $form->itemid = [];
        $form->definition = [];
        $form->selectadd = (string) count($datasetvalues);
        $form->selectshow = (string) count($datasetvalues);

        $datasetdefs = [];
        foreach ($variables as $index => $variable) {
            $datasetindex = $index + 1;
            $defid = '1-0-' . $variable['name'];
            $datasetdefs[$variable['name']] = $defid;
            $form->calcmin[$datasetindex] = $variable['min'];
            $form->calcmax[$datasetindex] = $variable['max'];
            $form->calclength[$datasetindex] = (string) $variable['decimals'];
            $form->calcdistribution[$datasetindex] = $variable['distribution'];
            $form->datasetdef[$datasetindex] = $defid;
            $form->defoptions[$datasetindex] = '';
        }

        $itemindex = 1;
        foreach ($datasetvalues as $row) {
            foreach ($variables as $variable) {
                $name = $variable['name'];
                $form->number[$itemindex] = (string) $row[$name];
                $form->itemid[$itemindex] = 0;
                $form->definition[$itemindex] = $datasetdefs[$name];
                $itemindex++;
            }
        }
    }

    /**
     * Clean and validate a calculated variable name.
     *
     * @param string $name Name.
     * @param string $questiontype Questiontype.
     * @return string
     */
    private static function clean_calculated_variable_name(string $name, string $questiontype): string {
        $name = clean_param($name, PARAM_ALPHANUMEXT);
        if ($name === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            throw new \invalid_parameter_exception("{$questiontype} variable names must start with a letter and contain only letters, numbers, and underscores.");
        }

        return $name;
    }

    /**
     * Validate a calculated numeric value.
     *
     * @param mixed $value Value.
     * @param string $label Label.
     * @param string $questiontype Questiontype.
     * @return float
     */
    private static function validate_calculated_number($value, string $label, string $questiontype): float {
        if (!is_numeric($value)) {
            throw new \invalid_parameter_exception("{$questiontype} {$label} must be numeric.");
        }

        return (float) $value;
    }

    /**
     * Validate calculated generated-value decimal length.
     *
     * @param mixed $value Value.
     * @param string $questiontype Questiontype.
     * @return int
     */
    private static function validate_calculated_decimals($value, string $questiontype): int {
        $decimals = (int) $value;
        if ($decimals < 0 || $decimals > 9) {
            throw new \invalid_parameter_exception("{$questiontype} variable decimals must be between 0 and 9.");
        }

        return $decimals;
    }

    /**
     * Validate calculated generated-value distribution.
     *
     * @param mixed $value Value.
     * @param string $questiontype Questiontype.
     * @return int|string
     */
    private static function validate_calculated_distribution($value, string $questiontype) {
        $raw = strtolower(trim((string) $value));
        $mapping = [
            'uniform' => 'uniform',
            'loguniform' => 'loguniform',
        ];

        if (!isset($mapping[$raw])) {
            throw new \invalid_parameter_exception(
                "{$questiontype} variable distribution must be uniform or loguniform."
            );
        }

        $distribution = $mapping[$raw];
        if ($questiontype === 'calculatedsimple') {
            return $distribution === 'loguniform' ? 1 : 0;
        }

        return $distribution;
    }

    /**
     * Add matching-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_matching_options(\stdClass $form, array $options): void {
        $subquestions = $options['subquestions'] ?? ($options['pairs'] ?? null);
        if (!is_array($subquestions) || count($subquestions) < 2) {
            throw new \invalid_parameter_exception(
                'options.subquestions must contain at least two question/answer pairs for matching questions.'
            );
        }

        $form->shuffleanswers = array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1;
        $form->shownumcorrect = !empty($options['show_num_correct']) ? 1 : 0;
        $form->showstandardinstruction = array_key_exists('show_standard_instruction', $options)
            ? (int) !empty($options['show_standard_instruction'])
            : 1;
        $form->subquestions = [];
        $form->subanswers = [];

        $index = 0;
        foreach ($subquestions as $subquestion) {
            if (!is_array($subquestion)) {
                throw new \invalid_parameter_exception('Each matching subquestion must be an object.');
            }

            $questiontext = trim((string) ($subquestion['question'] ?? ($subquestion['text'] ?? '')));
            $answertext = trim((string) ($subquestion['answer'] ?? ''));
            if ($questiontext === '' || $answertext === '') {
                throw new \invalid_parameter_exception('Each matching subquestion requires question and answer.');
            }

            $form->subquestions[$index] = [
                'text' => $questiontext,
                'format' => FORMAT_HTML,
            ];
            $form->subanswers[$index] = $answertext;
            $index++;
        }

        $extraanswers = $options['extra_answers'] ?? [];
        if (!is_array($extraanswers)) {
            throw new \invalid_parameter_exception('options.extra_answers must be an array when provided.');
        }

        foreach ($extraanswers as $extraanswer) {
            if (is_array($extraanswer)) {
                $answertext = trim((string) ($extraanswer['answer'] ?? ($extraanswer['text'] ?? '')));
            } else {
                $answertext = trim((string) $extraanswer);
            }

            if ($answertext === '') {
                throw new \invalid_parameter_exception('Each matching extra answer requires text.');
            }

            $form->subquestions[$index] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $form->subanswers[$index] = $answertext;
            $index++;
        }

        if (count($form->subanswers) < 3) {
            throw new \invalid_parameter_exception(
                'Matching questions require at least three available answers; add a third pair or use options.extra_answers.'
            );
        }

        $form->correctfeedback = [
            'text' => (string) ($options['correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->partiallycorrectfeedback = [
            'text' => (string) ($options['partially_correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->incorrectfeedback = [
            'text' => (string) ($options['incorrect_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
    }

    /**
     * Add essay-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_essay_options(\stdClass $form, array $options): void {
        $responseformat = clean_param((string) ($options['response_format'] ?? 'editor'), PARAM_ALPHANUMEXT);
        if (!in_array($responseformat, ['editor', 'editorfilepicker', 'plain', 'monospaced', 'noinline'], true)) {
            throw new \invalid_parameter_exception(
                'response_format must be one of: editor, editorfilepicker, plain, monospaced, noinline.'
            );
        }

        $lines = (int) ($options['response_field_lines'] ?? 10);
        if ($lines < 2 || $lines > 40) {
            throw new \invalid_parameter_exception('response_field_lines must be between 2 and 40.');
        }

        $attachments = (int) ($options['attachments'] ?? 0);
        if (!in_array($attachments, [0, 1, 2, 3, -1], true)) {
            throw new \invalid_parameter_exception('attachments must be one of: 0, 1, 2, 3, -1.');
        }

        $attachmentsrequired = (int) ($options['attachments_required'] ?? 0);
        if (!in_array($attachmentsrequired, [0, 1, 2, 3], true)) {
            throw new \invalid_parameter_exception('attachments_required must be one of: 0, 1, 2, 3.');
        }
        if ($attachments === 0) {
            $attachmentsrequired = 0;
        }

        $form->responseformat = $responseformat;
        $form->responserequired = array_key_exists('response_required', $options) ? (int) !empty($options['response_required']) : 1;
        $form->responsefieldlines = $lines;
        $form->attachments = $attachments;
        $form->attachmentsrequired = $attachmentsrequired;
        $form->filetypeslist = (string) ($options['file_types'] ?? '');
        $form->maxbytes = (int) ($options['max_bytes'] ?? 0);
        $form->graderinfo = [
            'text' => (string) ($options['grader_info'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->responsetemplate = [
            'text' => (string) ($options['response_template'] ?? ''),
            'format' => FORMAT_HTML,
        ];

        if (array_key_exists('min_word_limit', $options)) {
            $minwordlimit = (int) $options['min_word_limit'];
            if ($minwordlimit < 0) {
                throw new \invalid_parameter_exception('min_word_limit must be zero or greater.');
            }
            $form->minwordenabled = 1;
            $form->minwordlimit = $minwordlimit;
        }

        if (array_key_exists('max_word_limit', $options)) {
            $maxwordlimit = (int) $options['max_word_limit'];
            if ($maxwordlimit < 0) {
                throw new \invalid_parameter_exception('max_word_limit must be zero or greater.');
            }
            $form->maxwordenabled = 1;
            $form->maxwordlimit = $maxwordlimit;
        }
    }

    /**
     * Add description-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_description_options(\stdClass $form, array $options): void {
        $form->defaultmark = 0.0;
        $form->penalty = 0.0;
    }

    /**
     * Add random short-answer matching-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     * @param int $categoryid Categoryid.
     */
    private static function apply_randomsamatch_options(\stdClass $form, array $options, int $categoryid): void {
        $choose = (int) ($options['choose'] ?? 2);
        if ($choose < 2 || $choose > 10) {
            throw new \invalid_parameter_exception('choose must be between 2 and 10 for randomsamatch questions.');
        }

        $subcats = array_key_exists('subcats', $options) ? (int) !empty($options['subcats']) : 1;
        $available = \question_bank::get_qtype('randomsamatch')->get_available_saquestions_from_category($categoryid, $subcats);
        if ($available === false || count($available) < $choose) {
            throw new \invalid_parameter_exception(
                'randomsamatch requires at least choose available shortanswer questions in the selected category.'
            );
        }

        $form->choose = $choose;
        $form->subcats = $subcats;
        $form->correctfeedback = [
            'text' => (string) ($options['correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->partiallycorrectfeedback = [
            'text' => (string) ($options['partially_correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->incorrectfeedback = [
            'text' => (string) ($options['incorrect_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
    }

    /**
     * Add gapselect-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param string $questiontext Questiontext.
     * @param array $options Options.
     */
    private static function apply_gapselect_options(\stdClass $form, string $questiontext, array $options): void {
        $form->shuffleanswers = array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1;
        $form->choices = self::build_embedded_choice_options($questiontext, $options, false);
        self::apply_combined_feedback_options($form, $options);
    }

    /**
     * Add drag-and-drop-words-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param string $questiontext Questiontext.
     * @param array $options Options.
     */
    private static function apply_ddwtos_options(\stdClass $form, string $questiontext, array $options): void {
        $form->shuffleanswers = array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1;
        $form->choices = self::build_embedded_choice_options($questiontext, $options, true);
        self::apply_combined_feedback_options($form, $options);
    }

    /**
     * Build choices for embedded-slot qtypes such as gapselect and ddwtos.
     *
     * @param string $questiontext Questiontext.
     * @param array $options Options.
     * @param bool $includeinfinite Includeinfinite.
     * @return array
     */
    private static function build_embedded_choice_options(string $questiontext, array $options, bool $includeinfinite): array {
        $choices = $options['choices'] ?? ($options['answers'] ?? null);
        if (!is_array($choices) || count($choices) < 2) {
            throw new \invalid_parameter_exception('options.choices must contain at least two choices.');
        }

        $slotnumbers = self::extract_embedded_slot_numbers($questiontext);
        $highestslot = max($slotnumbers);
        if (count($choices) < $highestslot) {
            throw new \invalid_parameter_exception('options.choices must include a choice for every [[n]] slot in question_text.');
        }

        $built = [];
        foreach ($choices as $index => $choice) {
            if (!is_array($choice)) {
                throw new \invalid_parameter_exception('Each embedded choice must be an object.');
            }

            $answer = trim((string) ($choice['answer'] ?? ($choice['text'] ?? '')));
            if ($answer === '') {
                throw new \invalid_parameter_exception('Each embedded choice requires answer or text.');
            }
            if (strip_tags($answer) !== $answer) {
                throw new \invalid_parameter_exception('Embedded choices must be plain text.');
            }

            $choicegroup = (int) ($choice['group'] ?? ($choice['choice_group'] ?? 1));
            if ($choicegroup < 1 || $choicegroup > 20) {
                throw new \invalid_parameter_exception('Each embedded choice group must be between 1 and 20.');
            }

            $built[$index] = [
                'answer' => $answer,
                'choicegroup' => $choicegroup,
            ];
            if ($includeinfinite) {
                $built[$index]['infinite'] = !empty($choice['infinite']) ? 1 : 0;
            }
        }

        if ($includeinfinite) {
            foreach (array_count_values($slotnumbers) as $slotnumber => $count) {
                if ($count > 1 && empty($built[$slotnumber - 1]['infinite'])) {
                    throw new \invalid_parameter_exception(
                        'Repeated ddwtos slots require the referenced choice to set infinite=true.'
                    );
                }
            }
        }

        return $built;
    }

    /**
     * Extract Moodle embedded-slot numbers from question text.
     *
     * @param string $questiontext Questiontext.
     * @return array
     */
    private static function extract_embedded_slot_numbers(string $questiontext): array {
        if (!preg_match_all('/\[\[(\d+)]]/', $questiontext, $matches)) {
            throw new \invalid_parameter_exception('question_text must contain at least one [[n]] slot.');
        }

        $slotnumbers = [];
        foreach ($matches[1] as $slot) {
            $slotnumber = (int) $slot;
            if ($slotnumber < 1) {
                throw new \invalid_parameter_exception('question_text slots must be positive integers.');
            }
            $slotnumbers[] = $slotnumber;
        }

        return $slotnumbers;
    }

    /**
     * Add combined feedback fields shared by several qtypes.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_combined_feedback_options(\stdClass $form, array $options): void {
        $form->correctfeedback = [
            'text' => (string) ($options['correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->partiallycorrectfeedback = [
            'text' => (string) ($options['partially_correct_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
        $form->incorrectfeedback = [
            'text' => (string) ($options['incorrect_feedback'] ?? ''),
            'format' => FORMAT_HTML,
        ];
    }

    /**
     * Add drag-and-drop-marker-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_ddmarker_options(\stdClass $form, array $options): void {
        \question_bank::get_qtype('ddmarker');

        $form->bgimage = self::create_question_draft_image(
            (string) ($options['background_image_base64'] ?? ''),
            (string) ($options['background_filename'] ?? 'background.png'),
            'options.background_image_base64'
        );
        $form->shuffleanswers = array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1;
        $form->showmisplaced = !empty($options['show_misplaced']) ? 1 : 0;
        $form->drags = self::build_ddmarker_drags($options);
        $form->drops = self::build_ddmarker_drops($options, count($form->drags));
        self::apply_combined_feedback_options($form, $options);
    }

    /**
     * Add drag-and-drop-onto-image-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_ddimageortext_options(\stdClass $form, array $options): void {
        \question_bank::get_qtype('ddimageortext');

        $form->bgimage = self::create_question_draft_image(
            (string) ($options['background_image_base64'] ?? ''),
            (string) ($options['background_filename'] ?? 'background.png'),
            'options.background_image_base64'
        );
        $form->shuffleanswers = array_key_exists('shuffle_answers', $options) ? (int) !empty($options['shuffle_answers']) : 1;
        $form->dropzonevisibility = !empty($options['dropzone_visibility']) ? 1 : 0;

        $dragdata = self::build_ddimageortext_drags($options);
        $form->drags = $dragdata['drags'];
        $form->dragitem = $dragdata['dragitem'];
        $form->draglabel = $dragdata['draglabel'];
        $form->drops = self::build_ddimageortext_drops($options, count($form->drags));
        self::apply_combined_feedback_options($form, $options);
    }

    /**
     * Create a Moodle draft image file from a base64 option payload.
     *
     * @param string $base64 Base64.
     * @param string $filename Filename.
     * @param string $fieldname Fieldname.
     * @return int Draft item id.
     */
    private static function create_question_draft_image(string $base64, string $filename, string $fieldname): int {
        global $USER;

        $base64 = trim($base64);
        if ($base64 === '') {
            throw new \invalid_parameter_exception($fieldname . ' is required and must contain base64 image data.');
        }

        if (preg_match('/^data:image\/[a-z0-9.+-]+;base64,(.+)$/i', $base64, $matches)) {
            $base64 = $matches[1];
        }

        $content = base64_decode($base64, true);
        if ($content === false || $content === '') {
            throw new \invalid_parameter_exception($fieldname . ' must be valid base64 image data.');
        }

        if (@getimagesizefromstring($content) === false) {
            throw new \invalid_parameter_exception($fieldname . ' must contain a valid image.');
        }

        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, null, null, null, null);

        $filename = clean_filename($filename);
        if ($filename === '' || !preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename)) {
            $filename = 'background.png';
        }

        $filerecord = [
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ];
        get_file_storage()->create_file_from_string($filerecord, $content);

        return $draftitemid;
    }

    /**
     * Build draggable text/image items for Moodle's ddimageortext qtype.
     *
     * @param array $options Options.
     * @return array
     */
    private static function build_ddimageortext_drags(array $options): array {
        $drags = $options['drags'] ?? null;
        if (!is_array($drags) || count($drags) < 1) {
            throw new \invalid_parameter_exception('options.drags must contain at least one draggable item.');
        }

        $built = [
            'drags' => [],
            'dragitem' => [],
            'draglabel' => [],
        ];

        foreach ($drags as $index => $drag) {
            if (!is_array($drag)) {
                throw new \invalid_parameter_exception('Each ddimageortext drag must be an object.');
            }

            $type = clean_param((string) ($drag['type'] ?? ($drag['dragitemtype'] ?? 'text')), PARAM_ALPHANUMEXT);
            if ($type === 'text') {
                $type = 'word';
            }
            if (!in_array($type, ['word', 'image'], true)) {
                throw new \invalid_parameter_exception('Each ddimageortext drag type must be one of: text, word, image.');
            }

            $group = (int) ($drag['group'] ?? ($drag['draggroup'] ?? 1));
            if ($group < 1 || $group > 20) {
                throw new \invalid_parameter_exception('Each ddimageortext drag group must be between 1 and 20.');
            }

            $label = trim((string) ($drag['label'] ?? ($drag['text'] ?? '')));
            $label = clean_text($label, FORMAT_HTML);
            $draftitemid = 0;
            if ($type === 'image') {
                $draftitemid = self::create_question_draft_image(
                    (string) ($drag['image_base64'] ?? ''),
                    (string) ($drag['image_filename'] ?? ('drag-' . ($index + 1) . '.png')),
                    'options.drags[' . $index . '].image_base64'
                );
            } else if ($label === '') {
                throw new \invalid_parameter_exception('Each text ddimageortext drag requires label.');
            }

            $built['drags'][] = [
                'dragitemtype' => $type,
                'draggroup' => $group,
                'infinite' => !empty($drag['infinite']) ? 1 : 0,
            ];
            $built['dragitem'][] = $draftitemid;
            $built['draglabel'][] = $label;
        }

        return $built;
    }

    /**
     * Build drop zones for Moodle's ddimageortext qtype.
     *
     * @param array $options Options.
     * @param int $dragcount Dragcount.
     * @return array
     */
    private static function build_ddimageortext_drops(array $options, int $dragcount): array {
        $drops = $options['drops'] ?? ($options['drop_zones'] ?? null);
        if (!is_array($drops) || count($drops) < 1) {
            throw new \invalid_parameter_exception('options.drops must contain at least one drop zone.');
        }

        $built = [];
        foreach ($drops as $drop) {
            if (!is_array($drop)) {
                throw new \invalid_parameter_exception('Each ddimageortext drop must be an object.');
            }

            $xleft = self::validate_non_negative_coordinate($drop['xleft'] ?? ($drop['x'] ?? null), 'Each ddimageortext drop xleft must be zero or greater.');
            $ytop = self::validate_non_negative_coordinate($drop['ytop'] ?? ($drop['y'] ?? null), 'Each ddimageortext drop ytop must be zero or greater.');
            $choice = (int) ($drop['choice'] ?? ($drop['drag'] ?? 0));
            if ($choice < 1 || $choice > $dragcount) {
                throw new \invalid_parameter_exception('Each ddimageortext drop choice must reference an existing drag number.');
            }

            $built[] = [
                'xleft' => $xleft,
                'ytop' => $ytop,
                'choice' => $choice,
                'droplabel' => clean_text((string) ($drop['label'] ?? ($drop['droplabel'] ?? '')), FORMAT_HTML),
            ];
        }

        return $built;
    }

    /**
     * Validate a non-negative numeric image coordinate.
     *
     * @param mixed $value Value.
     * @param string $message Message.
     * @return int|float
     */
    private static function validate_non_negative_coordinate($value, string $message) {
        if (!is_numeric($value) || (float) $value < 0) {
            throw new \invalid_parameter_exception($message);
        }

        $number = (float) $value;
        return floor($number) === $number ? (int) $number : $number;
    }

    /**
     * Build marker drag records for Moodle's ddmarker qtype.
     *
     * @param array $options Options.
     * @return array
     */
    private static function build_ddmarker_drags(array $options): array {
        $drags = $options['drags'] ?? ($options['markers'] ?? null);
        if (!is_array($drags) || count($drags) < 1) {
            throw new \invalid_parameter_exception('options.drags must contain at least one marker.');
        }

        $built = [];
        foreach ($drags as $drag) {
            if (!is_array($drag)) {
                throw new \invalid_parameter_exception('Each ddmarker drag must be an object.');
            }

            $label = trim((string) ($drag['label'] ?? ($drag['text'] ?? '')));
            if ($label === '') {
                throw new \invalid_parameter_exception('Each ddmarker drag requires label.');
            }
            if (strip_tags($label) !== $label) {
                throw new \invalid_parameter_exception('ddmarker drag labels must be plain text.');
            }

            $count = (int) ($drag['count'] ?? ($drag['no_of_drags'] ?? ($drag['noofdrags'] ?? 1)));
            if ($count < 0 || $count > 100) {
                throw new \invalid_parameter_exception('Each ddmarker drag count must be between 0 and 100.');
            }

            $built[] = [
                'label' => $label,
                'noofdrags' => $count,
            ];
        }

        return $built;
    }

    /**
     * Build marker drop zones for Moodle's ddmarker qtype.
     *
     * @param array $options Options.
     * @param int $dragcount Dragcount.
     * @return array
     */
    private static function build_ddmarker_drops(array $options, int $dragcount): array {
        $drops = $options['drops'] ?? ($options['drop_zones'] ?? null);
        if (!is_array($drops) || count($drops) < 1) {
            throw new \invalid_parameter_exception('options.drops must contain at least one marker drop zone.');
        }

        $built = [];
        foreach ($drops as $drop) {
            if (!is_array($drop)) {
                throw new \invalid_parameter_exception('Each ddmarker drop must be an object.');
            }

            $shape = clean_param((string) ($drop['shape'] ?? 'circle'), PARAM_ALPHANUMEXT);
            $coords = trim((string) ($drop['coords'] ?? ''));
            $choice = (int) ($drop['choice'] ?? ($drop['drag'] ?? 0));
            if (!in_array($shape, ['circle', 'rectangle', 'polygon'], true)) {
                throw new \invalid_parameter_exception('Each ddmarker drop shape must be one of: circle, rectangle, polygon.');
            }
            if ($choice < 1 || $choice > $dragcount) {
                throw new \invalid_parameter_exception('Each ddmarker drop choice must reference an existing drag number.');
            }
            self::validate_ddmarker_coords($shape, $coords);

            $built[] = [
                'shape' => $shape,
                'coords' => $coords,
                'choice' => $choice,
            ];
        }

        return $built;
    }

    /**
     * Validate public ddmarker coordinate strings before Moodle stores them.
     *
     * @param string $shape Shape.
     * @param string $coords Coords.
     */
    private static function validate_ddmarker_coords(string $shape, string $coords): void {
        if ($coords === '') {
            throw new \invalid_parameter_exception('Each ddmarker drop requires coords.');
        }

        $number = '-?\d+(?:\.\d+)?';
        if ($shape === 'circle' && preg_match('/^' . $number . ',' . $number . ';' . $number . '$/', $coords)) {
            return;
        }
        if ($shape === 'rectangle' && preg_match('/^' . $number . ',' . $number . ';' . $number . ',' . $number . '$/', $coords)) {
            return;
        }
        if ($shape === 'polygon' && preg_match('/^' . $number . ',' . $number . '(?:;' . $number . ',' . $number . '){2,}$/', $coords)) {
            return;
        }

        throw new \invalid_parameter_exception(
            'ddmarker coords must be "x,y;r" for circle, "x,y;width,height" for rectangle, or "x,y;x,y;x,y..." for polygon.'
        );
    }

    /**
     * Add ordering-specific fields to the question form object.
     *
     * @param \stdClass $form Form.
     * @param array $options Options.
     */
    private static function apply_ordering_options(\stdClass $form, array $options): void {
        $items = $options['items'] ?? ($options['answers'] ?? null);
        if (!is_array($items) || count($items) < 2) {
            throw new \invalid_parameter_exception('options.items must contain at least two ordering items.');
        }

        $answers = [];
        $seen = [];
        foreach ($items as $index => $item) {
            if (is_array($item)) {
                $text = trim((string) ($item['text'] ?? ($item['answer'] ?? '')));
            } else {
                $text = trim((string) $item);
            }

            if ($text === '') {
                throw new \invalid_parameter_exception('Each ordering item requires text.');
            }

            $dedupekey = \core_text::strtolower(strip_tags($text));
            if (array_key_exists($dedupekey, $seen)) {
                throw new \invalid_parameter_exception('Ordering items must be unique.');
            }
            $seen[$dedupekey] = true;

            $answers[$index] = [
                'text' => $text,
                'format' => FORMAT_HTML,
            ];
        }

        $form->answer = $answers;
        $form->layouttype = self::map_ordering_layout($options['layout'] ?? 'vertical');
        $form->selecttype = self::map_ordering_selection($options['selection'] ?? 'all');
        $form->selectcount = (int) ($options['selection_count'] ?? 3);
        if ($form->selecttype !== 0) {
            if ($form->selectcount < 3) {
                throw new \invalid_parameter_exception('selection_count must be at least 3 when selection is not all.');
            }
            if ($form->selectcount > count($answers)) {
                throw new \invalid_parameter_exception('selection_count must not exceed the number of ordering items.');
            }
        }
        $form->gradingtype = self::map_ordering_grading($options['grading'] ?? 'absolute_position');
        $form->showgrading = array_key_exists('show_grading', $options) ? (int) !empty($options['show_grading']) : 1;
        $form->numberingstyle = self::map_ordering_numbering_style($options['numbering_style'] ?? 'none');
        self::apply_combined_feedback_options($form, $options);
    }

    /**
     * Map ordering layout option names to Moodle constants.
     *
     * @param mixed $layout Layout.
     * @return int
     */
    private static function map_ordering_layout($layout): int {
        if (is_int($layout)) {
            if (in_array($layout, [0, 1], true)) {
                return $layout;
            }
        }

        $value = clean_param((string) $layout, PARAM_ALPHANUMEXT);
        $map = [
            'vertical' => 0,
            'horizontal' => 1,
        ];
        if (!array_key_exists($value, $map)) {
            throw new \invalid_parameter_exception('layout must be one of: vertical, horizontal.');
        }

        return $map[$value];
    }

    /**
     * Map ordering item subset option names to Moodle constants.
     *
     * @param mixed $selection Selection.
     * @return int
     */
    private static function map_ordering_selection($selection): int {
        if (is_int($selection)) {
            if (in_array($selection, [0, 1, 2], true)) {
                return $selection;
            }
        }

        $value = clean_param((string) $selection, PARAM_ALPHANUMEXT);
        $map = [
            'all' => 0,
            'random' => 1,
            'contiguous' => 2,
        ];
        if (!array_key_exists($value, $map)) {
            throw new \invalid_parameter_exception('selection must be one of: all, random, contiguous.');
        }

        return $map[$value];
    }

    /**
     * Map ordering grading option names to Moodle constants.
     *
     * @param mixed $grading Grading.
     * @return int
     */
    private static function map_ordering_grading($grading): int {
        if (is_int($grading)) {
            if (in_array($grading, [-1, 0, 1, 2, 3, 4, 5, 6, 7], true)) {
                return $grading;
            }
        }

        $value = clean_param((string) $grading, PARAM_ALPHANUMEXT);
        $map = [
            'all_or_nothing' => -1,
            'absolute_position' => 0,
            'relative_next_exclude_last' => 1,
            'relative_next_include_last' => 2,
            'relative_one_previous_and_next' => 3,
            'relative_all_previous_and_next' => 4,
            'longest_ordered_subset' => 5,
            'longest_contiguous_subset' => 6,
            'relative_to_correct' => 7,
        ];
        if (!array_key_exists($value, $map)) {
            throw new \invalid_parameter_exception(
                'grading must be one of: ' . implode(', ', array_keys($map)) . '.'
            );
        }

        return $map[$value];
    }

    /**
     * Map ordering numbering style option names to Moodle values.
     *
     * @param mixed $style Style.
     * @return string
     */
    private static function map_ordering_numbering_style($style): string {
        $value = clean_param((string) $style, PARAM_ALPHANUMEXT);
        $allowed = ['none', 'abc', 'ABCD', '123', 'iii', 'IIII'];
        if (!in_array($value, $allowed, true)) {
            throw new \invalid_parameter_exception('numbering_style must be one of: none, abc, ABCD, 123, iii, IIII.');
        }

        return $value;
    }

    /**
     * Validate a Cloze embedded-answers question through Moodle's multianswer parser.
     *
     * @param \stdClass $form Form.
     */
    private static function apply_multianswer_options(\stdClass $form): void {
        \question_bank::get_qtype('multianswer');
        if (!function_exists('qtype_multianswer_extract_question') || !function_exists('qtype_multianswer_validate_question')) {
            throw new \moodle_exception('error', 'local_moodlia', '', null, 'Moodle multianswer question APIs are not available.');
        }

        $parsed = qtype_multianswer_extract_question($form->questiontext);
        $errors = qtype_multianswer_validate_question($parsed);
        if (!empty($errors)) {
            $message = reset($errors);
            throw new \invalid_parameter_exception((string) $message);
        }
    }

    /**
     * Add a question to a quiz activity.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $questionid Questionid.
     * @param int|null $slot Slot.
     * @return array
     */
    public static function add_question_to_quiz(int $quizmoduleid, int $questionid, ?int $slot = null): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        module_tools::get_quiz_module($course, $quizmoduleid);

        $question = self::get_question($questionid);
        question_require_capability_on($question, 'use');

        $page = $slot === null ? 0 : max(1, $slot);
        quiz_add_quiz_question($questionid, $quizobj->get_quiz(), $page);
        rebuild_course_cache($course->id, true);

        $refreshedquizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $structure = $refreshedquizobj->get_structure();
        $slotnumber = $structure->get_question_count();
        $quizslot = $structure->get_slot_by_number($slotnumber);
        $maxmark = max(1.0, (float) ($question->defaultmark ?? 1.0));
        $maxmarkchanged = $structure->update_slot_maxmark($quizslot, $maxmark);
        $quiz = $refreshedquizobj->get_quiz();
        if ($maxmarkchanged) {
            quiz_delete_previews($quiz);
        }
        $gradecalculator = $refreshedquizobj->get_grade_calculator();
        $gradecalculator->recompute_quiz_sumgrades();
        $gradecalculator->recompute_all_attempt_sumgrades();
        $gradecalculator->recompute_all_final_grades();
        quiz_update_grades($quiz, 0, true);
        rebuild_course_cache($course->id, true);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'question_id' => $questionid,
            'slot' => (int) $slotnumber,
            'maxmark' => $maxmark,
        ];
    }

    /**
     * Add random question slots to a quiz from a question category.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $categoryid Categoryid.
     * @param int $number Number.
     * @param int|null $slot Slot.
     * @param bool $includesubcategories Includesubcategories.
     * @param string|null $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @return array
     */
    public static function add_random_questions_to_quiz(
        int $quizmoduleid,
        int $categoryid,
        int $number,
        ?int $slot = null,
        bool $includesubcategories = false,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null
    ): array {
        self::require_quiz_api();

        if ($categoryid <= 0) {
            throw new \invalid_parameter_exception('category_id must be a positive integer.');
        }
        if ($number <= 0 || $number > 50) {
            throw new \invalid_parameter_exception('number must be between 1 and 50.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        $location = self::resolve_existing_question_bank_location(
            (int) $course->id,
            $bankscope,
            $questionbankmoduleid,
            $bankscope === self::BANK_SCOPE_QUIZ_PRIVATE ? $quizmoduleid : null
        );
        if ($location === null) {
            throw new \invalid_parameter_exception('No matching source question bank exists in the quiz course.');
        }

        self::validate_category_in_location((int) $course->id, $categoryid, $location);
        require_capability('moodle/question:useall', $location['context']);

        $structure = $quizobj->get_structure();
        $beforecount = $structure->get_question_count();
        $page = $slot === null ? 0 : max(1, $slot);
        $structure->add_random_questions($page, $number, [
            'filter' => [
                'category' => [
                    'jointype' => \core_question\local\bank\condition::JOINTYPE_DEFAULT,
                    'values' => [$categoryid],
                    'filteroptions' => [
                        'includesubcategories' => $includesubcategories,
                    ],
                ],
            ],
        ]);

        $quiz = $quizobj->get_quiz();
        quiz_delete_previews($quiz);
        $gradecalculator = $quizobj->get_grade_calculator();
        $gradecalculator->recompute_quiz_sumgrades();
        $gradecalculator->recompute_all_attempt_sumgrades();
        $gradecalculator->recompute_all_final_grades();
        quiz_update_grades($quiz, 0, true);
        rebuild_course_cache($course->id, true);

        $refreshedquizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $refreshedstructure = $refreshedquizobj->get_structure();
        $addedslots = [];
        for ($slotnumber = $beforecount + 1; $slotnumber <= $refreshedstructure->get_question_count(); $slotnumber++) {
            $addedslots[] = self::quiz_question_slot_to_response($refreshedstructure, $cm, $slotnumber);
        }

        return [
            'quiz_id' => (int) $refreshedquizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'category_id' => $categoryid,
            'added_count' => count($addedslots),
            'include_subcategories' => $includesubcategories,
            'slots' => $addedslots,
        ];
    }

    /**
     * Update a quiz question slot.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $slotnumber Slotnumber.
     * @param float $maxmark Maxmark.
     * @return array
     */
    public static function update_quiz_question_slot(int $quizmoduleid, int $slotnumber, float $maxmark): array {
        self::require_quiz_api();

        if ($slotnumber <= 0) {
            throw new \invalid_parameter_exception('slot must be a positive integer.');
        }
        if ($maxmark < 0.0 || $maxmark > 1000.0) {
            throw new \invalid_parameter_exception('max_mark must be between 0 and 1000.');
        }

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        $structure = $quizobj->get_structure();
        if ($slotnumber > $structure->get_question_count()) {
            throw new \invalid_parameter_exception('slot must reference a question currently used by the quiz.');
        }

        $slot = $structure->get_slot_by_number($slotnumber);
        $changed = $structure->update_slot_maxmark($slot, $maxmark);
        $quiz = $quizobj->get_quiz();
        if ($changed) {
            quiz_delete_previews($quiz);
        }
        $gradecalculator = $quizobj->get_grade_calculator();
        $gradecalculator->recompute_quiz_sumgrades();
        $gradecalculator->recompute_all_attempt_sumgrades();
        $gradecalculator->recompute_all_final_grades();
        quiz_update_grades($quiz, 0, true);
        rebuild_course_cache($course->id, true);

        $refreshedquizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $updatedslot = self::quiz_question_slot_to_response($refreshedquizobj->get_structure(), $cm, $slotnumber);

        return [
            'quiz_id' => (int) $refreshedquizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'slot' => $updatedslot['slot'],
            'slot_id' => $updatedslot['slot_id'],
            'question_id' => $updatedslot['question_id'],
            'question_type' => $updatedslot['question_type'],
            'maxmark' => $updatedslot['maxmark'],
            'updated' => true,
        ];
    }

    /**
     * Remove a question slot from a quiz activity.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int|null $slotnumber Slotnumber.
     * @param int|null $questionid Questionid.
     * @return array
     */
    public static function remove_question_from_quiz(int $quizmoduleid, ?int $slotnumber = null, ?int $questionid = null): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        $structure = $quizobj->get_structure();

        if (($slotnumber === null || $slotnumber <= 0) && ($questionid === null || $questionid <= 0)) {
            throw new \invalid_parameter_exception('Either slot or question_id is required.');
        }

        $resolvedslot = $slotnumber === null ? null : (int) $slotnumber;
        $resolvedquestionid = $questionid === null ? 0 : (int) $questionid;
        if ($resolvedslot === null) {
            for ($candidate = 1; $candidate <= $structure->get_question_count(); $candidate++) {
                $question = $structure->get_question_in_slot($candidate);
                $candidatequestionid = (int) ($question->questionid ?? $question->id ?? 0);
                if ($candidatequestionid === $resolvedquestionid) {
                    $resolvedslot = $candidate;
                    break;
                }
            }
        }

        if ($resolvedslot === null || $resolvedslot <= 0 || $resolvedslot > $structure->get_question_count()) {
            throw new \invalid_parameter_exception('slot or question_id must reference a question currently used by the quiz.');
        }

        $question = $structure->get_question_in_slot($resolvedslot);
        $removedquestionid = (int) ($question->questionid ?? $question->id ?? 0);
        $quiz = $quizobj->get_quiz();

        $structure->remove_slot($resolvedslot);
        quiz_delete_previews($quiz);
        $gradecalculator = $quizobj->get_grade_calculator();
        $gradecalculator->recompute_quiz_sumgrades();
        $gradecalculator->recompute_all_attempt_sumgrades();
        $gradecalculator->recompute_all_final_grades();
        quiz_update_grades($quiz, 0, true);
        rebuild_course_cache($course->id, true);

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'question_id' => $removedquestionid,
            'slot' => (int) $resolvedslot,
            'removed' => true,
        ];
    }

    /**
     * Return questions currently used by a quiz module.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @return array
     */
    public static function get_quiz_questions(int $quizmoduleid): array {
        self::require_quiz_api();

        $quizobj = quiz_settings::create_for_cmid($quizmoduleid);
        $course = $quizobj->get_course();
        $cm = module_tools::get_quiz_module($course, $quizmoduleid);
        $structure = $quizobj->get_structure();
        $questions = [];

        for ($slotnumber = 1; $slotnumber <= $structure->get_question_count(); $slotnumber++) {
            $questions[] = self::quiz_question_slot_to_response($structure, $cm, $slotnumber);
        }

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'quiz_module_id' => (int) $cm->id,
            'questions' => $questions,
        ];
    }

    /**
     * Validate that a category belongs to a resolved question bank location.
     *
     * @param int $courseid Courseid.
     * @param int $categoryid Categoryid.
     * @param array $location Location.
     */
    public static function validate_category_in_location(int $courseid, int $categoryid, array $location): void {
        $categories = self::get_question_categories(
            $courseid,
            $location['bank_scope'],
            $location['question_bank_module_id'],
            $location['quiz_module_id'],
            true
        );

        foreach ($categories as $category) {
            if ((int) $category['category_id'] === $categoryid) {
                return;
            }
        }

        throw new \invalid_parameter_exception('category_id must belong to the selected question bank.');
    }

    /**
     * Return a canonical quiz slot response.
     *
     * @param \mod_quiz\structure $structure Structure.
     * @param \cm_info $cm Cm.
     * @param int $slotnumber Slotnumber.
     * @return array
     */
    private static function quiz_question_slot_to_response(\mod_quiz\structure $structure, \cm_info $cm, int $slotnumber): array {
        $slot = $structure->get_slot_by_number($slotnumber);
        $question = $structure->get_question_in_slot($slotnumber);
        $questionid = (int) ($question->questionid ?? $question->id ?? 0);
        $questionname = (string) ($question->name ?? $structure->get_question_name_in_slot($slotnumber));
        $questiontype = (string) $structure->get_question_type_for_slot($slotnumber);
        if ($questiontype === '') {
            $questiontype = (string) ($question->qtype ?? 'random');
        }
        if ($questiontype === '') {
            $questiontype = 'random';
        }

        return [
            'slot' => (int) $slotnumber,
            'slot_id' => (int) ($slot->id ?? $structure->get_slot_id_for_slot($slotnumber)),
            'question_id' => $questionid,
            'name' => format_string($questionname, true, [
                'context' => \context_module::instance($cm->id),
            ]),
            'question_type' => self::to_contract_question_type($questiontype),
            'page' => (int) ($slot->page ?? 0),
            'maxmark' => (float) ($slot->maxmark ?? 0),
        ];
    }

    /**
     * Return quiz settings and structure details exposed through Moodle quiz APIs.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_quiz_details(\stdClass $course, \cm_info $cm): array {
        self::require_quiz_api();

        module_tools::get_quiz_module($course, (int) $cm->id);

        $quizobj = quiz_settings::create_for_cmid((int) $cm->id);
        $quiz = $quizobj->get_quiz();
        $structure = $quizobj->get_structure();

        return [
            'quiz_id' => (int) $quizobj->get_quizid(),
            'timeopen' => (int) ($quiz->timeopen ?? 0),
            'timeclose' => (int) ($quiz->timeclose ?? 0),
            'timelimit' => (int) ($quiz->timelimit ?? 0),
            'overduehandling' => (string) ($quiz->overduehandling ?? ''),
            'graceperiod' => (int) ($quiz->graceperiod ?? 0),
            'preferredbehaviour' => (string) ($quiz->preferredbehaviour ?? ''),
            'canredoquestions' => (int) ($quiz->canredoquestions ?? 0),
            'attempts' => (int) ($quiz->attempts ?? 0),
            'attemptonlast' => (int) ($quiz->attemptonlast ?? 0),
            'grademethod' => (int) ($quiz->grademethod ?? 0),
            'decimalpoints' => (int) ($quiz->decimalpoints ?? 0),
            'questiondecimalpoints' => (int) ($quiz->questiondecimalpoints ?? 0),
            'questionsperpage' => (int) ($quiz->questionsperpage ?? 0),
            'navmethod' => (string) ($quiz->navmethod ?? ''),
            'shuffleanswers' => (int) ($quiz->shuffleanswers ?? 0),
            'sumgrades' => (float) ($quiz->sumgrades ?? 0),
            'grade' => (float) ($quiz->grade ?? 0),
            'subnet' => (string) ($quiz->subnet ?? ''),
            'browsersecurity' => (string) ($quiz->browsersecurity ?? ''),
            'delay1' => (int) ($quiz->delay1 ?? 0),
            'delay2' => (int) ($quiz->delay2 ?? 0),
            'showuserpicture' => (int) ($quiz->showuserpicture ?? 0),
            'showblocks' => (int) ($quiz->showblocks ?? 0),
            'completionattemptsexhausted' => (int) ($quiz->completionattemptsexhausted ?? 0),
            'completionminattempts' => (int) ($quiz->completionminattempts ?? 0),
            'allowofflineattempts' => (int) ($quiz->allowofflineattempts ?? 0),
            'question_count' => (int) $structure->get_question_count(),
        ];
    }

}
