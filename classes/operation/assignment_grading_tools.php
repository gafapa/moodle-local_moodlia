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
 * Shared assignment advanced grading helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Handles Moodle Assignment advanced grading through Moodle grading controllers.
 */
class assignment_grading_tools {
    /**
     * Load Moodle advanced grading APIs.
     */
    public static function require_grading_api(): void {
        global $CFG;

        assignment_tools::require_assignment_api();
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        require_once($CFG->dirroot . '/grade/grading/form/lib.php');
        require_once($CFG->dirroot . '/grade/grading/form/rubric/lib.php');
        require_once($CFG->dirroot . '/grade/grading/form/guide/lib.php');
        require_once($CFG->libdir . '/gradelib.php');
    }

    /**
     * Return the current grading form definition.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    public static function get_form(int $courseid, int $moduleid): array {
        self::require_grading_api();
        [$course, $cm] = self::get_course_and_assignment_module($courseid, $moduleid);
        $manager = self::get_manager($cm);
        $method = (string) ($manager->get_active_method() ?? '');

        if ($method === '' || !in_array($method, ['rubric', 'guide'], true)) {
            return self::empty_response($course, $cm, $method);
        }

        $controller = self::get_controller($course, $cm, $method);
        return self::definition_response($course, $cm, $method, $controller);
    }

    /**
     * Create or update a rubric definition and activate it.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $criteriajson Criteriajson.
     * @param string $optionsjson Optionsjson.
     * @return array
     */
    public static function set_rubric(
        int $courseid,
        int $moduleid,
        string $name,
        string $description,
        string $criteriajson,
        string $optionsjson = '{}'
    ): array {
        self::require_grading_api();
        [$course, $cm] = self::get_course_and_assignment_module($courseid, $moduleid);
        $criteria = self::decode_list_payload($criteriajson, 'criteria', 'criteria');
        $options = self::decode_object($optionsjson, 'options');

        $manager = self::get_manager($cm);
        $manager->set_active_method('rubric');
        $controller = self::get_controller($course, $cm, 'rubric');

        $definition = self::base_definition($name, $description);
        $definition->rubric = [
            'criteria' => self::rubric_criteria_for_update($criteria),
            'options' => array_merge(\gradingform_rubric_controller::get_default_options(), $options),
        ];
        $controller->update_definition($definition);

        return self::definition_response($course, $cm, 'rubric', self::get_controller($course, $cm, 'rubric'));
    }

    /**
     * Create a binary checklist as a Moodle rubric and activate it.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $itemsjson Itemsjson.
     * @return array
     */
    public static function set_checklist(
        int $courseid,
        int $moduleid,
        string $name,
        string $description,
        string $itemsjson
    ): array {
        $items = self::decode_list_payload($itemsjson, 'items', 'items');
        $criteria = [];
        foreach ($items as $index => $item) {
            $item = self::ensure_array($item, "items[$index]");
            $score = array_key_exists('score', $item) ? (float) $item['score'] : 1.0;
            if ($score <= 0) {
                throw new \invalid_parameter_exception("items[$index].score must be greater than zero.");
            }
            $criteria[] = [
                'criterion_id' => (int) ($item['criterion_id'] ?? 0),
                'description' => (string) ($item['description'] ?? ''),
                'sort_order' => (int) ($item['sort_order'] ?? ($index + 1)),
                'levels' => [
                    ['definition' => 'Not met', 'score' => 0],
                    ['definition' => 'Met', 'score' => $score],
                ],
            ];
        }

        return self::set_rubric($courseid, $moduleid, $name, $description, json_encode(['criteria' => $criteria]));
    }

