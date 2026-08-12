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
 * MoodlIA plugin implementation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Create a course from a portable MoodlIA blueprint.
 */
class create_course_from_blueprint {
    /**
     * Execute the operation.
     *
     * @param array $blueprint Blueprint.
     * @param callable|null $coursewritevalidator Coursewritevalidator.
     * @return array
     */
    public static function execute(array $blueprint, ?callable $coursewritevalidator = null): array {
        return course_workflow_tools::create_from_blueprint($blueprint, $coursewritevalidator);
    }
}
