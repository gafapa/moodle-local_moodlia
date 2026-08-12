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
 * Shared helpers for loading and resolving Moodle course modules.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

use core_course\local\factory\content_item_service_factory;

/**
 * Handles module API loading, lookup, and canonical response formatting.
 */
class module_lookup_tools {
    /**
     * Load Moodle module APIs.
     */
    public static function require_module_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        require_once($CFG->dirroot . '/mod/data/lib.php');
        require_once($CFG->dirroot . '/mod/feedback/lib.php');
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        require_once($CFG->dirroot . '/mod/glossary/lib.php');
        require_once($CFG->dirroot . '/mod/label/lib.php');
        require_once($CFG->dirroot . '/mod/lesson/lib.php');
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');
        require_once($CFG->dirroot . '/mod/lti/lib.php');
        require_once($CFG->dirroot . '/mod/lti/locallib.php');
        require_once($CFG->dirroot . '/mod/qbank/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/resource/lib.php');
        require_once($CFG->dirroot . '/mod/subsection/lib.php');
        require_once($CFG->dirroot . '/mod/url/lib.php');
        require_once($CFG->dirroot . '/mod/workshop/lib.php');
        require_once($CFG->dirroot . '/mod/workshop/locallib.php');
        require_once($CFG->dirroot . '/mod/wiki/lib.php');
        require_once($CFG->dirroot . '/mod/wiki/locallib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/resourcelib.php');
    }

    /**
     * Resolve a content item module id for an activity type.
     *
     * @param \stdClass $course Course.
     * @param string $modulename Modulename.
     * @return int
     */
    public static function resolve_content_item_id(\stdClass $course, string $modulename): int {
        global $USER;

        $service = content_item_service_factory::get_content_item_service();
        $items = $service->get_content_items_for_user_in_course($USER, $course);

        foreach ($items as $item) {
            if (($item->name ?? null) === $modulename && ($item->componentname ?? null) === 'mod_' . $modulename) {
                return (int) $item->id;
            }
        }

        throw new \moodle_exception('moduledisable', '', '', $modulename);
    }

    /**
     * Load a course module from a course context.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return \cm_info
     */
    public static function get_course_module(\stdClass $course, int $cmid): \cm_info {
        if ($cmid <= 0) {
            throw new \invalid_parameter_exception('module_id must be a positive integer.');
        }

        $cm = get_fast_modinfo($course)->get_cm($cmid);
        if (!$cm || (int) $cm->course !== (int) $course->id) {
            throw new \moodle_exception('invalidcoursemodule');
        }

        return $cm;
    }

    /**
     * Return the canonical module response shape.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return array
     */
    public static function to_response(\stdClass $course, int $cmid): array {
        rebuild_course_cache($course->id, true);
        $cm = get_fast_modinfo($course)->get_cm($cmid);

        return [
            'module_id' => (int) $cm->id,
            'course_module_id' => (int) $cm->id,
            'instance_id' => (int) $cm->instance,
            'name' => format_string($cm->name, true, ['context' => \context_module::instance($cm->id)]),
            'module_type' => (string) $cm->modname,
            'visible' => (bool) $cm->visible,
            'visible_on_course_page' => module_common_tools::is_visible_on_course_page($cm),
            'user_visible' => (bool) $cm->uservisible,
            'id_number' => (string) ($cm->idnumber ?? ''),
            'language' => (string) ($cm->lang ?? ''),
            'group_mode' => (int) ($cm->groupmode ?? 0),
            'grouping_id' => (int) ($cm->groupingid ?? 0),
            'availability' => (string) ($cm->availability ?? ''),
            'download_content' => (bool) ($cm->downloadcontent ?? false),
            'completion' => (int) ($cm->completion ?? 0),
            'completion_view' => (int) ($cm->completionview ?? 0),
            'completion_grade_item_number' => (int) ($cm->completiongradeitemnumber ?? -1),
            'completion_expected' => (int) ($cm->completionexpected ?? 0),
            'url' => $cm->url ? $cm->url->out(false) : '',
        ];
    }

    /**
     * Verify that a course module belongs to a quiz activity.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return \cm_info
     */
    public static function get_quiz_module(\stdClass $course, int $cmid): \cm_info {
        $cm = self::get_course_module($course, $cmid);
        if ($cm->modname !== 'quiz') {
            throw new \invalid_parameter_exception('quiz_module_id must reference a quiz activity.');
        }

        return $cm;
    }
}
