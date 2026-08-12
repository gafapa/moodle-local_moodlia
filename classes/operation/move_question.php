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
 * Move question operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Moves a Moodle question through Moodle question APIs.
 */
class move_question {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $questionid Question id.
     * @param int $targetcategoryid Destination question category id.
     * @param string|null $targetbankscope Destination bank scope.
     * @param int|null $targetquestionbankmoduleid Destination course qbank module id.
     * @param int|null $targetquizmoduleid Destination quiz module id.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $questionid,
        int $targetcategoryid,
        ?string $targetbankscope = null,
        ?int $targetquestionbankmoduleid = null,
        ?int $targetquizmoduleid = null
    ): array {
        return question_tools::move_question(
            $courseid,
            $questionid,
            $targetcategoryid,
            $targetbankscope,
            $targetquestionbankmoduleid,
            $targetquizmoduleid
        );
    }
}
