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
 * Course completion audit helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Audits and repairs course-module completion settings through Moodle APIs.
 */
class completion_audit_tools {
    /** @var array Supported repair modes. */
    public const REPAIR_MODES = ['book_view_only', 'all_grade_to_view', 'disable_all'];

    /**
     * Audit course-module completion settings.
     *
     * @param int $courseid Moodle course id.
     * @param bool $includeok Include non-issue module rows.
     * @return array
     */
    public static function audit(int $courseid, bool $includeok = false): array {
        completion_tools::require_completion_api();

        $course = course_tools::get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $issues = [];
        $ok = [];

        foreach ($modinfo->get_cms() as $cm) {
            $rowissues = self::module_issues($course, $cm);
            if ($rowissues) {
                foreach ($rowissues as $issue) {
                    $issues[] = $issue;
                }
            } else if ($includeok) {
                $ok[] = self::module_row($cm, 'ok', 'Module completion settings look consistent.');
            }
        }

        return [
            'course_id' => (int) $course->id,
            'issue_count' => count($issues),
            'repairable_count' => count(array_filter($issues, static fn($issue) => (bool) ($issue['repairable'] ?? false))),
            'issues' => $issues,
            'ok' => $ok,
        ];
    }

    /**
     * Repair course-module completion settings using a conservative mode.
     *
     * @param int $courseid Moodle course id.
     * @param string $mode Repair mode.
     * @param bool $dryrun Whether to only report changes.
     * @param bool $resetstates Whether Moodle should reset existing completion states.
     * @return array
     */
    public static function repair(int $courseid, string $mode, bool $dryrun, bool $resetstates): array {
        completion_tools::require_completion_api();

        if (!in_array($mode, self::REPAIR_MODES, true)) {
            throw new \invalid_parameter_exception('mode must be one of: ' . implode(', ', self::REPAIR_MODES) . '.');
        }

        $course = course_tools::get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $changes = [];
        $warnings = [];

        foreach ($modinfo->get_cms() as $cm) {
            $options = self::repair_options($cm, $mode, $resetstates);
            if (!$options) {
                continue;
            }

            $change = [
                'module_id' => (int) $cm->id,
                'module_type' => (string) $cm->modname,
                'name' => format_string($cm->name, true, ['context' => \context_module::instance($cm->id)]),
                'mode' => $mode,
                'dry_run' => $dryrun,
                'options' => $options,
            ];

            if (!$dryrun) {
                try {
                    $updated = update_module::execute((int) $course->id, (int) $cm->id, null, null, $options);
                    $change['updated'] = true;
                    $change['completion'] = (int) $updated['completion'];
                    $change['completion_view'] = (int) $updated['completion_view'];
                    $change['completion_grade_item_number'] = (int) $updated['completion_grade_item_number'];
                } catch (\Throwable $error) {
                    $change['updated'] = false;
                    $warnings[] = [
                        'module_id' => (int) $cm->id,
                        'module_type' => (string) $cm->modname,
                        'message' => $error->getMessage(),
                    ];
                }
            }

            $changes[] = $change;
        }

        return [
            'course_id' => (int) $course->id,
            'mode' => $mode,
            'dry_run' => $dryrun,
            'changed_count' => count($changes),
            'warning_count' => count($warnings),
            'changes' => $changes,
            'warnings' => $warnings,
        ];
    }

