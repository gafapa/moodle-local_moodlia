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
 * Set forum discussion lock external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\forum_tools;
use local_moodlia\operation\set_forum_discussion_lock as set_forum_discussion_lock_operation;

/**
 * External API adapter for set_forum_discussion_lock.
 */
class set_forum_discussion_lock extends external_api {
    /**
     * Execute parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'locked' => new external_value(PARAM_BOOL, 'Target locked state'),
        ]);
    }

    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $discussionid Discussionid.
     * @param bool $locked Locked.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, int $discussionid, bool $locked): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'discussion_id' => $discussionid,
            'locked' => $targetlocked,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'discussion_id' => $discussionid,
            'locked' => $locked,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        $course = get_course($courseid);
        $cm = forum_tools::get_forum_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('moodle/course:manageactivities', $modulecontext);

        return set_forum_discussion_lock_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $discussionid,
            (bool) $targetlocked
        );
    }

    /**
     * Execute returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'forum_id' => new external_value(PARAM_INT, 'Forum instance id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'locked' => new external_value(PARAM_BOOL, 'Whether the discussion is locked'),
            'lock_time' => new external_value(PARAM_INT, 'Discussion lock timestamp, or 0 when unlocked'),
        ]);
    }
}
