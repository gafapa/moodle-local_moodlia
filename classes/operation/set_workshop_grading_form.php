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
 * Set Workshop grading form operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Creates or replaces a supported Workshop grading form through Moodle strategy APIs.
 */
class set_workshop_grading_form {
    /**
     * Execute the operation.
     *
     * @param int $courseid Moodle course id.
     * @param int $moduleid Workshop course module id.
     * @param string $strategy Workshop grading strategy.
     * @param string $definitionjson JSON definition.
     * @return array
     */
    public static function execute(int $courseid, int $moduleid, string $strategy, string $definitionjson): array {
        workshop_tools::require_workshop_api();

        $course = course_tools::get_course($courseid);
        $cm = workshop_tools::get_workshop_module($course, $moduleid);
        workshop_tools::prepare_page_context($course, $cm);
        $workshop = workshop_tools::get_workshop_object($course, $cm);
        $strategy = clean_param($strategy, PARAM_PLUGIN);

        if (!in_array($strategy, ['accumulative', 'comments', 'numerrors', 'rubric'], true)) {
            throw new \invalid_parameter_exception('strategy must be accumulative, comments, numerrors, or rubric.');
        }
        if ((string) $workshop->strategy !== $strategy) {
            throw new \invalid_parameter_exception('strategy must match the Workshop module strategy.');
        }
        if ((int) $workshop->phase !== \workshop::PHASE_SETUP) {
            throw new \invalid_parameter_exception('Workshop grading forms can only be changed in setup phase.');
        }

        $strategyinstance = $workshop->grading_strategy_instance();
        $existing = $strategyinstance->get_dimensions_info();
        if ($strategy === 'accumulative') {
            $dimensions = workshop_tools::decode_accumulative_definition($definitionjson);
            $formdata = workshop_tools::accumulative_edit_form_data($workshop, $dimensions, $existing);
        } else if ($strategy === 'comments') {
            $dimensions = workshop_tools::decode_comments_definition($definitionjson);
            $formdata = workshop_tools::comments_edit_form_data($workshop, $dimensions, $existing);
        } else if ($strategy === 'numerrors') {
            $definition = workshop_tools::decode_numerrors_definition($definitionjson);
            $formdata = workshop_tools::numerrors_edit_form_data($workshop, $definition, $existing);
        } else {
            $definition = workshop_tools::decode_rubric_definition($definitionjson);
            $existingrubric = workshop_tools::rubric_existing_dimensions($strategyinstance);
            $formdata = workshop_tools::rubric_edit_form_data($workshop, $definition, $existingrubric);
        }

        $strategyinstance->save_edit_strategy_form($formdata);

        $updatedworkshop = workshop_tools::get_workshop_object($course, $cm);
        $updatedstrategy = $updatedworkshop->grading_strategy_instance();
        $info = $updatedstrategy->get_dimensions_info();

        rebuild_course_cache($course->id, true);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'strategy' => $strategy,
            'updated' => true,
            'dimensions_count' => count($info),
            'dimensions_json' => workshop_tools::json_value(array_values($info)),
        ];
    }
}