    /**
     * Create or update a marking guide definition and activate it.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $name Name.
     * @param string $description Description.
     * @param string $criteriajson Criteriajson.
     * @param string $commentsjson Commentsjson.
     * @param string $optionsjson Optionsjson.
     * @return array
     */
    public static function set_marking_guide(
        int $courseid,
        int $moduleid,
        string $name,
        string $description,
        string $criteriajson,
        string $commentsjson = '{}',
        string $optionsjson = '{}'
    ): array {
        self::require_grading_api();
        [$course, $cm] = self::get_course_and_assignment_module($courseid, $moduleid);
        $criteria = self::decode_list_payload($criteriajson, 'criteria', 'criteria');
        $comments = self::decode_list_payload($commentsjson, 'comments', 'comments', true);
        $options = self::decode_object($optionsjson, 'options');

        $manager = self::get_manager($cm);
        $manager->set_active_method('guide');
        $controller = self::get_controller($course, $cm, 'guide');

        $definition = self::base_definition($name, $description);
        $definition->guide = [
            'criteria' => self::guide_criteria_for_update($criteria),
            'comments' => self::guide_comments_for_update($comments),
            'options' => array_merge(\gradingform_guide_controller::get_default_options(), $options),
        ];
        $controller->update_definition($definition);

        return self::definition_response($course, $cm, 'guide', self::get_controller($course, $cm, 'guide'));
    }

    /**
     * Grade an assignment using the active rubric.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @param string $gradesjson Gradesjson.
     * @param string $feedbackcomment Feedbackcomment.
     * @param int $attemptnumber Attemptnumber.
     * @return array
     */
    public static function grade_with_rubric(
        int $courseid,
        int $moduleid,
        int $userid,
        string $gradesjson,
        string $feedbackcomment = '',
        int $attemptnumber = -1
    ): array {
        self::require_active_method($courseid, $moduleid, 'rubric');
        $criteria = self::decode_list_payload($gradesjson, 'criteria', 'criteria');
        $advanced = ['rubric' => ['criteria' => self::rubric_fillings_for_save($criteria)]];

        return self::save_advanced_grade($courseid, $moduleid, $userid, $advanced, $feedbackcomment, $attemptnumber);
    }

    /**
     * Grade an assignment checklist generated as a binary rubric.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @param string $itemsjson Itemsjson.
     * @param string $feedbackcomment Feedbackcomment.
     * @param int $attemptnumber Attemptnumber.
     * @return array
     */
    public static function grade_with_checklist(
        int $courseid,
        int $moduleid,
        int $userid,
        string $itemsjson,
        string $feedbackcomment = '',
        int $attemptnumber = -1
    ): array {
        $form = self::require_active_method($courseid, $moduleid, 'rubric');
        $items = self::decode_list_payload($itemsjson, 'items', 'items');
        $criteria = [];

        foreach ($items as $index => $item) {
            $item = self::ensure_array($item, "items[$index]");
            $criterionid = (int) ($item['criterion_id'] ?? 0);
            if ($criterionid <= 0) {
                throw new \invalid_parameter_exception("items[$index].criterion_id must be a positive integer.");
            }
            $levels = self::levels_for_criterion($form, $criterionid);
            $criteria[] = [
                'criterion_id' => $criterionid,
                'level_id' => !empty($item['checked']) ? end($levels)['level_id'] : reset($levels)['level_id'],
                'remark' => (string) ($item['remark'] ?? ''),
            ];
        }

        $advanced = ['rubric' => ['criteria' => self::rubric_fillings_for_save($criteria)]];
        return self::save_advanced_grade($courseid, $moduleid, $userid, $advanced, $feedbackcomment, $attemptnumber);
    }

    /**
     * Grade an assignment using the active marking guide.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @param string $gradesjson Gradesjson.
     * @param string $feedbackcomment Feedbackcomment.
     * @param int $attemptnumber Attemptnumber.
     * @return array
     */
    public static function grade_with_marking_guide(
        int $courseid,
        int $moduleid,
        int $userid,
        string $gradesjson,
        string $feedbackcomment = '',
        int $attemptnumber = -1
    ): array {
        self::require_active_method($courseid, $moduleid, 'guide');
        $criteria = self::decode_list_payload($gradesjson, 'criteria', 'criteria');
        $advanced = ['guide' => ['criteria' => self::guide_fillings_for_save($criteria)]];

        return self::save_advanced_grade($courseid, $moduleid, $userid, $advanced, $feedbackcomment, $attemptnumber);
    }

