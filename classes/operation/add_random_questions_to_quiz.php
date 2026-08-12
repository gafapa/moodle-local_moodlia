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
 * Add random questions to quiz operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Adds random Moodle question slots to a quiz through Moodle quiz APIs.
 */
class add_random_questions_to_quiz {
    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quiz course module id.
     * @param int $categoryid Question category id.
     * @param int $number Number of random slots.
     * @param int|null $slot Requested slot.
     * @param bool $includesubcategories Include child categories.
     * @param string|null $bankscope Source bank scope.
     * @param int|null $questionbankmoduleid Source course qbank module id.
     * @return array
     */
    public static function execute(
        int $quizmoduleid,
        int $categoryid,
        int $number,
        ?int $slot = null,
        bool $includesubcategories = false,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null
    ): array {
        return question_tools::add_random_questions_to_quiz(
            $quizmoduleid,
            $categoryid,
            $number,
            $slot,
            $includesubcategories,
            $bankscope,
            $questionbankmoduleid
        );
    }
}
