<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Synchronization capability discovery.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Return contextual evidence used by an external synchronization coordinator.
 */
class get_sync_capabilities {
    /**
     * Execute the operation.
     *
     * @param int|null $courseid Courseid.
     * @param int|null $categoryid Categoryid.
     * @return array
     */
    public static function execute(?int $courseid = null, ?int $categoryid = null): array {
        $systemcontext = \context_system::instance();
        $evidence = [
            'api_use' => has_capability('local/moodlia:useapi', $systemcontext),
            'course_view' => null,
            'course_create' => null,
            'course_update' => null,
            'group_manage' => null,
            'book_edit' => null,
            'activity_manage' => null,
            'assignment_grade' => null,
            'grading_form_manage' => null,
            'workshop_form_manage' => null,
        ];

        if ($categoryid !== null) {
            $categorycontext = \context_coursecat::instance($categoryid);
            $evidence['course_create'] = has_capability('moodle/course:create', $categorycontext);
        }

        if ($courseid !== null) {
            $coursecontext = \context_course::instance($courseid);
            $evidence['course_view'] = has_capability('moodle/course:view', $coursecontext);
            $evidence['course_update'] = has_capability('moodle/course:update', $coursecontext);
            $evidence['group_manage'] = has_capability('moodle/course:managegroups', $coursecontext);
            $evidence['book_edit'] = has_capability('mod/book:edit', $coursecontext);
            $evidence['activity_manage'] = has_capability('moodle/course:manageactivities', $coursecontext);
            $evidence['assignment_grade'] = has_capability('mod/assign:grade', $coursecontext);
            $evidence['grading_form_manage'] = has_capability('moodle/grade:managegradingforms', $coursecontext);
            $evidence['workshop_form_manage'] = has_capability('mod/workshop:editdimensions', $coursecontext);
        }

        return [
            'course_id' => $courseid ?? 0,
            'category_id' => $categoryid ?? 0,
            'context_evaluated' => $courseid !== null || $categoryid !== null,
            'capabilities_json' => course_workflow_tools::encode_json($evidence),
        ];
    }
}
