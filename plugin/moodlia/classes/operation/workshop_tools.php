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
 * Shared workshop helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle Workshop operations.
 */
class workshop_tools {
    /**
     * Load Moodle workshop APIs.
     */
    public static function require_workshop_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/workshop/lib.php');
        require_once($CFG->dirroot . '/mod/workshop/locallib.php');
        require_once($CFG->dirroot . '/mod/workshop/classes/external.php');
    }

    /**
     * Verify that a course module belongs to a workshop activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_workshop_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'workshop') {
            throw new \invalid_parameter_exception('module_id must reference a workshop activity.');
        }

        return $cm;
    }

    /**
     * Return workshop instance data exposed through Moodle's workshop external API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Workshop course module.
     * @return array
     */
    public static function get_workshop_instance_data(\stdClass $course, \cm_info $cm): array {
        self::require_workshop_api();

        $result = \mod_workshop_external::get_workshops_by_courses([(int) $course->id]);
        foreach (($result['workshops'] ?? []) as $workshop) {
            $workshop = (array) $workshop;
            if (
                (int) ($workshop['id'] ?? 0) === (int) $cm->instance ||
                (int) ($workshop['coursemodule'] ?? $workshop['cmid'] ?? $workshop['coursemoduleid'] ?? 0) === (int) $cm->id
            ) {
                return $workshop;
            }
        }

        throw new \invalid_parameter_exception('module_id must reference a visible workshop activity in the selected course.');
    }

    /**
     * Return a workshop domain object.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Workshop course module.
     * @return \workshop
     */
    public static function get_workshop_object(\stdClass $course, \cm_info $cm): \workshop {
        self::require_workshop_api();

        $data = (object) self::get_workshop_instance_data($course, $cm);
        $data->id = (int) $cm->instance;
        $data->course = (int) $course->id;
        $cmrecord = get_coursemodule_from_id('workshop', (int) $cm->id, (int) $course->id, false, MUST_EXIST);

        return new \workshop($data, $cmrecord, $course);
    }

    /**
     * Prepare Moodle page globals required by Workshop form component APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Workshop course module.
     * @return \stdClass Course-module record.
     */
    public static function prepare_page_context(\stdClass $course, \cm_info $cm): \stdClass {
        global $PAGE;

        $cmrecord = get_coursemodule_from_id('workshop', (int) $cm->id, (int) $course->id, false, MUST_EXIST);
        $PAGE->set_course($course);
        $PAGE->set_cm($cmrecord, $course);

        return $cmrecord;
    }

    /**
     * Decode and validate an accumulative grading-form definition.
     *
     * @param string $definitionjson JSON object with a dimensions array.
     * @return array
     */
    public static function decode_accumulative_definition(string $definitionjson): array {
        $decoded = json_decode($definitionjson, true);
        if (!is_array($decoded) || !isset($decoded['dimensions']) || !is_array($decoded['dimensions'])) {
            throw new \invalid_parameter_exception('definition must be a JSON object with a dimensions array.');
        }
        if (count($decoded['dimensions']) === 0) {
            throw new \invalid_parameter_exception('definition.dimensions must contain at least one dimension.');
        }

        $dimensions = [];
        foreach ($decoded['dimensions'] as $dimension) {
            if (!is_array($dimension)) {
                throw new \invalid_parameter_exception('Each dimension must be an object.');
            }
            $description = trim((string) ($dimension['description'] ?? ''));
            if ($description === '' || trim(strip_tags($description)) === '') {
                throw new \invalid_parameter_exception('Each dimension description must be non-empty.');
            }
            $grade = (float) ($dimension['grade'] ?? 0);
            if ($grade <= 0) {
                throw new \invalid_parameter_exception('Each dimension grade must be greater than zero.');
            }
            $weight = (float) ($dimension['weight'] ?? 1);
            if ($weight < 0) {
                throw new \invalid_parameter_exception('Each dimension weight must be zero or greater.');
            }

            $dimensions[] = [
                'description' => $description,
                'grade' => $grade,
                'weight' => $weight,
            ];
        }

        return $dimensions;
    }

    /**
     * Decode and validate a comments grading-form definition.
     *
     * @param string $definitionjson JSON object with a dimensions array.
     * @return array
     */
    public static function decode_comments_definition(string $definitionjson): array {
        $decoded = json_decode($definitionjson, true);
        if (!is_array($decoded) || !isset($decoded['dimensions']) || !is_array($decoded['dimensions'])) {
            throw new \invalid_parameter_exception('definition must be a JSON object with a dimensions array.');
        }
        if (count($decoded['dimensions']) === 0) {
            throw new \invalid_parameter_exception('definition.dimensions must contain at least one dimension.');
        }

        $dimensions = [];
        $seen = [];
        foreach ($decoded['dimensions'] as $dimension) {
            if (!is_array($dimension)) {
                throw new \invalid_parameter_exception('Each dimension must be an object.');
            }
            $description = trim((string) ($dimension['description'] ?? ''));
            if ($description === '' || trim(strip_tags($description)) === '') {
                throw new \invalid_parameter_exception('Each dimension description must be non-empty.');
            }
            $key = \core_text::strtolower(strip_tags($description));
            if (array_key_exists($key, $seen)) {
                throw new \invalid_parameter_exception('Comments dimensions must have unique descriptions.');
            }
            $seen[$key] = true;

            $dimensions[] = [
                'description' => $description,
            ];
        }

        return $dimensions;
    }

    /**
     * Decode and validate a number-of-errors grading-form definition.
     *
     * @param string $definitionjson JSON object with dimensions and optional mappings.
     * @return array
     */
    public static function decode_numerrors_definition(string $definitionjson): array {
        $decoded = json_decode($definitionjson, true);
        if (!is_array($decoded) || !isset($decoded['dimensions']) || !is_array($decoded['dimensions'])) {
            throw new \invalid_parameter_exception('definition must be a JSON object with a dimensions array.');
        }
        if (count($decoded['dimensions']) === 0) {
            throw new \invalid_parameter_exception('definition.dimensions must contain at least one dimension.');
        }

        $dimensions = [];
        $seen = [];
        $totalweight = 0;
        foreach ($decoded['dimensions'] as $dimension) {
            if (!is_array($dimension)) {
                throw new \invalid_parameter_exception('Each dimension must be an object.');
            }
            $description = trim((string) ($dimension['description'] ?? ''));
            if ($description === '' || trim(strip_tags($description)) === '') {
                throw new \invalid_parameter_exception('Each dimension description must be non-empty.');
            }
            $key = \core_text::strtolower(strip_tags($description));
            if (array_key_exists($key, $seen)) {
                throw new \invalid_parameter_exception('Number-of-errors dimensions must have unique descriptions.');
            }
            $seen[$key] = true;

            $grade0 = trim((string) ($dimension['grade0'] ?? 'No'));
            $grade1 = trim((string) ($dimension['grade1'] ?? 'Yes'));
            if ($grade0 === '' || trim(strip_tags($grade0)) === '' || $grade1 === '' || trim(strip_tags($grade1)) === '') {
                throw new \invalid_parameter_exception('Each number-of-errors dimension requires non-empty grade0 and grade1 labels.');
            }
            if (\core_text::strtolower(strip_tags($grade0)) === \core_text::strtolower(strip_tags($grade1))) {
                throw new \invalid_parameter_exception('grade0 and grade1 labels must be different.');
            }

            if (array_key_exists('weight', $dimension) && !is_numeric($dimension['weight'])) {
                throw new \invalid_parameter_exception('Each dimension weight must be numeric.');
            }
            $weight = (int) ($dimension['weight'] ?? 1);
            if ($weight <= 0) {
                throw new \invalid_parameter_exception('Each number-of-errors dimension weight must be greater than zero.');
            }
            $totalweight += $weight;

            $dimensions[] = [
                'description' => $description,
                'grade0' => $grade0,
                'grade1' => $grade1,
                'weight' => $weight,
            ];
        }

        $mappings = [];
        if (isset($decoded['mappings'])) {
            if (!is_array($decoded['mappings'])) {
                throw new \invalid_parameter_exception('definition.mappings must be an array or object.');
            }

            foreach ($decoded['mappings'] as $key => $mapping) {
                if (is_array($mapping)) {
                    if (!array_key_exists('errors', $mapping) || !is_numeric($mapping['errors'])) {
                        throw new \invalid_parameter_exception('Each number-of-errors mapping requires a numeric errors value.');
                    }
                    if (!array_key_exists('grade', $mapping) || !is_numeric($mapping['grade'])) {
                        throw new \invalid_parameter_exception('Each number-of-errors mapping requires a numeric grade value.');
                    }
                    $errors = (int) $mapping['errors'];
                    $grade = (float) $mapping['grade'];
                } else {
                    if (!is_numeric($key) || !is_numeric($mapping)) {
                        throw new \invalid_parameter_exception('definition.mappings object keys and values must be numeric.');
                    }
                    $errors = (int) $key;
                    $grade = (float) $mapping;
                }

                if ($errors < 1 || $errors > $totalweight) {
                    throw new \invalid_parameter_exception('Number-of-errors mapping errors must be between 1 and the total dimension weight.');
                }
                if ($grade < 0 || $grade > 100) {
                    throw new \invalid_parameter_exception('Number-of-errors mapping grade must be between 0 and 100.');
                }
                $mappings[$errors] = $grade;
            }
        } else {
            for ($errors = 1; $errors <= $totalweight; $errors++) {
                $mappings[$errors] = floor(100 - $errors * 100 / $totalweight);
            }
        }

        ksort($mappings, SORT_NUMERIC);

        return [
            'dimensions' => $dimensions,
            'mappings' => $mappings,
            'total_weight' => $totalweight,
        ];
    }

    /**
     * Decode and validate a rubric grading-form definition.
     *
     * @param string $definitionjson JSON object with layout and dimensions.
     * @return array
     */
    public static function decode_rubric_definition(string $definitionjson): array {
        $decoded = json_decode($definitionjson, true);
        if (!is_array($decoded) || !isset($decoded['dimensions']) || !is_array($decoded['dimensions'])) {
            throw new \invalid_parameter_exception('definition must be a JSON object with a dimensions array.');
        }
        if (count($decoded['dimensions']) === 0) {
            throw new \invalid_parameter_exception('definition.dimensions must contain at least one dimension.');
        }

        $layout = clean_param((string) ($decoded['layout'] ?? 'list'), PARAM_ALPHA);
        if (!in_array($layout, ['list', 'grid'], true)) {
            throw new \invalid_parameter_exception('definition.layout must be list or grid.');
        }

        $dimensions = [];
        $seen = [];
        foreach ($decoded['dimensions'] as $dimension) {
            if (!is_array($dimension)) {
                throw new \invalid_parameter_exception('Each dimension must be an object.');
            }
            $description = trim((string) ($dimension['description'] ?? ''));
            if ($description === '' || trim(strip_tags($description)) === '') {
                throw new \invalid_parameter_exception('Each dimension description must be non-empty.');
            }
            $dimensionkey = \core_text::strtolower(strip_tags($description));
            if (array_key_exists($dimensionkey, $seen)) {
                throw new \invalid_parameter_exception('Rubric dimensions must have unique descriptions.');
            }
            $seen[$dimensionkey] = true;

            if (!isset($dimension['levels']) || !is_array($dimension['levels']) || count($dimension['levels']) < 2) {
                throw new \invalid_parameter_exception('Each rubric dimension must contain at least two levels.');
            }

            $levels = [];
            $levelgrades = [];
            $leveldefinitions = [];
            foreach ($dimension['levels'] as $level) {
                if (!is_array($level)) {
                    throw new \invalid_parameter_exception('Each rubric level must be an object.');
                }
                $definition = trim((string) ($level['definition'] ?? ''));
                if ($definition === '' || trim(strip_tags($definition)) === '') {
                    throw new \invalid_parameter_exception('Each rubric level definition must be non-empty.');
                }
                if (!array_key_exists('grade', $level) || !is_numeric($level['grade'])) {
                    throw new \invalid_parameter_exception('Each rubric level grade must be numeric.');
                }
                $grade = (float) $level['grade'];
                $gradekey = (string) $grade;
                if (array_key_exists($gradekey, $levelgrades)) {
                    throw new \invalid_parameter_exception('Rubric level grades must be unique within each dimension.');
                }
                $definitionkey = \core_text::strtolower(strip_tags($definition));
                if (array_key_exists($definitionkey, $leveldefinitions)) {
                    throw new \invalid_parameter_exception('Rubric level definitions must be unique within each dimension.');
                }
                $levelgrades[$gradekey] = true;
                $leveldefinitions[$definitionkey] = true;
                $levels[] = [
                    'definition' => $definition,
                    'grade' => $grade,
                ];
            }

            usort($levels, static fn($left, $right): int => $left['grade'] <=> $right['grade']);

            $dimensions[] = [
                'description' => $description,
                'levels' => $levels,
            ];
        }

        return [
            'layout' => $layout,
            'dimensions' => $dimensions,
        ];
    }

    /**
     * Build form-shaped data for Workshop accumulative strategy saving.
     *
     * @param \workshop $workshop Workshop domain object.
     * @param array $dimensions New dimension rows.
     * @param array $existing Existing dimension info keyed by id.
     * @return \stdClass
     */
    public static function accumulative_edit_form_data(\workshop $workshop, array $dimensions, array $existing = []): \stdClass {
        $data = new \stdClass();
        $data->workshopid = (int) $workshop->id;
        $data->norepeats = count($existing) + count($dimensions);

        $index = 0;
        foreach (array_keys($existing) as $dimensionid) {
            $data->{'dimensionid__idx_' . $index} = (int) $dimensionid;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => '',
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];
            $data->{'grade__idx_' . $index} = 0;
            $data->{'weight__idx_' . $index} = 0;
            $index++;
        }

        foreach ($dimensions as $dimension) {
            $data->{'dimensionid__idx_' . $index} = 0;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => $dimension['description'],
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];
            $data->{'grade__idx_' . $index} = $dimension['grade'];
            $data->{'weight__idx_' . $index} = $dimension['weight'];
            $index++;
        }

        return $data;
    }

    /**
     * Build form-shaped data for Workshop comments strategy saving.
     *
     * @param \workshop $workshop Workshop domain object.
     * @param array $dimensions New dimension rows.
     * @param array $existing Existing dimension info keyed by id.
     * @return \stdClass
     */
    public static function comments_edit_form_data(\workshop $workshop, array $dimensions, array $existing = []): \stdClass {
        $data = new \stdClass();
        $data->workshopid = (int) $workshop->id;
        $data->norepeats = count($existing) + count($dimensions);

        $index = 0;
        foreach (array_keys($existing) as $dimensionid) {
            $data->{'dimensionid__idx_' . $index} = (int) $dimensionid;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => '',
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];
            $index++;
        }

        foreach ($dimensions as $dimension) {
            $data->{'dimensionid__idx_' . $index} = 0;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => $dimension['description'],
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];
            $index++;
        }

        return $data;
    }

    /**
     * Build form-shaped data for Workshop number-of-errors strategy saving.
     *
     * @param \workshop $workshop Workshop domain object.
     * @param array $definition Validated number-of-errors definition.
     * @param array $existing Existing dimension info keyed by id.
     * @return \stdClass
     */
    public static function numerrors_edit_form_data(\workshop $workshop, array $definition, array $existing = []): \stdClass {
        $dimensions = $definition['dimensions'];
        $data = new \stdClass();
        $data->workshopid = (int) $workshop->id;
        $data->norepeats = count($existing) + count($dimensions);

        $index = 0;
        foreach (array_keys($existing) as $dimensionid) {
            $data->{'dimensionid__idx_' . $index} = (int) $dimensionid;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => '',
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];
            $data->{'grade0__idx_' . $index} = 'No';
            $data->{'grade1__idx_' . $index} = 'Yes';
            $data->{'weight__idx_' . $index} = 1;
            $index++;
        }

        foreach ($dimensions as $dimension) {
            $data->{'dimensionid__idx_' . $index} = 0;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => $dimension['description'],
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];
            $data->{'grade0__idx_' . $index} = $dimension['grade0'];
            $data->{'grade1__idx_' . $index} = $dimension['grade1'];
            $data->{'weight__idx_' . $index} = $dimension['weight'];
            $index++;
        }

        for ($errors = 1; $errors <= $definition['total_weight']; $errors++) {
            $data->{'map__idx_' . $errors} = array_key_exists($errors, $definition['mappings'])
                ? $definition['mappings'][$errors]
                : '';
        }

        return $data;
    }

    /**
     * Return rubric dimensions and levels loaded by Moodle's rubric strategy object.
     *
     * @param object $strategyinstance Workshop rubric strategy instance.
     * @return array
     */
    public static function rubric_existing_dimensions(object $strategyinstance): array {
        if (!property_exists($strategyinstance, 'dimensions')) {
            return [];
        }

        $reflection = new \ReflectionObject($strategyinstance);
        if (!$reflection->hasProperty('dimensions')) {
            return [];
        }

        $property = $reflection->getProperty('dimensions');
        $property->setAccessible(true);
        $dimensions = $property->getValue($strategyinstance);
        if (!is_array($dimensions)) {
            return [];
        }

        return $dimensions;
    }

    /**
     * Build form-shaped data for Workshop rubric strategy saving.
     *
     * @param \workshop $workshop Workshop domain object.
     * @param array $definition Validated rubric definition.
     * @param array $existing Existing rubric dimensions from the strategy object.
     * @return \stdClass
     */
    public static function rubric_edit_form_data(\workshop $workshop, array $definition, array $existing = []): \stdClass {
        $dimensions = $definition['dimensions'];
        $data = new \stdClass();
        $data->workshopid = (int) $workshop->id;
        $data->config_layout = $definition['layout'];
        $data->norepeats = count($existing) + count($dimensions);

        $index = 0;
        foreach ($existing as $dimension) {
            $dimension = (object) $dimension;
            $data->{'dimensionid__idx_' . $index} = (int) ($dimension->id ?? 0);
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => '',
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];

            $levelindex = 0;
            foreach ((array) ($dimension->levels ?? []) as $level) {
                $level = (object) $level;
                $data->{'levelid__idx_' . $index . '__idy_' . $levelindex} = (int) ($level->id ?? 0);
                $data->{'grade__idx_' . $index . '__idy_' . $levelindex} = (float) ($level->grade ?? 0);
                $data->{'definition__idx_' . $index . '__idy_' . $levelindex} = '';
                $levelindex++;
            }
            $index++;
        }

        foreach ($dimensions as $dimension) {
            $data->{'dimensionid__idx_' . $index} = 0;
            $data->{'description__idx_' . $index . '_editor'} = [
                'text' => $dimension['description'],
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ];

            $levelindex = 0;
            foreach ($dimension['levels'] as $level) {
                $data->{'levelid__idx_' . $index . '__idy_' . $levelindex} = 0;
                $data->{'grade__idx_' . $index . '__idy_' . $levelindex} = $level['grade'];
                $data->{'definition__idx_' . $index . '__idy_' . $levelindex} = $level['definition'];
                $levelindex++;
            }
            $index++;
        }

        return $data;
    }

    /**
     * Convert a public phase name to a Moodle workshop phase constant.
     *
     * @param string $phase Public phase.
     * @return int
     */
    public static function phase_to_constant(string $phase): int {
        self::require_workshop_api();

        $phase = clean_param(strtolower(trim($phase)), PARAM_ALPHA);
        $map = [
            'setup' => \workshop::PHASE_SETUP,
            'submission' => \workshop::PHASE_SUBMISSION,
            'assessment' => \workshop::PHASE_ASSESSMENT,
            'evaluation' => \workshop::PHASE_EVALUATION,
            'closed' => \workshop::PHASE_CLOSED,
        ];

        if (!array_key_exists($phase, $map)) {
            throw new \invalid_parameter_exception('phase must be one of: setup, submission, assessment, evaluation, closed.');
        }

        return (int) $map[$phase];
    }

    /**
     * Convert a Moodle workshop phase constant to a public phase name.
     *
     * @param int $phase Moodle phase constant.
     * @return string
     */
    public static function phase_from_constant(int $phase): string {
        self::require_workshop_api();

        $map = [
            \workshop::PHASE_SETUP => 'setup',
            \workshop::PHASE_SUBMISSION => 'submission',
            \workshop::PHASE_ASSESSMENT => 'assessment',
            \workshop::PHASE_EVALUATION => 'evaluation',
            \workshop::PHASE_CLOSED => 'closed',
        ];

        return $map[$phase] ?? 'unknown';
    }

    /**
     * Convert a public content format name to a Moodle format constant.
     *
     * @param string $format Public format.
     * @return int
     */
    public static function format_to_constant(string $format): int {
        $format = clean_param($format ?: 'html', PARAM_ALPHA);
        if ($format === 'html') {
            return FORMAT_HTML;
        }
        if ($format === 'plain') {
            return FORMAT_PLAIN;
        }

        throw new \invalid_parameter_exception('content_format must be one of: html, plain.');
    }

    /**
     * Convert a Moodle format constant to a public format name.
     *
     * @param int $format Moodle format.
     * @return string
     */
    public static function format_from_constant(int $format): string {
        return $format === FORMAT_PLAIN ? 'plain' : 'html';
    }

    /**
     * Return a canonical workshop submission response.
     *
     * @param \cm_info $cm Workshop course module.
     * @param array|\stdClass $submission Moodle submission payload.
     * @return array
     */
    public static function submission_to_response(\cm_info $cm, $submission): array {
        $submission = (array) $submission;

        return [
            'submission_id' => (int) ($submission['id'] ?? 0),
            'workshop_id' => (int) ($submission['workshopid'] ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'author_id' => (int) ($submission['authorid'] ?? 0),
            'title' => (string) ($submission['title'] ?? ''),
            'content' => (string) ($submission['content'] ?? ''),
            'content_format' => self::format_from_constant((int) ($submission['contentformat'] ?? FORMAT_HTML)),
            'grade' => self::optional_float($submission, 'grade'),
            'grade_over' => self::optional_float($submission, 'gradeover'),
            'grade_over_by' => (int) ($submission['gradeoverby'] ?? 0),
            'published' => (bool) ($submission['published'] ?? false),
            'late' => (bool) ($submission['late'] ?? false),
            'time_created' => (int) ($submission['timecreated'] ?? 0),
            'time_modified' => (int) ($submission['timemodified'] ?? 0),
        ];
    }

    /**
     * Return a submission and ensure it belongs to the selected module.
     *
     * @param \cm_info $cm Workshop course module.
     * @param int $submissionid Submission id.
     * @return array
     */
    public static function get_submission(\cm_info $cm, int $submissionid): array {
        self::require_workshop_api();

        $result = \mod_workshop_external::get_submission($submissionid);
        $submission = (array) ($result['submission'] ?? []);
        if ((int) ($submission['workshopid'] ?? 0) !== (int) $cm->instance) {
            throw new \invalid_parameter_exception('submission_id must reference a submission in the selected workshop module.');
        }

        return self::submission_to_response($cm, $submission);
    }

    /**
     * Return an assessment and ensure it belongs to the selected module.
     *
     * @param \cm_info $cm Workshop course module.
     * @param int $assessmentid Assessment id.
     * @return array
     */
    public static function get_assessment(\cm_info $cm, int $assessmentid): array {
        self::require_workshop_api();

        $result = \mod_workshop_external::get_assessment($assessmentid);
        $assessment = (array) ($result['assessment'] ?? []);
        if (array_key_exists('workshopid', $assessment) && (int) $assessment['workshopid'] !== (int) $cm->instance) {
            throw new \invalid_parameter_exception('assessment_id must reference an assessment in the selected workshop module.');
        }
        if (!array_key_exists('workshopid', $assessment)) {
            $submissionid = (int) ($assessment['submissionid'] ?? 0);
            if ($submissionid <= 0) {
                throw new \invalid_parameter_exception('assessment_id must reference an assessment in the selected workshop module.');
            }
            self::get_submission($cm, $submissionid);
        }

        return self::assessment_to_response($cm, $assessment);
    }

    /**
     * Return a canonical workshop assessment response.
     *
     * @param \cm_info $cm Workshop course module.
     * @param array|\stdClass $assessment Moodle assessment payload.
     * @return array
     */
    public static function assessment_to_response(\cm_info $cm, $assessment): array {
        $assessment = (array) $assessment;

        return [
            'assessment_id' => (int) ($assessment['id'] ?? 0),
            'workshop_id' => (int) ($assessment['workshopid'] ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'submission_id' => (int) ($assessment['submissionid'] ?? 0),
            'reviewer_id' => (int) ($assessment['reviewerid'] ?? 0),
            'weight' => (int) ($assessment['weight'] ?? 0),
            'grade' => self::optional_float($assessment, 'grade'),
            'grading_grade' => self::optional_float($assessment, 'gradinggrade'),
            'grading_grade_over' => self::optional_float($assessment, 'gradinggradeover'),
            'grading_grade_over_by' => (int) ($assessment['gradinggradeoverby'] ?? 0),
            'feedback_author' => (string) ($assessment['feedbackauthor'] ?? ''),
            'feedback_author_format' => self::format_from_constant((int) ($assessment['feedbackauthorformat'] ?? FORMAT_HTML)),
            'feedback_reviewer' => (string) ($assessment['feedbackreviewer'] ?? ''),
            'feedback_reviewer_format' => self::format_from_constant((int) ($assessment['feedbackreviewerformat'] ?? FORMAT_HTML)),
            'time_created' => (int) ($assessment['timecreated'] ?? 0),
            'time_modified' => (int) ($assessment['timemodified'] ?? 0),
        ];
    }

    /**
     * Return canonical workshop assessment response rows.
     *
     * @param \cm_info $cm Workshop course module.
     * @param mixed $assessments Moodle assessment rows.
     * @return array
     */
    public static function assessments_to_response(\cm_info $cm, $assessments): array {
        $items = [];
        foreach ((array) $assessments as $assessment) {
            $items[] = self::assessment_to_response($cm, $assessment);
        }

        return $items;
    }

    /**
     * Convert Moodle warning rows to the canonical response shape.
     *
     * @param mixed $warnings Moodle warning rows.
     * @return array
     */
    public static function warnings_to_response($warnings): array {
        $items = [];
        foreach ((array) $warnings as $warning) {
            $warning = (array) $warning;
            $items[] = [
                'item' => (string) ($warning['item'] ?? ''),
                'item_id' => (int) ($warning['itemid'] ?? $warning['item_id'] ?? 0),
                'warning_code' => (string) ($warning['warningcode'] ?? $warning['warning_code'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Encode flexible Moodle payloads as stable JSON strings.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public static function json_value($value): string {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '[]' : $encoded;
    }

    /**
     * Return a canonical workshop user plan response.
     *
     * @param \cm_info $cm Workshop course module.
     * @param int $userid User id.
     * @param array $result Moodle workshop external result.
     * @return array
     */
    public static function user_plan_to_response(\cm_info $cm, int $userid, array $result): array {
        $userplan = (array) ($result['userplan'] ?? []);
        $phases = [];
        foreach (($userplan['phases'] ?? []) as $phase) {
            $phase = (array) $phase;
            $tasks = [];
            foreach (($phase['tasks'] ?? []) as $task) {
                $task = (array) $task;
                $tasks[] = [
                    'code' => (string) ($task['code'] ?? ''),
                    'title' => (string) ($task['title'] ?? ''),
                    'link' => (string) ($task['link'] ?? ''),
                    'details' => (string) ($task['details'] ?? ''),
                    'completed' => self::completed_to_string($task['completed'] ?? ''),
                ];
            }

            $actions = [];
            foreach (($phase['actions'] ?? []) as $action) {
                $action = (array) $action;
                $actions[] = [
                    'type' => (string) ($action['type'] ?? ''),
                    'label' => (string) ($action['label'] ?? ''),
                    'url' => (string) ($action['url'] ?? ''),
                    'method' => (string) ($action['method'] ?? ''),
                ];
            }

            $phases[] = [
                'code' => (int) ($phase['code'] ?? 0),
                'title' => (string) ($phase['title'] ?? ''),
                'phase' => self::phase_from_constant((int) ($phase['code'] ?? 0)),
                'active' => (bool) ($phase['active'] ?? false),
                'task_count' => count($tasks),
                'tasks' => $tasks,
                'action_count' => count($actions),
                'actions' => $actions,
            ];
        }

        $examples = [];
        foreach (($userplan['examples'] ?? []) as $example) {
            $example = (array) $example;
            $examples[] = [
                'submission_id' => (int) ($example['id'] ?? 0),
                'title' => (string) ($example['title'] ?? ''),
                'assessment_id' => (int) ($example['assessmentid'] ?? 0),
                'grade' => self::optional_float($example, 'grade'),
                'grading_grade' => self::optional_float($example, 'gradinggrade'),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'user_id' => (int) $userid,
            'phase_count' => count($phases),
            'phases' => $phases,
            'example_count' => count($examples),
            'examples' => $examples,
        ];
    }

    /**
     * Return a canonical workshop grades response.
     *
     * @param \cm_info $cm Workshop course module.
     * @param int $userid User id.
     * @param array $result Moodle workshop external result.
     * @return array
     */
    public static function grades_to_response(\cm_info $cm, int $userid, array $result): array {
        return [
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'user_id' => (int) $userid,
            'submission_raw_grade' => self::optional_float($result, 'submissionrawgrade'),
            'submission_grade' => (string) ($result['submissionlongstrgrade'] ?? ''),
            'submission_grade_hidden' => (bool) ($result['submissiongradehidden'] ?? false),
            'assessment_raw_grade' => self::optional_float($result, 'assessmentrawgrade'),
            'assessment_grade' => (string) ($result['assessmentlongstrgrade'] ?? ''),
            'assessment_grade_hidden' => (bool) ($result['assessmentgradehidden'] ?? false),
        ];
    }

    /**
     * Return a canonical workshop grades report response.
     *
     * @param \cm_info $cm Workshop course module.
     * @param array $result Moodle workshop external result.
     * @param int $groupid Resolved group id.
     * @param string $sortby Sort field.
     * @param string $sortdirection Sort direction.
     * @param int $page Page number.
     * @param int $perpage Page size.
     * @return array
     */
    public static function grades_report_to_response(
        \cm_info $cm,
        array $result,
        int $groupid,
        string $sortby,
        string $sortdirection,
        int $page,
        int $perpage
    ): array {
        $report = (array) ($result['report'] ?? []);
        $grades = [];
        foreach (($report['grades'] ?? []) as $grade) {
            $grade = (array) $grade;
            $grades[] = [
                'user_id' => (int) ($grade['userid'] ?? 0),
                'submission_id' => (int) ($grade['submissionid'] ?? 0),
                'submission_title' => (string) ($grade['submissiontitle'] ?? ''),
                'submission_modified' => (int) ($grade['submissionmodified'] ?? 0),
                'submission_grade' => self::optional_float($grade, 'submissiongrade'),
                'grading_grade' => self::optional_float($grade, 'gradinggrade'),
                'submission_grade_over' => self::optional_float($grade, 'submissiongradeover'),
                'submission_grade_over_by' => (int) ($grade['submissiongradeoverby'] ?? 0),
                'submission_published' => (bool) ($grade['submissionpublished'] ?? false),
                'reviewed_by' => self::report_reviews_to_response($grade['reviewedby'] ?? []),
                'reviewer_of' => self::report_reviews_to_response($grade['reviewerof'] ?? []),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'workshop_id' => (int) $cm->instance,
            'group_id' => (int) $groupid,
            'sort_by' => $sortby,
            'sort_direction' => $sortdirection,
            'page' => (int) $page,
            'per_page' => (int) $perpage,
            'total_count' => (int) ($report['totalcount'] ?? count($grades)),
            'count' => count($grades),
            'grades' => $grades,
        ];
    }

    /**
     * Return an optional float response value.
     *
     * @param array $data Payload.
     * @param string $key Key.
     * @return float
     */
    public static function optional_float(array $data, string $key): float {
        return isset($data[$key]) && is_scalar($data[$key]) ? (float) $data[$key] : 0.0;
    }

    /**
     * Return Moodle's flexible completion value as a stable string.
     *
     * @param mixed $completed Completion payload.
     * @return string
     */
    private static function completed_to_string($completed): string {
        if (is_bool($completed)) {
            return $completed ? 'true' : 'false';
        }
        if (is_scalar($completed)) {
            return (string) $completed;
        }

        return '';
    }

    /**
     * Return canonical assessment review rows for a grades report row.
     *
     * @param mixed $reviews Moodle review rows.
     * @return array
     */
    private static function report_reviews_to_response($reviews): array {
        $items = [];
        foreach ((array) $reviews as $review) {
            $review = (array) $review;
            $items[] = [
                'user_id' => (int) ($review['userid'] ?? 0),
                'assessment_id' => (int) ($review['assessmentid'] ?? 0),
                'submission_id' => (int) ($review['submissionid'] ?? 0),
                'grade' => self::optional_float($review, 'grade'),
                'grading_grade' => self::optional_float($review, 'gradinggrade'),
                'grading_grade_over' => self::optional_float($review, 'gradinggradeover'),
                'weight' => (int) ($review['weight'] ?? 0),
            ];
        }

        return $items;
    }
}
