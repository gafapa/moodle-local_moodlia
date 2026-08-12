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
 * Set forum discussion pin operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Pins or unpins a Moodle forum discussion through Moodle forum APIs.
 */
class set_forum_discussion_pin {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Forum course module id.
     * @param int $discussionid Moodle forum discussion id.
     * @param bool $pinned Target pin state.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $discussionid, bool $pinned): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        forum_tools::get_raw_discussion($cm, $discussionid);

        $result = \mod_forum_external::set_pin_state($discussionid, (int) $pinned);
        $resultdata = is_array($result) ? $result : (array) $result;
        $discussion = forum_tools::get_raw_discussion($cm, $discussionid);

        return [
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'forum_id' => (int) $cm->instance,
            'discussion_id' => (int) ($resultdata['id'] ?? $discussionid),
            'pinned' => self::normalise_pinned_state($discussion, $resultdata, $pinned),
        ];
    }

    /**
     * Resolve the pin state from Moodle response shapes.
     *
     * @param array $discussion Raw discussion listing data.
     * @param mixed $result Moodle external result.
     * @param bool $fallback Fallback state.
     * @return bool
     */
    private static function normalise_pinned_state(array $discussion, $result, bool $fallback): bool {
        $result = is_array($result) ? $result : (array) $result;
        foreach (['pinned', 'discussionpinned'] as $key) {
            if (array_key_exists($key, $discussion)) {
                return (bool) $discussion[$key];
            }
            if (array_key_exists($key, $result)) {
                return (bool) $result[$key];
            }
        }

        return $fallback;
    }
}