    /**
     * Return Moodle course and assignment module.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @return array
     */
    private static function get_course_and_assignment_module(int $courseid, int $moduleid): array {
        $course = course_tools::get_course($courseid);
        return [$course, assignment_tools::get_assignment_module($course, $moduleid)];
    }

    /**
     * Return the advanced grading manager for assignment submissions.
     *
     * @param \cm_info $cm Cm.
     * @return \grading_manager
     */
    private static function get_manager(\cm_info $cm): \grading_manager {
        $context = \context_module::instance($cm->id);
        return get_grading_manager($context, 'mod_assign', 'submissions');
    }

    /**
     * Return a configured grading controller.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param string $method Method.
     * @return \gradingform_controller
     */
    private static function get_controller(\stdClass $course, \cm_info $cm, string $method): \gradingform_controller {
        $controller = self::get_manager($cm)->get_controller($method);
        $assignment = new \assign(\context_module::instance($cm->id), $cm, $course);
        $maxgrade = (float) ($assignment->get_instance()->grade ?? 100);
        $controller->set_grade_range(make_grades_menu($maxgrade), $maxgrade > 0);
        return $controller;
    }

    /**
     * Require an active advanced grading method and return its form response.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param string $method Method.
     * @return array
     */
    private static function require_active_method(int $courseid, int $moduleid, string $method): array {
        $form = self::get_form($courseid, $moduleid);
        if ($form['active_method'] !== $method || !$form['supported'] || $form['definition_id'] <= 0) {
            throw new \invalid_parameter_exception("Assignment must have an active $method grading form.");
        }
        return $form;
    }

    /**
     * Save an advanced grade through Moodle's Assignment external API.
     *
     * @param int $courseid Courseid.
     * @param int $moduleid Moduleid.
     * @param int $userid Userid.
     * @param array $advancedgradingdata Advancedgradingdata.
     * @param string $feedbackcomment Feedbackcomment.
     * @param int $attemptnumber Attemptnumber.
     * @return array
     */
    private static function save_advanced_grade(
        int $courseid,
        int $moduleid,
        int $userid,
        array $advancedgradingdata,
        string $feedbackcomment,
        int $attemptnumber
    ): array {
        if ($userid <= 0) {
            throw new \invalid_parameter_exception('user_id must be a positive integer.');
        }

        [$course, $cm] = self::get_course_and_assignment_module($courseid, $moduleid);
        \mod_assign_external::save_grade(
            (int) $cm->instance,
            $userid,
            0,
            $attemptnumber,
            false,
            'released',
            false,
            [
                'assignfeedbackcomments_editor' => [
                    'text' => $feedbackcomment,
                    'format' => FORMAT_HTML,
                ],
            ],
            $advancedgradingdata
        );

        return assignment_tools::get_submission_status($course, $cm, $userid);
    }

