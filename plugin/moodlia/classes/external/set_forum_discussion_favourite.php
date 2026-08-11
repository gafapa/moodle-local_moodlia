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
 * Set forum discussion favourite external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\forum_tools;
use local_moodlia\operation\set_forum_discussion_favourite as set_forum_discussion_favourite_operation;

/**
 * External API adapter for set_forum_discussion_favourite.
 */
class set_forum_discussion_favourite extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'favourite' => new external_value(PARAM_BOOL, 'Target favourite state'),
        ]);
    }

    public static function execute(int $course_id, int $module_id, int $discussion_id, bool $favourite): array {
        [
            'course_id' => $courseid,
            'module_id' => $moduleid,
            'discussion_id' => $discussionid,
            'favourite' => $targetfavourite,
        ] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'module_id' => $module_id,
            'discussion_id' => $discussion_id,
            'favourite' => $favourite,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);

        $course = get_course($courseid);
        $cm = forum_tools::get_forum_module($course, (int) $moduleid);
        $modulecontext = \context_module::instance($cm->id);
        self::validate_context($modulecontext);
        require_capability('mod/forum:viewdiscussion', $modulecontext);

        return set_forum_discussion_favourite_operation::execute(
            (int) $courseid,
            (int) $moduleid,
            (int) $discussionid,
            (bool) $targetfavourite
        );
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'module_id' => new external_value(PARAM_INT, 'Forum course module id'),
            'forum_id' => new external_value(PARAM_INT, 'Forum instance id'),
            'discussion_id' => new external_value(PARAM_INT, 'Forum discussion id'),
            'favourite' => new external_value(PARAM_BOOL, 'Whether the current user has favourited the discussion'),
        ]);
    }
}
