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
 * Set forum discussion subscription operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Subscribes or unsubscribes the current user from a Moodle forum discussion.
 */
class set_forum_discussion_subscription {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $discussionid Discussionid.
     * @param bool $subscribed Subscribed.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $discussionid, bool $subscribed): array {
        module_tools::require_module_api();
        forum_tools::require_forum_api();

        $course = course_tools::get_course($courseid);
        $cm = forum_tools::get_forum_module($course, $moduleid);
        forum_tools::get_raw_discussion($cm, $discussionid);

        $result = \mod_forum_external::set_subscription_state((int) $cm->instance, $discussionid, $subscribed);
        $resultdata = is_array($result) ? $result : (array) $result;

        return [
            'course_id' => (int) $courseid,
            'module_id' => (int) $moduleid,
            'forum_id' => (int) $cm->instance,
            'discussion_id' => (int) ($resultdata['id'] ?? $discussionid),
            'subscribed' => self::normalise_subscription_state($resultdata, $subscribed),
        ];
    }

    /**
     * Resolve the subscription state from Moodle response shapes.
     *
     * @param mixed $result Result.
     * @param bool $fallback Fallback.
     * @return bool
     */
    private static function normalise_subscription_state($result, bool $fallback): bool {
        $result = is_array($result) ? $result : (array) $result;
        foreach (['subscribed', 'issubscribed', 'usersubscribed', 'userstate.subscribed'] as $key) {
            if (array_key_exists($key, $result)) {
                return (bool) $result[$key];
            }
        }

        $userstate = (array) ($result['userstate'] ?? []);
        foreach (['subscribed', 'issubscribed', 'usersubscribed'] as $key) {
            if (array_key_exists($key, $userstate)) {
                return (bool) $userstate[$key];
            }
        }

        return $fallback;
    }
}
