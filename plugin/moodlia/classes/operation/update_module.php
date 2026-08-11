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
 * Update module operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

use core_courseformat\formatactions;

/**
 * Updates a Moodle course module through Moodle core APIs.
 */
class update_module {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Course module id.
     * @param string|null $name New module name.
     * @param bool|null $visible Module visibility state.
     * @param array $options Type-specific options.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $moduleid,
        ?string $name = null,
        ?bool $visible = null,
        array $options = []
    ): array {
        module_tools::require_module_api();

        $course = course_tools::get_course($courseid);
        $cm = module_tools::get_course_module($course, $moduleid);

        if ($visible === null && array_key_exists('visible', $options)) {
            $visible = (bool) $options['visible'];
            unset($options['visible']);
        }

        $visibleoncoursepage = null;
        if (array_key_exists('visible_on_course_page', $options) || array_key_exists('visibleoncoursepage', $options)) {
            $visibleoncoursepage = (bool) ($options['visible_on_course_page'] ?? $options['visibleoncoursepage']);
            unset($options['visible_on_course_page'], $options['visibleoncoursepage']);
        }

        if ($name === null && $visible === null && $visibleoncoursepage === null && !$options) {
            throw new \invalid_parameter_exception('At least one of name, visible, visible_on_course_page, or options is required.');
        }

        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new \invalid_parameter_exception('name cannot be empty.');
            }

            formatactions::cm($course->id)->rename((int) $cm->id, $name);
        }

        if ($visible !== null || $visibleoncoursepage !== null) {
            $newvisible = $visible === null ? (bool) $cm->visible : $visible;
            $newvisibleoncoursepage = $visibleoncoursepage === null
                ? module_tools::is_visible_on_course_page($cm)
                : $visibleoncoursepage;
            set_coursemodule_visible((int) $cm->id, $newvisible ? 1 : 0, $newvisibleoncoursepage ? 1 : 0);
            rebuild_course_cache($course->id, true);
        }

        if ($options) {
            module_tools::apply_common_update_options($course, $cm, $options);
            rebuild_course_cache($course->id, true);
        }

        return module_tools::to_response($course, (int) $cm->id);
    }
}
