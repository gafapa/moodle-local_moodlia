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
 * Get Glossary authors operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Lists Glossary authors through Moodle Glossary external APIs.
 */
class get_glossary_authors {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $from From.
     * @param int $limit Limit.
     * @param bool $includenotapproved Includenotapproved.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        int $from = 0,
        int $limit = 20,
        bool $includenotapproved = false
    ): array {
        global $PAGE;

        glossary_tools::require_glossary_api();

        $course = course_tools::get_course($courseid);
        $cm = glossary_tools::get_glossary_module($course, $moduleid);
        $context = \context_module::instance((int) $cm->id);
        \core_external\external_api::validate_context($context);
        require_capability('mod/glossary:view', $context);
        // Core's external exporter leaves $authors undefined for empty pages.
        // Its native query retains approval, ownership, and imported-entry filtering.
        [$users, $count] = glossary_get_authors(
            (object) ['id' => (int) $cm->instance],
            $context,
            max(1, $limit),
            max(0, $from),
            ['includenotapproved' => $includenotapproved]
        );
        $authors = [];
        try {
            foreach ($users as $user) {
                $picture = new \user_picture($user);
                $picture->size = 1;
                $authors[] = (object) [
                    'id' => (int) $user->id,
                    'fullname' => fullname($user, has_capability('moodle/site:viewfullnames', $context)),
                    'pictureurl' => $picture->get_url($PAGE)->out(false),
                ];
            }
        } finally {
            $users->close();
        }
        $result = ['count' => $count, 'authors' => $authors, 'warnings' => []];

        return glossary_tools::authors_to_response($cm, $result);
    }
}
