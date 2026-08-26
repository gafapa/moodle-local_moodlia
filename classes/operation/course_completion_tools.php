<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course completion configuration helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Reads and writes Moodle course completion configuration through core APIs.
 */
class course_completion_tools {
    /**
     * Load the Moodle completion API.
     */
    public static function require_completion_api(): void {
        global $CFG;

        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
        require_once($CFG->dirroot . '/completion/completion_aggregation.php');
    }

    /**
     * Convert a percentage to a grade in the course-total range.
     *
     * @param \grade_item $item Item.
     * @param float $percentage Percentage.
     * @return float
     */
    public static function grade_from_percentage(\grade_item $item, float $percentage): float {
        if ($percentage < 0 || $percentage > 100) {
            throw new \invalid_parameter_exception('grade_pass_percent must be between 0 and 100.');
        }

        return (float) $item->grademin + (((float) $item->grademax - (float) $item->grademin) * ($percentage / 100));
    }

    /**
     * Convert a course-total grade to a percentage.
     *
     * @param \grade_item $item Item.
     * @param float $grade Grade.
     * @return float
     */
    public static function percentage_from_grade(\grade_item $item, float $grade): float {
        $range = (float) $item->grademax - (float) $item->grademin;
        if ($range <= 0) {
            return 0.0;
        }

        return (($grade - (float) $item->grademin) / $range) * 100;
    }

    /**
     * Return a normalised completion configuration.
     *
     * @param \stdClass $course Course.
     * @return array
     */
    public static function configuration_to_response(\stdClass $course): array {
        self::require_completion_api();
        gradebook_tools::require_gradebook_api();

        $completion = new \completion_info($course);
        $courseitem = \grade_item::fetch_course_item((int) $course->id);
        $moduleids = [];
        $gradecriterionpass = null;

        foreach ($completion->get_criteria() as $criterion) {
            if ($criterion->criteriatype === COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                $moduleids[] = (int) $criterion->moduleinstance;
            }
            if ($criterion->criteriatype === COMPLETION_CRITERIA_TYPE_GRADE) {
                $gradecriterionpass = (float) $criterion->gradepass;
            }
        }

        sort($moduleids, SORT_NUMERIC);
        $criteriaaggregation = self::aggregation_name($completion->get_aggregation_method());
        $activityaggregation = self::aggregation_name(
            $completion->get_aggregation_method(COMPLETION_CRITERIA_TYPE_ACTIVITY)
        );

        return [
            'course_id' => (int) $course->id,
            'course_completion_enabled' => (bool) ($course->enablecompletion ?? false),
            'criteria_locked' => $completion->is_course_locked(),
            'criteria_aggregation' => $criteriaaggregation,
            'activity_aggregation' => $activityaggregation,
            'required_module_ids' => $moduleids,
            'criteria_count' => count($completion->get_criteria()),
            'grade_criterion_enabled' => $gradecriterionpass !== null,
            'required_course_grade' => $gradecriterionpass ?? 0.0,
            'required_course_grade_percent' => $gradecriterionpass === null
                ? 0.0
                : self::percentage_from_grade($courseitem, $gradecriterionpass),
            'course_grade_pass' => (float) $courseitem->gradepass,
            'course_grade_pass_percent' => self::percentage_from_grade($courseitem, (float) $courseitem->gradepass),
            'course_grade_min' => (float) $courseitem->grademin,
            'course_grade_max' => (float) $courseitem->grademax,
        ];
    }

    /**
     * Set an aggregation method for a course or criterion type.
     *
     * @param int $courseid Courseid.
     * @param int|null $criteriatype Criteriatype.
     * @param string $aggregation Aggregation.
     */
    public static function set_aggregation(int $courseid, ?int $criteriatype, string $aggregation): void {
        self::require_completion_api();

        $method = self::aggregation_method($aggregation);
        $record = new \completion_aggregation([
            'course' => $courseid,
            'criteriatype' => $criteriatype,
        ]);
        $record->course = $courseid;
        $record->criteriatype = $criteriatype;
        $record->value = null;
        $record->setMethod($method);
        $record->save();
    }

    /**
     * Resolve a public aggregation name.
     *
     * @param string $aggregation Aggregation.
     * @return int
     */
    public static function aggregation_method(string $aggregation): int {
        return match ($aggregation) {
            'all' => COMPLETION_AGGREGATION_ALL,
            'any' => COMPLETION_AGGREGATION_ANY,
            default => throw new \invalid_parameter_exception('criteria_aggregation must be all or any.'),
        };
    }

    /**
     * Convert a Moodle aggregation constant to its public name.
     *
     * @param int $method Method.
     * @return string
     */
    private static function aggregation_name(int $method): string {
        return $method === COMPLETION_AGGREGATION_ANY ? 'any' : 'all';
    }
}
