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
 * Set assignment marking guide operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates or updates an assignment marking guide.
 */
class set_assignment_marking_guide {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $criteria Criteria.
     * @param string $comments Comments.
     * @param string $options Options.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $name,
        string $description,
        string $criteria,
        string $comments = '{}',
        string $options = '{}'
    ): array {
        return assignment_grading_tools::set_marking_guide(
            $courseid,
            $moduleid,
            $name,
            $description,
            $criteria,
            $comments,
            $options
        );
    }
}
