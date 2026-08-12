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
 * Export question bank blueprint operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Exports a portable MoodlIA JSON blueprint from a Moodle question bank.
 */
class export_question_bank_blueprint {
    public const SCHEMA = 'moodlia.question_bank_blueprint.v1';

    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param string|null $bankscope Question bank scope.
     * @param int|null $questionbankmoduleid Course question bank module id.
     * @param int|null $quizmoduleid Quiz module id.
     * @param int|null $categoryid Optional single category id.
     * @param bool $includeunsupported Include unsupported questions as skipped entries.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        ?int $categoryid = null,
        bool $includeunsupported = true
    ): array {
        question_tools::require_question_api();

        $course = course_tools::get_course($courseid);
        $location = question_tools::resolve_existing_question_bank_location(
            (int) $course->id,
            $bankscope,
            $questionbankmoduleid,
            $quizmoduleid
        );
        if ($location === null) {
            throw new \invalid_parameter_exception('No matching question bank exists in the selected course.');
        }

        $context = $location['context'];
        if ($categoryid !== null) {
            question_tools::validate_category_in_context($categoryid, $context);
        }

        $categories = question_tools::get_question_categories(
            (int) $course->id,
            $location['bank_scope'],
            $location['question_bank_module_id'],
            $location['quiz_module_id'],
            false
        );

        $exportedcategories = [];
        $questioncount = 0;
        $skipped = [];

        foreach ($categories as $category) {
            if ($categoryid !== null && (int) $category['category_id'] !== $categoryid) {
                continue;
            }

            $questions = [];
            foreach (question_tools::get_question_objects((int) $category['category_id']) as $question) {
                try {
                    $questions[] = question_tools::question_to_blueprint($question);
                    $questioncount++;
                } catch (\Throwable $error) {
                    $skipped[] = [
                        'question_id' => (int) ($question->id ?? 0),
                        'question_type' => (string) ($question->qtype ?? ''),
                        'name' => format_string((string) ($question->name ?? '')),
                        'reason' => $error->getMessage(),
                    ];
                    if (!$includeunsupported) {
                        throw $error;
                    }
                }
            }

            $exportedcategories[] = [
                'source_category_id' => (int) $category['category_id'],
                'source_parent_id' => (int) $category['parent_id'],
                'name' => (string) $category['name'],
                'questions' => $questions,
            ];
        }

        $blueprint = [
            'schema' => self::SCHEMA,
            'exported_at' => time(),
            'source_course_id' => (int) $course->id,
            'bank_scope' => (string) $location['bank_scope'],
            'context_id' => (int) $context->id,
            'question_bank_module_id' => $location['question_bank_module_id'],
            'quiz_module_id' => $location['quiz_module_id'],
            'categories' => $exportedcategories,
            'skipped_questions' => $skipped,
        ];

        return [
            'course_id' => (int) $course->id,
            'bank_scope' => (string) $location['bank_scope'],
            'context_id' => (int) $context->id,
            'question_bank_module_id' => $location['question_bank_module_id'],
            'quiz_module_id' => $location['quiz_module_id'],
            'category_count' => count($exportedcategories),
            'question_count' => $questioncount,
            'skipped_question_count' => count($skipped),
            'blueprint_json' => json_encode($blueprint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }
}
