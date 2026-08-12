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
 * Delete question category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Deletes a Moodle question category through Moodle question APIs.
 */
class delete_question_category {
    /**
     * Execute the operation.
     *
     * @param int $categoryid Categoryid.
     * @param string $deletemode Deletemode.
     * @return array
     */
    public static function execute(int $categoryid, string $deletemode = 'delete'): array {
        if ($deletemode !== 'delete') {
            throw new \invalid_parameter_exception('Only delete_mode=delete is currently supported.');
        }

        question_tools::category_manager()->delete_category($categoryid);

        return [
            'deleted' => true,
            'id' => $categoryid,
        ];
    }
}
