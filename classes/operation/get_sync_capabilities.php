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
            'question_view' => null,
            'question_manage' => null,
            'question_bank_module_available' => \core_component::get_plugin_directory('mod', 'qbank') !== null,
            'database_field_manage' => null,
            'feedback_item_manage' => null,
            'completion_manage' => null,
            'quiz_manage' => null,
            'lesson_manage' => null,
            'gradebook_manage' => null,
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
            $evidence['question_view'] = has_capability('moodle/question:viewall', $coursecontext);
            $evidence['question_manage'] = has_capability(
                'moodle/question:managecategory',
                $coursecontext
            ) && has_capability('moodle/question:add', $coursecontext);
            $evidence['database_field_manage'] = has_capability('mod/data:managetemplates', $coursecontext);
            $evidence['feedback_item_manage'] = has_capability('mod/feedback:edititems', $coursecontext);
            $evidence['completion_manage'] = has_capability('moodle/course:update', $coursecontext)
                && has_capability('moodle/grade:manage', $coursecontext);
            $evidence['quiz_manage'] = has_capability('mod/quiz:manage', $coursecontext);
            $evidence['lesson_manage'] = has_capability('mod/lesson:manage', $coursecontext);
            $evidence['gradebook_manage'] = has_capability('moodle/grade:manage', $coursecontext);
        }

        return [
            'course_id' => $courseid ?? 0,
            'category_id' => $categoryid ?? 0,
            'context_evaluated' => $courseid !== null || $categoryid !== null,
            'capabilities_json' => course_workflow_tools::encode_json($evidence),
        ];
    }
}
