<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Get Workshop grading form operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Exports the active Workshop grading form as a portable definition.
 */
class get_workshop_grading_form {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid): array {
        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        $workshop = workshop_tools::get_workshop_object($course, $cm);
        $definition = workshop_tools::export_grading_form_definition($workshop);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'strategy' => (string) $workshop->strategy,
            'phase' => (int) $workshop->phase,
            'dimensions_count' => count($definition['dimensions'] ?? []),
            'definition_json' => workshop_tools::json_value($definition),
        ];
    }
}
