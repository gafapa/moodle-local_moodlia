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
 * Partial updates of existing quiz introductions.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Updates quiz introduction fields without processing the full quiz settings form.
 */
class quiz_intro_tools {
    /**
     * Validate introduction options before any module mutation.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param array $options Options.
     * @return \stdClass|null Partial quiz record, or null when no introduction was supplied.
     */
    public static function prepare_update(\stdClass $course, \cm_info $cm, array $options): ?\stdClass {
        global $DB;

        if (!$options) {
            return null;
        }
        if ($cm->modname !== 'quiz') {
            throw new \invalid_parameter_exception('update_module introduction options currently require a quiz activity.');
        }
        if (!array_key_exists('intro', $options) || !is_string($options['intro'])) {
            throw new \invalid_parameter_exception('options.intro must be a string, including an empty string to clear it.');
        }
        if (array_key_exists('intro_format', $options) && !is_string($options['intro_format'])) {
            throw new \invalid_parameter_exception('options.intro_format must be a supported text format name.');
        }
        require_capability('moodle/course:manageactivities', \context_module::instance((int) $cm->id));
        $quiz = $DB->get_record('quiz', [
            'id' => (int) $cm->instance,
            'course' => (int) $course->id,
        ], 'id', MUST_EXIST);
        $update = (object) ['id' => (int) $quiz->id, 'intro' => $options['intro']];
        if (array_key_exists('intro_format', $options)) {
            $update->introformat = text_format_tools::to_constant($options['intro_format'], 'intro_format');
        }

        return $update;
    }

    /**
     * Persist only the validated introduction fields and emit Moodle's module update event.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param \stdClass $update Update.
     */
    public static function apply_update(\stdClass $course, \cm_info $cm, \stdClass $update): void {
        global $DB;

        // A full quiz update processes review masks, feedback and access-rule settings,
        // and deletes previews. A text edit must not run those unrelated callbacks.
        $transaction = $DB->start_delegated_transaction();
        $update->timemodified = time();
        $DB->update_record('quiz', $update);
        rebuild_course_cache((int) $course->id, true);
        $updatedcm = get_coursemodule_from_id('quiz', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $updatedcm->name = $DB->get_field('quiz', 'name', ['id' => (int) $cm->instance], MUST_EXIST);
        \core\event\course_module_updated::create_from_cm(
            $updatedcm,
            \context_module::instance((int) $cm->id)
        )->trigger();
        $transaction->allow_commit();
    }
}
