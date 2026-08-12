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
 * Process quiz attempt operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Processes Moodle quiz attempt responses and can finish the attempt.
 */
class process_quiz_attempt {
    /**
     * Execute the operation.
     *
     * @param int $quizmoduleid Quizmoduleid.
     * @param int $attemptid Attemptid.
     * @param array $data Data.
     * @param bool $finishattempt Finishattempt.
     * @param bool $timeup Timeup.
     * @param array $preflightdata Preflightdata.
     * @return array
     */
    public static function execute(
        int $quizmoduleid,
        int $attemptid,
        array $data = [],
        bool $finishattempt = false,
        bool $timeup = false,
        array $preflightdata = []
    ): array {
        return question_quiz_attempt_tools::process_quiz_attempt(
            $quizmoduleid,
            $attemptid,
            $data,
            $finishattempt,
            $timeup,
            $preflightdata
        );
    }
}
