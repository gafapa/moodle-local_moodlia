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
 * Create question category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle question category through Moodle question APIs.
 */
class create_question_category {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param string $name Category name.
     * @param int|null $parentid Parent category id.
     * @param string|null $description Category description.
     * @param string|null $bankscope Question bank scope.
     * @param int|null $questionbankmoduleid Course question bank module id.
     * @param int|null $quizmoduleid Quiz module id.
     * @return array
     */
    public static function execute(
        int $courseid,
        string $name,
        ?int $parentid = null,
        ?string $description = null,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null
    ): array {
        $location = question_tools::resolve_question_bank_location(
            $courseid,
            $bankscope,
            $questionbankmoduleid,
            $quizmoduleid
        );
        $defaultcategory = question_tools::get_default_category_for_bank(
            $courseid,
            $location['bank_scope'],
            $location['question_bank_module_id'],
            $location['quiz_module_id']
        );
        $context = $location['context'];
        $parent = $parentid ?: (int) $defaultcategory->id;
        $manager = question_tools::category_manager();

        $categoryid = $manager->add_category(
            $parent . ',' . $context->id,
            trim($name),
            (string) $description,
            FORMAT_HTML
        );

        return question_tools::category_to_response($categoryid, $name, $context->id, $location);
    }
}