    /**
     * Return issues for one module.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Course module.
     * @return array
     */
    private static function module_issues(\stdClass $course, \cm_info $cm): array {
        $issues = [];
        $tracking = (int) ($cm->completion ?? COMPLETION_TRACKING_NONE);
        $view = (int) ($cm->completionview ?? 0);
        $gradeitem = (int) ($cm->completiongradeitemnumber ?? -1);

        if ($tracking > COMPLETION_TRACKING_NONE && empty($course->enablecompletion)) {
            $issues[] = self::module_row(
                $cm,
                'course_completion_disabled',
                'The activity tracks completion but course completion is disabled.',
                true
            );
        }

        if ($tracking === COMPLETION_TRACKING_AUTOMATIC && $gradeitem >= 0 && $cm->modname === 'book') {
            $issues[] = self::module_row(
                $cm,
                'book_grade_completion',
                'Book activity has Moodle grade-based completion enabled.',
                true
            );
        }

        if ($tracking === COMPLETION_TRACKING_AUTOMATIC && $gradeitem >= 0 && $view > 0) {
            $issues[] = self::module_row(
                $cm,
                'view_and_grade_completion',
                'Activity requires both viewing and a grade for completion.',
                true
            );
        }

        if ($tracking === COMPLETION_TRACKING_AUTOMATIC && $gradeitem < 0 && $view === 0 && !self::has_custom_completion_rules($cm)) {
            $issues[] = self::module_row(
                $cm,
                'automatic_without_visible_rule',
                'Activity uses automatic completion without a visible view, grade, or custom completion rule.',
                false
            );
        }

        return $issues;
    }

    /**
     * Return repair options for one module.
     *
     * @param \cm_info $cm Course module.
     * @param string $mode Repair mode.
     * @param bool $resetstates Reset completion states.
     * @return array
     */
    private static function repair_options(\cm_info $cm, string $mode, bool $resetstates): array {
        $gradeitem = (int) ($cm->completiongradeitemnumber ?? -1);
        $tracking = (int) ($cm->completion ?? COMPLETION_TRACKING_NONE);

        if ($mode === 'disable_all' && $tracking !== COMPLETION_TRACKING_NONE) {
            return [
                'completion_tracking' => 'none',
                'reset_completion_states' => $resetstates,
            ];
        }

        if ($mode === 'book_view_only' && $cm->modname === 'book' && $gradeitem >= 0) {
            return self::view_only_options($resetstates);
        }

        if ($mode === 'all_grade_to_view' && $gradeitem >= 0) {
            return self::view_only_options($resetstates);
        }

        return [];
    }

    /**
     * Return options that keep automatic view completion and clear grade flags.
     *
     * @param bool $resetstates Reset completion states.
     * @return array
     */
    private static function view_only_options(bool $resetstates): array {
        return [
            'completion_tracking' => 'automatic',
            'completion_view_required' => true,
            'completion_use_grade' => false,
            'completion_pass_grade' => false,
            'completion_grade_item_number' => -1,
            'reset_completion_states' => $resetstates,
        ];
    }

    /**
     * Return whether Moodle exposes custom completion rules for this module.
     *
     * @param \cm_info $cm Course module.
     * @return bool
     */
    private static function has_custom_completion_rules(\cm_info $cm): bool {
        $customdata = $cm->customdata ?? null;
        if (!is_object($customdata) || !method_exists($customdata, 'get')) {
            return false;
        }

        $rules = $customdata->get('customcompletionrules');
        return is_array($rules) && !empty($rules);
    }

    /**
     * Return a canonical audit row.
     *
     * @param \cm_info $cm Course module.
     * @param string $code Issue code.
     * @param string $message Human-readable message.
     * @param bool $repairable Whether the issue can be repaired automatically.
     * @return array
     */
    private static function module_row(\cm_info $cm, string $code, string $message, bool $repairable = false): array {
        return [
            'module_id' => (int) $cm->id,
            'module_type' => (string) $cm->modname,
            'name' => format_string($cm->name, true, ['context' => \context_module::instance($cm->id)]),
            'code' => $code,
            'message' => $message,
            'repairable' => $repairable,
            'completion' => (int) ($cm->completion ?? 0),
            'completion_view' => (int) ($cm->completionview ?? 0),
            'completion_grade_item_number' => (int) ($cm->completiongradeitemnumber ?? -1),
            'completion_expected' => (int) ($cm->completionexpected ?? 0),
        ];
    }
}
