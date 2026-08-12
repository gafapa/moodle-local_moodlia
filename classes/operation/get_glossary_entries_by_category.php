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
 * Get Glossary entries by category operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists Glossary entries by category through Moodle Glossary external APIs.
 */
class get_glossary_entries_by_category {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $categoryid Categoryid.
     * @param int $from From.
     * @param int $limit Limit.
     * @param bool $includenotapproved Includenotapproved.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $categoryid = 0,
        int $from = 0,
        int $limit = 20,
        bool $includenotapproved = false
    ): array {
        glossary_tools::require_glossary_api();

        $course = course_tools::get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $result = \mod_glossary_external::get_entries_by_category(
            (int) $cm->instance,
            $categoryid,
            max(0, $from),
            max(1, $limit),
            ['includenotapproved' => $includenotapproved]
        );

        return glossary_tools::entries_result_to_response($cm, $result) + ['category_id' => $categoryid];
    }
}
