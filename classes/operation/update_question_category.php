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
 * Update question category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates a Moodle question category through Moodle question APIs.
 */
class update_question_category {
    /**
     * Execute the operation.
     *
     * @param int $categoryid Categoryid.
     * @param string|null $name Name.
     * @param string|null $description Description.
     * @return array
     */
    public static function execute(int $categoryid, ?string $name = null, ?string $description = null): array {
        if ($name === null || trim($name) === '') {
            throw new \invalid_parameter_exception('name is required for the current update_question_category implementation.');
        }

        question_tools::category_manager()->update_category(
            $categoryid,
            '',
            trim($name),
            $description,
            FORMAT_HTML
        );

        return [
            'category_id' => $categoryid,
            'name' => trim($name),
        ];
    }
}