    /**
     * Return a response when the active method is absent or unsupported.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param string $method Method.
     * @return array
     */
    private static function empty_response(\stdClass $course, \cm_info $cm, string $method): array {
        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'assignment_id' => (int) $cm->instance,
            'active_method' => $method,
            'supported' => false,
            'definition_id' => 0,
            'name' => '',
            'description' => '',
            'status' => 0,
            'criteria' => [],
            'comments' => [],
            'checklist_compatible' => false,
        ];
    }

    /**
     * Convert a grading definition to the canonical response.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param string $method Method.
     * @param \gradingform_controller $controller Controller.
     * @return array
     */
    private static function definition_response(
        \stdClass $course,
        \cm_info $cm,
        string $method,
        \gradingform_controller $controller
    ): array {
        $definition = $controller->get_definition(true);
        if (!$definition) {
            return self::empty_response($course, $cm, $method);
        }

        $criteria = $method === 'rubric'
            ? self::rubric_criteria_to_response((array) ($definition->rubric_criteria ?? []))
            : self::guide_criteria_to_response((array) ($definition->guide_criteria ?? []));

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'assignment_id' => (int) $cm->instance,
            'active_method' => $method,
            'supported' => true,
            'definition_id' => (int) ($definition->id ?? 0),
            'name' => (string) ($definition->name ?? ''),
            'description' => (string) ($definition->description ?? ''),
            'status' => (int) ($definition->status ?? 0),
            'criteria' => $criteria,
            'comments' => $method === 'guide'
                ? self::guide_comments_to_response((array) ($definition->guide_comments ?? []))
                : [],
            'checklist_compatible' => $method === 'rubric' && self::is_binary_rubric($criteria),
        ];
    }

    /**
     * Create a base Moodle grading definition object.
     *
     * @param string $name Name.
     * @param string $description Description.
     * @return \stdClass
     */
    private static function base_definition(string $name, string $description): \stdClass {
        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        return (object) [
            'name' => $name,
            'description_editor' => [
                'text' => $description,
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ],
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'status' => \gradingform_controller::DEFINITION_STATUS_READY,
        ];
    }

    /**
     * Convert rubric criteria for controller update.
     *
     * @param array $criteria Criteria.
     * @return array
     */
    private static function rubric_criteria_for_update(array $criteria): array {
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('criteria must contain at least one rubric criterion.');
        }

        $mapped = [];
        foreach ($criteria as $index => $criterion) {
            $criterion = self::ensure_array($criterion, "criteria[$index]");
            $key = self::record_key($criterion['criterion_id'] ?? null, $index);
            $levels = self::ensure_list($criterion['levels'] ?? null, "criteria[$index].levels");
            if (count($levels) < 2) {
                throw new \invalid_parameter_exception("criteria[$index].levels must contain at least two levels.");
            }

            $mappedlevels = [];
            foreach ($levels as $levelindex => $level) {
                $level = self::ensure_array($level, "criteria[$index].levels[$levelindex]");
                $mappedlevels[self::record_key($level['level_id'] ?? null, $levelindex)] = [
                    'score' => (float) ($level['score'] ?? 0),
                    'definition' => trim((string) ($level['definition'] ?? '')),
                    'definitionformat' => FORMAT_MOODLE,
                ];
            }

            $mapped[$key] = [
                'sortorder' => (int) ($criterion['sort_order'] ?? ($index + 1)),
                'description' => trim((string) ($criterion['description'] ?? '')),
                'descriptionformat' => FORMAT_MOODLE,
                'levels' => $mappedlevels,
            ];
        }

        return $mapped;
    }

    /**
     * Convert guide criteria for controller update.
     *
     * @param array $criteria Criteria.
     * @return array
     */
    private static function guide_criteria_for_update(array $criteria): array {
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('criteria must contain at least one guide criterion.');
        }

        $mapped = [];
        foreach ($criteria as $index => $criterion) {
            $criterion = self::ensure_array($criterion, "criteria[$index]");
            $maxscore = (float) ($criterion['max_score'] ?? 0);
            if ($maxscore <= 0) {
                throw new \invalid_parameter_exception("criteria[$index].max_score must be greater than zero.");
            }
            $description = trim((string) ($criterion['description'] ?? ''));
            $mapped[self::record_key($criterion['criterion_id'] ?? null, $index)] = [
                'sortorder' => (int) ($criterion['sort_order'] ?? ($index + 1)),
                'shortname' => trim((string) ($criterion['shortname'] ?? $description)),
                'description' => $description,
                'descriptionformat' => FORMAT_MOODLE,
                'descriptionmarkers' => (string) ($criterion['description_markers'] ?? ''),
                'descriptionmarkersformat' => FORMAT_MOODLE,
                'maxscore' => $maxscore,
            ];
        }

        return $mapped;
    }

    /**
     * Convert reusable guide comments for controller update.
     *
     * @param array $comments Comments.
     * @return array
     */
    private static function guide_comments_for_update(array $comments): array {
        $mapped = [];
        foreach ($comments as $index => $comment) {
            $comment = self::ensure_array($comment, "comments[$index]");
            $description = trim((string) ($comment['description'] ?? ''));
            if ($description === '') {
                continue;
            }
            $mapped[self::record_key($comment['comment_id'] ?? null, $index)] = [
                'sortorder' => (int) ($comment['sort_order'] ?? ($index + 1)),
                'description' => $description,
            ];
        }

        return $mapped;
    }

    /**
     * Convert rubric grading payload for Assignment external API.
     *
     * @param array $criteria Criteria.
     * @return array
     */
    private static function rubric_fillings_for_save(array $criteria): array {
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('criteria must contain at least one rubric grade.');
        }

        $mapped = [];
        foreach ($criteria as $index => $criterion) {
            $criterion = self::ensure_array($criterion, "criteria[$index]");
            $criterionid = (int) ($criterion['criterion_id'] ?? 0);
            $levelid = (int) ($criterion['level_id'] ?? 0);
            if ($criterionid <= 0 || $levelid <= 0) {
                throw new \invalid_parameter_exception("criteria[$index] requires positive criterion_id and level_id.");
            }
            $mapped[] = [
                'criterionid' => $criterionid,
                'fillings' => [[
                    'criterionid' => $criterionid,
                    'levelid' => $levelid,
                    'remark' => (string) ($criterion['remark'] ?? ''),
                    'remarkformat' => FORMAT_HTML,
                ]],
            ];
        }

        return $mapped;
    }

    /**
     * Convert guide grading payload for Assignment external API.
     *
     * @param array $criteria Criteria.
     * @return array
     */
    private static function guide_fillings_for_save(array $criteria): array {
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('criteria must contain at least one guide grade.');
        }

        $mapped = [];
        foreach ($criteria as $index => $criterion) {
            $criterion = self::ensure_array($criterion, "criteria[$index]");
            $criterionid = (int) ($criterion['criterion_id'] ?? 0);
            if ($criterionid <= 0) {
                throw new \invalid_parameter_exception("criteria[$index].criterion_id must be a positive integer.");
            }
            $score = (float) ($criterion['score'] ?? -1);
            if ($score < 0) {
                throw new \invalid_parameter_exception("criteria[$index].score must be zero or greater.");
            }
            $mapped[] = [
                'criterionid' => $criterionid,
                'fillings' => [[
                    'criterionid' => $criterionid,
                    'score' => $score,
                    'remark' => (string) ($criterion['remark'] ?? ''),
                    'remarkformat' => FORMAT_HTML,
                ]],
            ];
        }

        return $mapped;
    }

    /**
     * Convert rubric criteria to canonical response.
     *
     * @param array $criteria Criteria.
     * @return array
     */
    private static function rubric_criteria_to_response(array $criteria): array {
        $items = [];
        foreach ($criteria as $criterion) {
            $criterion = self::ensure_array($criterion, 'rubric criterion');
            $levels = [];
            foreach ((array) ($criterion['levels'] ?? []) as $level) {
                $level = self::ensure_array($level, 'rubric level');
                $levels[] = [
                    'level_id' => (int) ($level['id'] ?? 0),
                    'score' => (float) ($level['score'] ?? 0),
                    'definition' => (string) ($level['definition'] ?? ''),
                ];
            }
            usort($levels, static function (array $a, array $b): int {
                return $a['score'] <=> $b['score'];
            });
            $items[] = [
                'criterion_id' => (int) ($criterion['id'] ?? 0),
                'sort_order' => (int) ($criterion['sortorder'] ?? 0),
                'shortname' => '',
                'description' => (string) ($criterion['description'] ?? ''),
                'description_markers' => '',
                'max_score' => empty($levels) ? 0.0 : (float) end($levels)['score'],
                'levels' => $levels,
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        return $items;
    }

    /**
     * Convert guide criteria to canonical response.
     *
     * @param array $criteria Criteria.
     * @return array
     */
    private static function guide_criteria_to_response(array $criteria): array {
        $items = [];
        foreach ($criteria as $criterion) {
            $criterion = self::ensure_array($criterion, 'guide criterion');
            $items[] = [
                'criterion_id' => (int) ($criterion['id'] ?? 0),
                'sort_order' => (int) ($criterion['sortorder'] ?? 0),
                'shortname' => (string) ($criterion['shortname'] ?? ''),
                'description' => (string) ($criterion['description'] ?? ''),
                'description_markers' => (string) ($criterion['descriptionmarkers'] ?? ''),
                'max_score' => (float) ($criterion['maxscore'] ?? 0),
                'levels' => [],
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        return $items;
    }

    /**
     * Convert guide comments to canonical response.
     *
     * @param array $comments Comments.
     * @return array
     */
    private static function guide_comments_to_response(array $comments): array {
        $items = [];
        foreach ($comments as $comment) {
            $comment = self::ensure_array($comment, 'guide comment');
            $items[] = [
                'comment_id' => (int) ($comment['id'] ?? 0),
                'sort_order' => (int) ($comment['sortorder'] ?? 0),
                'description' => (string) ($comment['description'] ?? ''),
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        return $items;
    }

    /**
     * Return levels for a rubric criterion.
     *
     * @param array $form Form.
     * @param int $criterionid Criterionid.
     * @return array
     */
    private static function levels_for_criterion(array $form, int $criterionid): array {
        foreach ($form['criteria'] as $criterion) {
            if ((int) $criterion['criterion_id'] === $criterionid) {
                $levels = $criterion['levels'];
                if (count($levels) < 2) {
                    throw new \invalid_parameter_exception('Checklist criterion must have at least two rubric levels.');
                }
                usort($levels, static function (array $a, array $b): int {
                    return $a['score'] <=> $b['score'];
                });
                return $levels;
            }
        }

        throw new \invalid_parameter_exception('criterion_id must belong to the active rubric.');
    }

    /**
     * Detect whether a rubric can behave as a binary checklist.
     *
     * @param array $criteria Criteria.
     * @return bool
     */
    private static function is_binary_rubric(array $criteria): bool {
        if (empty($criteria)) {
            return false;
        }
        foreach ($criteria as $criterion) {
            if (count($criterion['levels']) !== 2) {
                return false;
            }
        }
        return true;
    }

    /**
     * Decode a JSON object.
     *
     * @param string $json Json.
     * @param string $name Name.
     * @return array
     */
    private static function decode_object(string $json, string $name): array {
        $decoded = json_decode($json === '' ? '{}' : $json, true);
        if (!is_array($decoded) || (!empty($decoded) && array_is_list($decoded))) {
            throw new \invalid_parameter_exception("$name must be a JSON object.");
        }
        return $decoded;
    }

    /**
     * Decode a JSON object containing a list.
     *
     * @param string $json Json.
     * @param string $property Property.
     * @param string $name Name.
     * @param bool $allowempty Allowempty.
     * @return array
     */
    private static function decode_list_payload(string $json, string $property, string $name, bool $allowempty = false): array {
        $decoded = json_decode($json === '' ? '{}' : $json, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception("$name must be valid JSON.");
        }
        $list = array_is_list($decoded) ? $decoded : ($decoded[$property] ?? []);
        if (!is_array($list) || !array_is_list($list)) {
            throw new \invalid_parameter_exception("$name.$property must be a JSON array.");
        }
        if (!$allowempty && empty($list)) {
            throw new \invalid_parameter_exception("$name.$property must contain at least one item.");
        }
        return $list;
    }

    /**
     * Ensure a value is an array.
     *
     * @param mixed $value Value.
     * @param string $name Name.
     * @return array
     */
    private static function ensure_array($value, string $name): array {
        if (!is_array($value)) {
            throw new \invalid_parameter_exception("$name must be an object.");
        }
        return $value;
    }

    /**
     * Ensure a value is a list.
     *
     * @param mixed $value Value.
     * @param string $name Name.
     * @return array
     */
    private static function ensure_list($value, string $name): array {
        if (!is_array($value) || !array_is_list($value)) {
            throw new \invalid_parameter_exception("$name must be an array.");
        }
        return $value;
    }

    /**
     * Return an update key for existing or new advanced grading records.
     *
     * @param mixed $id Id.
     * @param int $index Index.
     * @return int|string
     */
    private static function record_key($id, int $index) {
        $id = (int) ($id ?? 0);
        return $id > 0 ? $id : 'NEWID' . ($index + 1);
    }
}
