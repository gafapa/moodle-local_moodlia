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
 * Set forum discussion favourite operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Favourites or unfavourites a Moodle forum discussion through Moodle forum APIs.
 */
class set_forum_discussion_favourite {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @param int $discussionid Moodle forum discussion id.
     * @param bool $favourite Target favourite state.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $discussionid, bool $favourite): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        forum_tools::get_raw_discussion($cm, $discussionid);

        $result = \mod_forum_external::toggle_favourite_state($discussionid, $favourite);
        $resultdata = is_array($result) ? $result : (array) $result;

        return [
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'forum_id' => (int) $cm->instance,
            'discussion_id' => (int) ($resultdata['id'] ?? $discussionid),
            'favourite' => self::normalise_favourite_state($resultdata, $favourite),
        ];
    }

    /**
     * Resolve the favourite state from Moodle response shapes.
     *
     * @param mixed $result Moodle external result.
     * @param bool $fallback Fallback state.
     * @return bool
     */
    private static function normalise_favourite_state($result, bool $fallback): bool {
        $result = is_array($result) ? $result : (array) $result;
        foreach (['favourite', 'favourited', 'starred', 'userstate.favourited'] as $key) {
            if (array_key_exists($key, $result)) {
                return (bool) $result[$key];
            }
        }

        $userstate = (array) ($result['userstate'] ?? []);
        foreach (['favourite', 'favourited', 'starred'] as $key) {
            if (array_key_exists($key, $userstate)) {
                return (bool) $userstate[$key];
            }
        }

        return $fallback;
    }
}
