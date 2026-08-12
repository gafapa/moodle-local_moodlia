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
 * Update question operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle question by creating a new Moodle question version.
 */
class update_question {
    /**
     * Execute the operation.
     *
     * @param int $questionid Questionid.
     * @param string|null $name Name.
     * @param string|null $questiontext Questiontext.
     * @param array $options Options.
     * @return array
     */
    public static function execute(int $questionid, ?string $name = null, ?string $questiontext = null, array $options = []): array {
        $existing = question_tools::get_question($questionid);
        $name = $name ?? $existing->name;
        $questiontext = $questiontext ?? $existing->questiontext;

        return question_tools::save_question(
            $questionid,
            (int) get_question_bank_entry((int) $existing->id)->questioncategoryid,
            $existing->qtype,
            $name,
            $questiontext,
            $options
        );
    }
}
