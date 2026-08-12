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
 * Search glossary entries operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Searches Moodle glossary entries through Moodle external APIs.
 */
class search_glossary_entries {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $query Query.
     * @param bool $fullsearch Fullsearch.
     * @param string $order Order.
     * @param string $sort Sort.
     * @param int $from From.
     * @param int $limit Limit.
     * @param bool $includenotapproved Includenotapproved.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        string $query,
        bool $fullsearch = true,
        string $order = 'CONCEPT',
        string $sort = 'ASC',
        int $from = 0,
        int $limit = 20,
        bool $includenotapproved = false
    ): array {
        glossary_tools::require_glossary_api();

        $course = course_tools::get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $order = strtoupper(clean_param($order, PARAM_ALPHA));
        $sort = strtoupper(clean_param($sort, PARAM_ALPHA));

        $result = glossary_tools::search_entries($cm, $query, $fullsearch, $order, $sort, $from, $limit, $includenotapproved);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'glossary_id' => (int) $cm->instance,
            'count' => $result['count'],
            'entries' => $result['entries'],
        ];
    }
}
