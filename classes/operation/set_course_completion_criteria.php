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
 * Course completion criteria operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Replaces an unlocked course's global completion criteria through Moodle APIs.
 */
class set_course_completion_criteria {
    /**
     * Execute the operation.
     *
     * @param int $courseid Courseid.
     * @param array $requiredmoduleids Requiredmoduleids.
     * @param bool $requireallactivities Requireallactivities.
     * @param float|null $requiredcoursegradepercent Requiredcoursegradepercent.
     * @param string $criteriaaggregation Criteriaaggregation.
     * @return array
     */
    public static function execute(
        int $courseid,
        array $requiredmoduleids = [],
        bool $requireallactivities = true,
        ?float $requiredcoursegradepercent = null,
        string $criteriaaggregation = 'all'
    ): array {
        global $DB;

        $course = course_tools::get_course($courseid);
        course_completion_tools::require_completion_api();
        gradebook_tools::require_gradebook_api();

        if (empty($course->enablecompletion)) {
            throw new \invalid_parameter_exception('Course completion must be enabled before global completion criteria can be configured.');
        }
        if ($requiredcoursegradepercent === null && empty($requiredmoduleids)) {
            throw new \invalid_parameter_exception('Configure at least one required module or a required course grade.');
        }

        // Validate before clearing existing criteria so an invalid value cannot alter the course.
        course_completion_tools::aggregation_method($criteriaaggregation);

        $completion = new \completion_info($course);
        if ($completion->is_course_locked()) {
            throw new \invalid_parameter_exception(
                'Course completion criteria are locked because completion records already exist. Unlock or reset completion data in Moodle before replacing the criteria.'
            );
        }

        $moduleids = self::validate_modules($course, $requiredmoduleids);
        $courseitem = \grade_item::fetch_course_item((int) $course->id);
        $requiredgrade = null;
        $transaction = $DB->start_delegated_transaction();
        if ($requiredcoursegradepercent !== null) {
            $requiredgrade = course_completion_tools::grade_from_percentage($courseitem, $requiredcoursegradepercent);
            $courseitem->gradepass = $requiredgrade;
            $courseitem->update('local_moodlia');
        }

        $completion->clear_criteria(false);

        foreach ($moduleids as $moduleid) {
            $coursemodule = get_coursemodule_from_id(null, $moduleid, (int) $course->id, false, MUST_EXIST);
            $criterion = \completion_criteria::factory([
                'course' => (int) $course->id,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
                'module' => (string) $coursemodule->modname,
                'moduleinstance' => (int) $coursemodule->id,
            ]);
            $criterion->insert();
        }

        if ($requiredgrade !== null) {
            $criterion = \completion_criteria::factory([
                'course' => (int) $course->id,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE,
                'gradepass' => $requiredgrade,
            ]);
            $criterion->insert();
        }

        course_completion_tools::set_aggregation((int) $course->id, null, $criteriaaggregation);
        course_completion_tools::set_aggregation(
            (int) $course->id,
            COMPLETION_CRITERIA_TYPE_ACTIVITY,
            $requireallactivities ? 'all' : 'any'
        );
        $transaction->allow_commit();

        return course_completion_tools::configuration_to_response($course);
    }

    /**
     * Validate and normalise the selected course modules.
     *
     * @param \stdClass $course Course.
     * @param array $moduleids Moduleids.
     * @return int[]
     */
    private static function validate_modules(\stdClass $course, array $moduleids): array {
        $normalised = [];
        foreach ($moduleids as $moduleid) {
            if (!is_int($moduleid) && !ctype_digit((string) $moduleid)) {
                throw new \invalid_parameter_exception('required_module_ids must contain Moodle course module ids.');
            }
            $moduleid = (int) $moduleid;
            if ($moduleid < 1) {
                throw new \invalid_parameter_exception('required_module_ids must contain positive Moodle course module ids.');
            }
            $normalised[$moduleid] = $moduleid;
        }

        foreach ($normalised as $moduleid) {
            $coursemodule = get_coursemodule_from_id(null, $moduleid, (int) $course->id, false, MUST_EXIST);
            if ((int) $coursemodule->completion === COMPLETION_TRACKING_NONE) {
                throw new \invalid_parameter_exception(
                    'Every required module must have activity completion tracking enabled before it can be a course completion criterion.'
                );
            }
        }

        return array_values($normalised);
    }
}
