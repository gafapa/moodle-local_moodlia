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
 * Get quiz attempt data operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns rendered question data for a Moodle quiz attempt page.
 */
class get_quiz_attempt_data {
    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $attemptid Attemptid.
     * @param int $page Page.
     * @param array $preflightdata Preflightdata.
     * @return array
     */
    public static function execute(int $quizmoduleid, int $attemptid, int $page = 0, array $preflightdata = []): array {
        return question_quiz_attempt_tools::get_quiz_attempt_data($quizmoduleid, $attemptid, $page, $preflightdata);
    }
}
