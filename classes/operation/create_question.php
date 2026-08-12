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
 * Create question operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates a Moodle question through Moodle question APIs.
 */
class create_question {
    /**
     * Execute the operation.
     *
     * @param int $categoryid Question category id.
     * @param string $questiontype Question type.
     * @param string $name Question name.
     * @param string $questiontext Question text.
     * @param array $options Type-specific options.
     * @return array
     */
    public static function execute(int $categoryid, string $questiontype, string $name, string $questiontext, array $options): array {
        return question_tools::save_question(null, $categoryid, $questiontype, $name, $questiontext, $options);
    }
}
