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
 * Import question bank blueprint operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Imports a portable MoodlIA JSON blueprint into a Moodle question bank.
 */
class import_question_bank_blueprint {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param string $blueprintjson Blueprintjson.
     * @param string|null $bankscope Bankscope.
     * @param int|null $questionbankmoduleid Questionbankmoduleid.
     * @param int|null $quizmoduleid Quizmoduleid.
     * @param int|null $categoryid Categoryid.
     * @param bool $createcategories Createcategories.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $blueprintjson,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        ?int $categoryid = null,
        bool $createcategories = true
    ): array {
        question_tools::require_question_api();

        $course = course_tools::get_course($courseid);
        $blueprint = self::decode_blueprint($blueprintjson);
        $location = question_tools::resolve_question_bank_location(
            (int) $course->id,
            $bankscope,
            $questionbankmoduleid,
            $quizmoduleid
        );
        $context = $location['context'];

        if ($categoryid !== null) {
            question_tools::validate_category_in_context($categoryid, $context);
        }

        $defaultcategory = question_tools::get_default_category_for_bank(
            (int) $course->id,
            $location['bank_scope'],
            $location['question_bank_module_id'],
            $location['quiz_module_id']
        );
        $rootcategoryid = $categoryid ?? (int) $defaultcategory->id;
        $categorymap = [];
        $createdcategories = [];
        $createdquestions = [];

        foreach ($blueprint['categories'] as $category) {
            $sourcecategoryid = (int) ($category['source_category_id'] ?? 0);
            if ($sourcecategoryid <= 0) {
                throw new \invalid_parameter_exception('Each blueprint category requires source_category_id.');
            }

            if ($createcategories) {
                $sourceparentid = (int) ($category['source_parent_id'] ?? 0);
                $targetparentid = $sourceparentid > 0 && isset($categorymap[$sourceparentid])
                    ? $categorymap[$sourceparentid]
                    : $rootcategoryid;
                $createdcategory = create_question_category::execute(
                    (int) $course->id,
                    (string) ($category['name'] ?? 'Imported category'),
                    $targetparentid,
                    null,
                    $location['bank_scope'],
                    $location['question_bank_module_id'],
                    $location['quiz_module_id']
                );
                $targetcategoryid = (int) $createdcategory['category_id'];
                $categorymap[$sourcecategoryid] = $targetcategoryid;
                $createdcategories[] = $createdcategory;
            } else {
                $targetcategoryid = $rootcategoryid;
                $categorymap[$sourcecategoryid] = $targetcategoryid;
            }

            foreach (($category['questions'] ?? []) as $question) {
                $createdquestions[] = self::create_blueprint_question($targetcategoryid, $question);
            }
        }

        return [
            'course_id' => (int) $course->id,
            'bank_scope' => (string) $location['bank_scope'],
            'context_id' => (int) $context->id,
            'question_bank_module_id' => $location['question_bank_module_id'],
            'quiz_module_id' => $location['quiz_module_id'],
            'created_category_count' => count($createdcategories),
            'created_question_count' => count($createdquestions),
            'created_categories_json' => json_encode($createdcategories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_questions_json' => json_encode($createdquestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Decode and validate a MoodlIA question-bank blueprint.
     *
     * @param string $blueprintjson Blueprintjson.
     * @return array
     */
    private static function decode_blueprint(string $blueprintjson): array {
        $blueprint = json_decode($blueprintjson, true);
        if (!is_array($blueprint)) {
            throw new \invalid_parameter_exception('blueprint_json must be a JSON object.');
        }
        if (($blueprint['schema'] ?? '') !== export_question_bank_blueprint::SCHEMA) {
            throw new \invalid_parameter_exception('Unsupported question bank blueprint schema.');
        }
        if (!isset($blueprint['categories']) || !is_array($blueprint['categories'])) {
            throw new \invalid_parameter_exception('blueprint_json.categories must be an array.');
        }

        return $blueprint;
    }

    /**
     * Create one question from a blueprint entry.
     *
     * @param int $categoryid Categoryid.
     * @param mixed $question Question.
     * @return array
     */
    private static function create_blueprint_question(int $categoryid, $question): array {
        if (!is_array($question)) {
            throw new \invalid_parameter_exception('Each blueprint question must be an object.');
        }

        $questiontype = trim((string) ($question['question_type'] ?? ''));
        $name = trim((string) ($question['name'] ?? ''));
        $questiontext = (string) ($question['question_text'] ?? '');
        $options = $question['options'] ?? [];
        if ($questiontype === '' || $name === '' || $questiontext === '') {
            throw new \invalid_parameter_exception('Each blueprint question requires question_type, name, and question_text.');
        }
        if (!is_array($options)) {
            throw new \invalid_parameter_exception('Each blueprint question options field must be an object.');
        }

        return create_question::execute($categoryid, $questiontype, $name, $questiontext, $options);
    }
}
