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
 * List question categories operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Lists Moodle question categories in a selected question bank.
 */
class get_question_categories {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param string|null $bankscope Bank scope.
     * @param int|null $questionbankmoduleid Course qbank module id.
     * @param int|null $quizmoduleid Quiz module id.
     * @param bool $includetop Include the synthetic top category.
     * @return array
     */
    public static function execute(
        int $courseid,
        ?string $bankscope = null,
        ?int $questionbankmoduleid = null,
        ?int $quizmoduleid = null,
        bool $includetop = false
    ): array {
        return [
            'categories' => question_tools::get_question_categories(
                $courseid,
                $bankscope,
                $questionbankmoduleid,
                $quizmoduleid,
                $includetop
            ),
        ];
    }
}
