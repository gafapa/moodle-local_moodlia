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
 * Course workflow helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * High-level course workflow helpers built on canonical operations.
 */
class course_workflow_tools {
    /** @var array Supported publishing states. */
    public const PUBLISH_STATES = ['draft', 'ready', 'published', 'archived'];

    /** @var array Supported module types for course blueprints. */
    public const MODULE_TYPES = [
        'assign',
        'book',
        'choice',
        'data',
        'feedback',
        'lesson',
        'lti',
        'page',
        'folder',
        'forum',
        'glossary',
        'label',
        'qbank',
        'quiz',
        'resource',
        'subsection',
        'url',
        'wiki',
        'workshop',
    ];

    /** @var array Supported role archetypes for course workflow enrolments. */
    public const ROLE_ARCHETYPES = ['student', 'teacher', 'editingteacher'];

    /**
     * Decode a JSON object parameter.
     *
     * @param string $json Json.
     * @param string $name Name.
     * @return array
     */
    public static function decode_object(string $json, string $name): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \invalid_parameter_exception($name . ' must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * Decode a JSON array parameter.
     *
     * @param string $json Json.
     * @param string $name Name.
     * @return array
     */
    public static function decode_array(string $json, string $name): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new \invalid_parameter_exception($name . ' must be a JSON array.');
        }

        return $decoded;
    }

    /**
     * Encode a response fragment as JSON.
     *
     * @param mixed $value Value.
     * @return string
     */
    public static function encode_json($value): string {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a course from a portable MoodlIA blueprint.
     *
     * @param array $blueprint Blueprint.
     * @param callable|null $coursewritevalidator Coursewritevalidator.
     * @return array
     */
    public static function create_from_blueprint(array $blueprint, ?callable $coursewritevalidator = null): array {
        self::validate_blueprint($blueprint, true, true);
        $courseinput = self::course_input($blueprint);
        $publishstate = self::normalise_publish_state((string) ($blueprint['publish_state'] ?? 'draft'));
        $courseinput['visible'] = self::visible_for_state($publishstate, (bool) ($courseinput['visible'] ?? false));

        $course = create_course::execute(
            (string) $courseinput['fullname'],
            (string) $courseinput['shortname'],
            (int) ($courseinput['category_id'] ?? 0),
            (bool) $courseinput['visible'],
            (string) ($courseinput['summary'] ?? ''),
            (string) ($courseinput['summary_format'] ?? 'html'),
            (string) ($courseinput['course_format'] ?? 'topics'),
            (bool) ($courseinput['enable_completion'] ?? false),
            (int) ($courseinput['start_date'] ?? 0),
            (int) ($courseinput['end_date'] ?? 0)
        );

        $applied = [
            'sections' => [],
            'modules' => [],
            'groups' => [],
            'enrolments' => [],
            'warnings' => [],
        ];
        if (self::blueprint_has_workflow($blueprint)) {
            if ($coursewritevalidator !== null) {
                $coursewritevalidator((int) $course['course_id'], $blueprint);
            }
            $applied = self::apply_to_course((int) $course['course_id'], $blueprint);
        }
        $published = set_course_publish_state::execute((int) $course['course_id'], $publishstate);

        return [
            'course_id' => (int) $course['course_id'],
            'publish_state' => $publishstate,
            'course_json' => $published['course_json'],
            'sections_json' => self::encode_json($applied['sections']),
            'modules_json' => self::encode_json($applied['modules']),
            'groups_json' => self::encode_json($applied['groups']),
            'enrolments_json' => self::encode_json($applied['enrolments']),
            'warnings_json' => self::encode_json($applied['warnings']),
        ];
    }

    /**
     * Apply a portable blueprint to an existing course.
     *
     * @param int $courseid Courseid.
     * @param array $blueprint Blueprint.
     * @return array
     */
    public static function apply_to_course(int $courseid, array $blueprint): array {
        course_tools::get_course($courseid);
        self::validate_blueprint($blueprint, false, false);

        $createdsections = [];
        $createdmodules = [];
        $createdgroups = [];
        $enrolments = [];
        $warnings = [];

        foreach (self::list_or_empty($blueprint['sections'] ?? []) as $section) {
            $sectionresponse = create_section::execute(
                $courseid,
                (string) ($section['name'] ?? 'Generated section'),
                (string) ($section['summary'] ?? ''),
                'plain',
                0,
                (bool) ($section['visible'] ?? true)
            );
            $createdsections[] = $sectionresponse;
            $sectionnumber = (int) $sectionresponse['section_number'];

            foreach (self::list_or_empty($section['modules'] ?? []) as $module) {
                try {
                    $createdmodule = create_module::execute(
                        $courseid,
                        $sectionnumber,
                        (string) ($module['module_type'] ?? ''),
                        (string) ($module['name'] ?? ''),
                        self::array_or_empty($module['options'] ?? [])
                    );
                    $createdsubelements = self::apply_module_subelements($courseid, $createdmodule, $module);
                    if (!empty($createdsubelements)) {
                        $createdmodule['subelements'] = $createdsubelements;
                    }
                    $createdmodules[] = $createdmodule;
                } catch (\Throwable $error) {
                    $warnings[] = [
                        'type' => 'module',
                        'name' => (string) ($module['name'] ?? ''),
                        'module_type' => (string) ($module['module_type'] ?? ''),
                        'message' => $error->getMessage(),
                    ];
                }
            }
        }

        foreach (self::list_or_empty($blueprint['groups'] ?? []) as $group) {
            try {
                $createdgroups[] = create_group::execute(
                    $courseid,
                    (string) ($group['name'] ?? ''),
                    (string) ($group['description'] ?? ''),
                    (string) ($group['idnumber'] ?? '')
                );
            } catch (\Throwable $error) {
                $warnings[] = [
                    'type' => 'group',
                    'name' => (string) ($group['name'] ?? ''),
                    'message' => $error->getMessage(),
                ];
            }
        }

        foreach (self::list_or_empty($blueprint['enrolments'] ?? []) as $enrolment) {
            try {
                $enrolments[] = enrol_user::execute(
                    $courseid,
                    (int) ($enrolment['user_id'] ?? 0),
                    (string) ($enrolment['role_archetype'] ?? 'student')
                );
            } catch (\Throwable $error) {
                $warnings[] = [
                    'type' => 'enrolment',
                    'user_id' => (int) ($enrolment['user_id'] ?? 0),
                    'message' => $error->getMessage(),
                ];
            }
        }

        return [
            'course_id' => $courseid,
            'sections' => $createdsections,
            'modules' => $createdmodules,
            'groups' => $createdgroups,
            'enrolments' => $enrolments,
            'warnings' => $warnings,
        ];
    }

    /**
     * Export a course as a portable MoodlIA blueprint.
     *
     * @param int $courseid Courseid.
     * @param bool $includecontents Includecontents.
     * @param bool $includegroups Includegroups.
     * @return array
     */
    public static function export_blueprint(int $courseid, bool $includecontents = true, bool $includegroups = true): array {
        $course = course_tools::to_response(course_tools::get_course($courseid));
        $blueprint = [
            'version' => 1,
            'course' => [
                'fullname' => $course['fullname'],
                'shortname' => $course['shortname'],
                'category_id' => $course['category_id'],
                'visible' => $course['visible'],
                'summary' => $course['summary'],
                'summary_format' => $course['summary_format'],
                'course_format' => $course['format'],
                'enable_completion' => $course['enable_completion'],
                'start_date' => $course['start_date'],
                'end_date' => $course['end_date'],
            ],
            'publish_state' => self::state_from_course($course),
            'sections' => [],
            'groups' => [],
            'enrolments' => [],
        ];

        if ($includecontents) {
            $contents = get_course_contents::execute($courseid);
            foreach ($contents['sections'] as $section) {
                if ((int) $section['section_number'] === 0) {
                    continue;
                }
                $modules = [];
                foreach ($section['modules'] as $module) {
                    $blueprintmodule = [
                        'module_type' => $module['module_type'],
                        'name' => $module['name'],
                        'options' => [
                            'visible' => (bool) $module['visible'],
                            'visible_on_course_page' => (bool) $module['visible_on_course_page'],
                        ],
                    ];
                    $modules[] = array_merge(
                        $blueprintmodule,
                        self::export_module_subelements($courseid, $module)
                    );
                }
                $blueprint['sections'][] = [
                    'name' => $section['name'],
                    'summary' => $section['summary'],
                    'visible' => (bool) $section['visible'],
                    'modules' => $modules,
                ];
            }
        }

        if ($includegroups) {
            foreach (get_groups::execute($courseid)['groups'] as $group) {
                $blueprint['groups'][] = [
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'idnumber' => $group['idnumber'],
                ];
            }
        }

        return $blueprint;
    }

    /**
     * Return basic quality issues for a course.
     *
     * @param int $courseid Courseid.
     * @return array
     */
    public static function audit_course(int $courseid): array {
        $course = course_tools::to_response(course_tools::get_course($courseid));
        $contents = get_course_contents::execute($courseid);
        $enrolled = get_enrolled_users::execute($courseid);
        $issues = [];

        if (!$course['visible']) {
            $issues[] = self::issue('warning', 'course_hidden', 'Course is hidden.');
        }
        if (trim(strip_tags((string) $course['summary'])) === '') {
            $issues[] = self::issue('warning', 'course_summary_empty', 'Course summary is empty.');
        }
        if (!$course['enable_completion']) {
            $issues[] = self::issue('info', 'completion_disabled', 'Course completion tracking is disabled.');
        }
        if (empty($enrolled['users'])) {
            $issues[] = self::issue('warning', 'no_enrolled_users', 'Course has no enrolled users visible to this token.');
        }

        $activitycount = 0;
        $teachablesections = 0;
        foreach ($contents['sections'] as $section) {
            if ((int) $section['section_number'] === 0) {
                continue;
            }
            $teachablesections++;
            $modules = $section['modules'] ?? [];
            $activitycount += count($modules);
            if (count($modules) === 0) {
                $issues[] = self::issue(
                    'warning',
                    'section_empty',
                    'Section has no activities.',
                    ['section_number' => (int) $section['section_number']]
                );
            }
        }

        if ($teachablesections === 0) {
            $issues[] = self::issue('error', 'no_teachable_sections', 'Course has no teaching sections.');
        }
        if ($activitycount === 0) {
            $issues[] = self::issue('error', 'no_activities', 'Course has no activities.');
        }

        return [
            'course_id' => $courseid,
            'ready' => !array_filter($issues, static fn($issue) => $issue['severity'] === 'error'),
            'issue_count' => count($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Convert a publish state to visibility.
     *
     * @param string $state State.
     * @param bool $fallback Fallback.
     * @return bool
     */
    public static function visible_for_state(string $state, bool $fallback = false): bool {
        $state = self::normalise_publish_state($state);
        if ($state === 'published') {
            return true;
        }
        if (in_array($state, ['draft', 'ready', 'archived'], true)) {
            return false;
        }
        return $fallback;
    }

    /**
     * Validate a publish state.
     *
     * @param string $state State.
     * @return string
     */
    public static function normalise_publish_state(string $state): string {
        $state = clean_param(trim($state), PARAM_ALPHA);
        if (!in_array($state, self::PUBLISH_STATES, true)) {
            throw new \invalid_parameter_exception('publish_state must be one of: draft, ready, published, archived.');
        }

        return $state;
    }

    /**
     * Validate a portable course blueprint before applying side effects.
     *
     * @param array $blueprint Blueprint.
     * @param bool $requirecourse Requirecourse.
     * @param bool $allowemptyworkflow Allowemptyworkflow.
     */
    public static function validate_blueprint(
        array $blueprint,
        bool $requirecourse = false,
        bool $allowemptyworkflow = false
    ): void {
        if ($requirecourse) {
            self::course_input($blueprint);
        }
        if (array_key_exists('publish_state', $blueprint)) {
            self::normalise_publish_state((string) $blueprint['publish_state']);
        }

        $sections = self::validated_list_field($blueprint, 'sections');
        $groups = self::validated_list_field($blueprint, 'groups');
        $enrolments = self::validated_list_field($blueprint, 'enrolments');
        $hasworkflow = !empty($sections) || !empty($groups) || !empty($enrolments);
        if (!$allowemptyworkflow && !$hasworkflow) {
            throw new \invalid_parameter_exception(
                'blueprint must include at least one section, group, or enrolment.'
            );
        }

        foreach ($sections as $sectionindex => $section) {
            if (!is_array($section) || array_is_list($section)) {
                throw new \invalid_parameter_exception(
                    'blueprint.sections[' . $sectionindex . '] must be a JSON object.'
                );
            }
            if (trim((string) ($section['name'] ?? '')) === '') {
                throw new \invalid_parameter_exception(
                    'blueprint.sections[' . $sectionindex . '].name is required.'
                );
            }
            if (
                array_key_exists('modules', $section)
                    && (!is_array($section['modules']) || !array_is_list($section['modules']))
            ) {
                throw new \invalid_parameter_exception(
                    'blueprint.sections[' . $sectionindex . '].modules must be a JSON array.'
                );
            }

            foreach (self::list_or_empty($section['modules'] ?? []) as $moduleindex => $module) {
                self::validate_blueprint_module($module, $sectionindex, $moduleindex);
            }
        }

        foreach ($groups as $groupindex => $group) {
            if (!is_array($group) || array_is_list($group)) {
                throw new \invalid_parameter_exception(
                    'blueprint.groups[' . $groupindex . '] must be a JSON object.'
                );
            }
            if (trim((string) ($group['name'] ?? '')) === '') {
                throw new \invalid_parameter_exception('blueprint.groups[' . $groupindex . '].name is required.');
            }
        }

        self::validate_enrolments($enrolments, 'blueprint.enrolments');
    }

    /**
     * Validate enrolment payloads before attempting writes.
     *
     * @param array $enrolments Enrolments.
     * @param string $prefix Prefix.
     */
    public static function validate_enrolments(array $enrolments, string $prefix = 'enrolments'): void {
        foreach ($enrolments as $index => $enrolment) {
            if (!is_array($enrolment) || array_is_list($enrolment)) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '] must be a JSON object.');
            }
            if (self::positive_integer_value($enrolment['user_id'] ?? null) === null) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '].user_id must be a positive integer.');
            }

            $role = (string) ($enrolment['role_archetype'] ?? 'student');
            if (!in_array($role, self::ROLE_ARCHETYPES, true)) {
                throw new \invalid_parameter_exception(
                    $prefix . '[' . $index . '].role_archetype must be one of: student, teacher, editingteacher.'
                );
            }
        }
    }

    /**
     * Infer the public state from course metadata.
     *
     * @param array $course Course.
     * @return string
     */
    private static function state_from_course(array $course): string {
        if ((bool) $course['visible']) {
            return 'published';
        }
        if ((int) ($course['end_date'] ?? 0) > 0 && (int) $course['end_date'] < time()) {
            return 'archived';
        }
        return 'draft';
    }

    /**
     * Validate a blueprint module object.
     *
     * @param mixed $module Module.
     * @param int $sectionindex Sectionindex.
     * @param int $moduleindex Moduleindex.
     */
    private static function validate_blueprint_module($module, int $sectionindex, int $moduleindex): void {
        $prefix = 'blueprint.sections[' . $sectionindex . '].modules[' . $moduleindex . ']';
        if (!is_array($module) || array_is_list($module)) {
            throw new \invalid_parameter_exception($prefix . ' must be a JSON object.');
        }

        $moduletype = clean_param((string) ($module['module_type'] ?? ''), PARAM_PLUGIN);
        if (!in_array($moduletype, self::MODULE_TYPES, true)) {
            throw new \invalid_parameter_exception($prefix . '.module_type is unsupported.');
        }
        if (trim((string) ($module['name'] ?? '')) === '') {
            throw new \invalid_parameter_exception($prefix . '.name is required.');
        }
        if (
            array_key_exists('options', $module)
                && (!is_array($module['options']) || array_is_list($module['options']))
        ) {
            throw new \invalid_parameter_exception($prefix . '.options must be a JSON object.');
        }
        if (array_key_exists('chapters', $module)) {
            if ($moduletype !== 'book') {
                throw new \invalid_parameter_exception($prefix . '.chapters is only supported for module_type=book.');
            }
            self::validate_book_chapters($module['chapters'], $prefix . '.chapters');
        }
        if (array_key_exists('feedback_items', $module)) {
            if ($moduletype !== 'feedback') {
                throw new \invalid_parameter_exception($prefix . '.feedback_items is only supported for module_type=feedback.');
            }
            self::validate_feedback_items($module['feedback_items'], $prefix . '.feedback_items');
        }
    }

    /**
     * Export supported activity subelements for a course blueprint module.
     *
     * @param int $courseid Courseid.
     * @param array $module Module.
     * @return array
     */
    private static function export_module_subelements(int $courseid, array $module): array {
        $moduletype = (string) ($module['module_type'] ?? '');
        if ($moduletype === 'book') {
            $chapters = get_book_chapters::execute($courseid, (int) ($module['module_id'] ?? 0), true, true);
            return [
                'chapters' => array_map(static function (array $chapter): array {
                    return [
                        'title' => $chapter['title'],
                        'content' => $chapter['content'],
                        'content_format' => $chapter['content_format'],
                        'subchapter' => $chapter['subchapter'],
                        'hidden' => $chapter['hidden'],
                    ];
                }, $chapters['chapters'] ?? []),
            ];
        }

        if ($moduletype === 'feedback') {
            $items = get_feedback_items::execute($courseid, (int) ($module['module_id'] ?? 0));
            return [
                'feedback_items' => array_map(static function (array $item): array {
                    return self::feedback_item_to_blueprint($item);
                }, $items['items'] ?? []),
            ];
        }

        return [];
    }

    /**
     * Apply supported activity subelements after a module shell is created.
     *
     * @param int $courseid Courseid.
     * @param array $createdmodule Createdmodule.
     * @param array $blueprintmodule Blueprintmodule.
     * @return array
     */
    private static function apply_module_subelements(int $courseid, array $createdmodule, array $blueprintmodule): array {
        if (($createdmodule['module_type'] ?? '') !== 'book') {
            if (($createdmodule['module_type'] ?? '') === 'feedback') {
                $feedbackitems = self::apply_feedback_items($courseid, $createdmodule, $blueprintmodule);
                return empty($feedbackitems) ? [] : ['feedback_items' => $feedbackitems];
            }

            return [];
        }

        $chapters = self::list_or_empty($blueprintmodule['chapters'] ?? []);
        if (empty($chapters)) {
            return [];
        }

        $modulecontext = \context_module::instance((int) $createdmodule['module_id']);
        require_capability('mod/book:edit', $modulecontext);

        $createdchapters = [];
        $afterchapterid = null;
        foreach ($chapters as $chapter) {
            $createdchapter = create_book_chapter::execute(
                $courseid,
                (int) $createdmodule['module_id'],
                (string) $chapter['title'],
                (string) ($chapter['content'] ?? ''),
                (int) ($chapter['content_format'] ?? FORMAT_HTML),
                (bool) ($chapter['subchapter'] ?? false),
                $afterchapterid,
                (bool) ($chapter['hidden'] ?? false)
            );
            $afterchapterid = (int) $createdchapter['chapter_id'];
            $createdchapters[] = $createdchapter;
        }

        return ['chapters' => $createdchapters];
    }

    /**
     * Convert a Feedback item response to a portable blueprint item.
     *
     * @param array $item Item.
     * @return array
     */
    private static function feedback_item_to_blueprint(array $item): array {
        $type = (string) ($item['type'] ?? '');
        $blueprint = [
            'source_item_id' => (int) ($item['item_id'] ?? 0),
            'type' => $type,
            'name' => (string) ($item['name'] ?? ''),
            'definition' => self::feedback_item_definition($item),
            'label' => (string) ($item['label'] ?? ''),
            'required' => (bool) ($item['required'] ?? false),
        ];

        $dependitemid = (int) ($item['depend_item_id'] ?? 0);
        if ($dependitemid > 0) {
            $blueprint['depend_source_item_id'] = $dependitemid;
            $blueprint['depend_value'] = (string) ($item['depend_value'] ?? '');
        }

        return $blueprint;
    }

    /**
     * Return a portable definition object for a supported Feedback item.
     *
     * @param array $item Item.
     * @return array
     */
    private static function feedback_item_definition(array $item): array {
        $type = (string) ($item['type'] ?? '');
        $presentation = (string) ($item['presentation'] ?? '');
        switch ($type) {
            case 'textfield':
                [$size, $maxlength] = self::split_pair($presentation, '30', '255');
                return ['size' => (int) $size, 'max_length' => (int) $maxlength];

            case 'textarea':
                [$width, $height] = self::split_pair($presentation, '30', '5');
                return ['width' => (int) $width, 'height' => (int) $height];

            case 'numeric':
                [$rangefrom, $rangeto] = self::split_pair($presentation, '-', '-');
                return [
                    'range_from' => $rangefrom === '-' ? null : (float) $rangefrom,
                    'range_to' => $rangeto === '-' ? null : (float) $rangeto,
                ];

            case 'multichoice':
                return self::feedback_choice_definition($presentation, false, (string) ($item['options'] ?? ''));

            case 'multichoicerated':
                return self::feedback_choice_definition($presentation, true, (string) ($item['options'] ?? ''));

            case 'label':
                return ['content' => $presentation];

            case 'info':
                $modes = ['1' => 'response_time', '2' => 'course', '3' => 'category'];
                return ['mode' => $modes[$presentation] ?? 'course'];

            case 'captcha':
                return [];

            case 'pagebreak':
                return [];
        }

        throw new \invalid_parameter_exception('Unsupported feedback item type in blueprint export.');
    }

    /**
     * Apply Feedback item blueprints after a Feedback module shell is created.
     *
     * @param int $courseid Courseid.
     * @param array $createdmodule Createdmodule.
     * @param array $blueprintmodule Blueprintmodule.
     * @return array
     */
    private static function apply_feedback_items(int $courseid, array $createdmodule, array $blueprintmodule): array {
        $items = self::list_or_empty($blueprintmodule['feedback_items'] ?? []);
        if (empty($items)) {
            return [];
        }

        $modulecontext = \context_module::instance((int) $createdmodule['module_id']);
        require_capability('mod/feedback:edititems', $modulecontext);

        $createditems = [];
        $itemmap = [];
        foreach ($items as $index => $item) {
            $dependsourceid = (int) ($item['depend_source_item_id'] ?? 0);
            $dependitemid = $dependsourceid > 0 ? ($itemmap[$dependsourceid] ?? 0) : 0;
            if ($dependsourceid > 0 && $dependitemid === 0) {
                throw new \invalid_parameter_exception(
                    'feedback_items[' . $index . '].depend_source_item_id must reference an earlier item.'
                );
            }

            $createditem = create_feedback_item::execute(
                $courseid,
                (int) $createdmodule['module_id'],
                (string) $item['type'],
                array_key_exists('name', $item) ? (string) $item['name'] : null,
                self::encode_json(self::array_or_empty($item['definition'] ?? [])),
                $index + 1,
                array_key_exists('label', $item) ? (string) $item['label'] : null,
                array_key_exists('required', $item) ? (bool) $item['required'] : null,
                $dependitemid > 0 ? $dependitemid : null,
                array_key_exists('depend_value', $item) ? (string) $item['depend_value'] : null
            );

            $sourceid = (int) ($item['source_item_id'] ?? 0);
            if ($sourceid > 0) {
                $itemmap[$sourceid] = (int) $createditem['item_id'];
            }
            $createditems[] = $createditem;
        }

        return $createditems;
    }

    /**
     * Validate Book chapter blueprints.
     *
     * @param mixed $chapters Chapters.
     * @param string $prefix Prefix.
     */
    private static function validate_book_chapters($chapters, string $prefix): void {
        if (!is_array($chapters) || !array_is_list($chapters)) {
            throw new \invalid_parameter_exception($prefix . ' must be a JSON array.');
        }

        foreach ($chapters as $index => $chapter) {
            if (!is_array($chapter) || array_is_list($chapter)) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '] must be a JSON object.');
            }
            if (
                !array_key_exists('title', $chapter) || !self::text_like_value($chapter['title'])
                    || trim((string) $chapter['title']) === ''
            ) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '].title is required.');
            }
            if (array_key_exists('content', $chapter) && !self::text_like_value($chapter['content'])) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '].content must be a string.');
            }
            if (
                array_key_exists('content_format', $chapter)
                    && self::non_negative_integer_value($chapter['content_format']) === null
            ) {
                throw new \invalid_parameter_exception(
                    $prefix . '[' . $index . '].content_format must be a non-negative integer.'
                );
            }
            if (array_key_exists('subchapter', $chapter) && !self::boolean_like_value($chapter['subchapter'])) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '].subchapter must be a boolean.');
            }
            if (array_key_exists('hidden', $chapter) && !self::boolean_like_value($chapter['hidden'])) {
                throw new \invalid_parameter_exception($prefix . '[' . $index . '].hidden must be a boolean.');
            }
            if ($index === 0 && !empty($chapter['subchapter'])) {
                throw new \invalid_parameter_exception($prefix . '[0].subchapter must be false.');
            }
        }
    }

    /**
     * Validate Feedback item blueprints before course workflow side effects.
     *
     * @param mixed $items Items.
     * @param string $prefix Prefix.
     */
    private static function validate_feedback_items($items, string $prefix): void {
        if (!is_array($items) || !array_is_list($items)) {
            throw new \invalid_parameter_exception($prefix . ' must be a JSON array.');
        }

        $supported = ['textfield', 'textarea', 'numeric', 'multichoice', 'multichoicerated', 'label', 'info', 'captcha', 'pagebreak'];
        $seenids = [];
        $hascaptcha = false;
        $previoustype = '';
        foreach ($items as $index => $item) {
            $itemprefix = $prefix . '[' . $index . ']';
            if (!is_array($item) || array_is_list($item)) {
                throw new \invalid_parameter_exception($itemprefix . ' must be a JSON object.');
            }
            $type = clean_param((string) ($item['type'] ?? ''), PARAM_ALPHANUMEXT);
            if (!in_array($type, $supported, true)) {
                throw new \invalid_parameter_exception($itemprefix . '.type is unsupported.');
            }
            if (
                $type !== 'pagebreak' && $type !== 'captcha'
                    && (!array_key_exists('name', $item) || !self::text_like_value($item['name'])
                        || trim((string) $item['name']) === '')
            ) {
                throw new \invalid_parameter_exception($itemprefix . '.name is required.');
            }
            if ($type === 'captcha') {
                if ($hascaptcha) {
                    throw new \invalid_parameter_exception($itemprefix . ' cannot add a second captcha item.');
                }
                $hascaptcha = true;
                if (array_key_exists('required', $item) && !$item['required']) {
                    throw new \invalid_parameter_exception($itemprefix . '.required must not be false for captcha items.');
                }
                if (array_key_exists('depend_source_item_id', $item) || array_key_exists('depend_value', $item)) {
                    throw new \invalid_parameter_exception($itemprefix . ' cannot define dependencies for captcha items.');
                }
            }
            if ($type === 'pagebreak' && ($index === 0 || $previoustype === 'pagebreak')) {
                throw new \invalid_parameter_exception($itemprefix . ' cannot be the first item or follow another pagebreak.');
            }

            if (array_key_exists('source_item_id', $item)) {
                $sourceid = self::positive_integer_value($item['source_item_id']);
                if ($sourceid === null) {
                    throw new \invalid_parameter_exception($itemprefix . '.source_item_id must be a positive integer.');
                }
                if (isset($seenids[$sourceid])) {
                    throw new \invalid_parameter_exception($itemprefix . '.source_item_id must be unique.');
                }
                $seenids[$sourceid] = true;
            }

            if (array_key_exists('depend_source_item_id', $item)) {
                $dependsourceid = self::positive_integer_value($item['depend_source_item_id']);
                if ($dependsourceid === null) {
                    throw new \invalid_parameter_exception($itemprefix . '.depend_source_item_id must be a positive integer.');
                }
                if (!isset($seenids[$dependsourceid])) {
                    throw new \invalid_parameter_exception(
                        $itemprefix . '.depend_source_item_id must reference an earlier item.'
                    );
                }
                if (array_key_exists('depend_value', $item) && !self::text_like_value($item['depend_value'])) {
                    throw new \invalid_parameter_exception($itemprefix . '.depend_value must be a string.');
                }
            }
            if (array_key_exists('label', $item) && !self::text_like_value($item['label'])) {
                throw new \invalid_parameter_exception($itemprefix . '.label must be a string.');
            }
            if (array_key_exists('required', $item) && !self::boolean_like_value($item['required'])) {
                throw new \invalid_parameter_exception($itemprefix . '.required must be a boolean.');
            }

            $definition = self::array_or_empty($item['definition'] ?? []);
            if (
                array_key_exists('definition', $item)
                    && (!is_array($item['definition']) || (!empty($item['definition']) && array_is_list($item['definition'])))
            ) {
                throw new \invalid_parameter_exception($itemprefix . '.definition must be a JSON object.');
            }
            self::validate_feedback_item_definition($type, $definition, $itemprefix . '.definition');
            $previoustype = $type;
        }
    }

    /**
     * Validate a Feedback item definition.
     *
     * @param string $type Type.
     * @param array $definition Definition.
     * @param string $prefix Prefix.
     */
    private static function validate_feedback_item_definition(string $type, array $definition, string $prefix): void {
        switch ($type) {
            case 'textfield':
                self::validate_optional_int_range($definition, 'size', $prefix, 5, 255);
                self::validate_optional_int_range($definition, 'max_length', $prefix, 1, 2000);
                break;

            case 'textarea':
                self::validate_optional_int_range($definition, 'width', $prefix, 5, 255);
                self::validate_optional_int_range($definition, 'height', $prefix, 1, 100);
                break;

            case 'numeric':
                $rangefrom = self::nullable_numeric_value($definition['range_from'] ?? null, $prefix . '.range_from');
                $rangeto = self::nullable_numeric_value($definition['range_to'] ?? null, $prefix . '.range_to');
                if ($rangefrom !== null && $rangeto !== null && $rangefrom > $rangeto) {
                    throw new \invalid_parameter_exception($prefix . '.range_from must not be greater than range_to.');
                }
                break;

            case 'multichoice':
                self::validate_feedback_choice_definition($definition, $prefix, true);
                break;

            case 'multichoicerated':
                self::validate_feedback_rated_choice_definition($definition, $prefix);
                break;

            case 'label':
                if (
                    !array_key_exists('content', $definition) || !self::text_like_value($definition['content'])
                        || trim((string) $definition['content']) === ''
                ) {
                    throw new \invalid_parameter_exception($prefix . '.content must be non-empty.');
                }
                break;

            case 'info':
                $mode = (string) ($definition['mode'] ?? 'course');
                if (!in_array($mode, ['response_time', 'responsetime', '1', 'course', '2', 'category', 'course_category', '3'], true)) {
                    throw new \invalid_parameter_exception($prefix . '.mode is unsupported.');
                }
                break;

            case 'captcha':
                if (!empty($definition)) {
                    throw new \invalid_parameter_exception($prefix . ' must be empty for captcha items.');
                }
                break;
        }
    }

    /**
     * Split a pipe-delimited Feedback presentation pair.
     *
     * @param string $presentation Presentation.
     * @param string $firstdefault Firstdefault.
     * @param string $seconddefault Seconddefault.
     * @return array
     */
    private static function split_pair(string $presentation, string $firstdefault, string $seconddefault): array {
        $parts = explode('|', $presentation, 2);
        return [
            $parts[0] ?? $firstdefault,
            $parts[1] ?? $seconddefault,
        ];
    }

    /**
     * Convert a Feedback choice presentation string to a portable definition.
     *
     * @param string $presentation Presentation.
     * @param bool $rated Rated.
     * @param string $options Options.
     * @return array
     */
    private static function feedback_choice_definition(string $presentation, bool $rated, string $options): array {
        $subtypecode = substr($presentation, 0, 1);
        $subtypes = ['r' => 'radio', 'c' => 'checkbox', 'd' => 'dropdown'];
        $definition = [
            'subtype' => $subtypes[$subtypecode] ?? 'radio',
        ];

        $payload = substr($presentation, 1);
        if (strpos($payload, '>>>>>') === 0) {
            $payload = substr($payload, 5);
        }
        [$choices, $horizontal] = explode('<<<<<', $payload, 2) + ['', '0'];
        $choiceparts = $choices === '' ? [] : explode('|', $choices);

        if ($rated) {
            $definition['choices'] = array_map(static function (string $choice): array {
                [$value, $text] = explode('####', $choice, 2) + [0, ''];
                return [
                    'value' => (int) $value,
                    'text' => $text,
                ];
            }, $choiceparts);
        } else {
            $definition['choices'] = $choiceparts;
        }

        if ($subtypecode !== 'd') {
            $definition['horizontal'] = $horizontal === '1';
        }
        $definition['ignore_empty'] = strpos($options, 'i') !== false;
        $definition['hide_no_select'] = strpos($options, 'h') !== false;

        return $definition;
    }

    /**
     * Validate an optional integer definition field.
     *
     * @param array $definition Definition.
     * @param string $key Key.
     * @param string $prefix Prefix.
     * @param int $min Min.
     * @param int $max Max.
     */
    private static function validate_optional_int_range(
        array $definition,
        string $key,
        string $prefix,
        int $min,
        int $max
    ): void {
        if (!array_key_exists($key, $definition)) {
            return;
        }
        $value = self::non_negative_integer_value($definition[$key]);
        if ($value === null || $value < $min || $value > $max) {
            throw new \invalid_parameter_exception($prefix . '.' . $key . ' is outside the valid range.');
        }
    }

    /**
     * Return a nullable numeric definition value.
     *
     * @param mixed $value Value.
     * @param string $path Path.
     * @return float|null
     */
    private static function nullable_numeric_value($value, string $path): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new \invalid_parameter_exception($path . ' must be numeric.');
        }

        return (float) $value;
    }

    /**
     * Validate a Feedback multichoice definition.
     *
     * @param array $definition Definition.
     * @param string $prefix Prefix.
     * @param bool $allowcheckbox Allowcheckbox.
     */
    private static function validate_feedback_choice_definition(array $definition, string $prefix, bool $allowcheckbox): void {
        $subtype = (string) ($definition['subtype'] ?? 'radio');
        $allowed = $allowcheckbox
            ? ['radio', 'checkbox', 'dropdown', 'r', 'c', 'd']
            : ['radio', 'dropdown', 'r', 'd'];
        if (!in_array($subtype, $allowed, true)) {
            throw new \invalid_parameter_exception($prefix . '.subtype is unsupported.');
        }
        foreach (['horizontal', 'ignore_empty', 'hide_no_select'] as $flag) {
            if (array_key_exists($flag, $definition) && !self::boolean_like_value($definition[$flag])) {
                throw new \invalid_parameter_exception($prefix . '.' . $flag . ' must be a boolean.');
            }
        }

        $choices = $definition['choices'] ?? null;
        if (!is_array($choices) || !array_is_list($choices) || count($choices) < 2) {
            throw new \invalid_parameter_exception($prefix . '.choices must contain at least two choices.');
        }

        $seen = [];
        foreach ($choices as $index => $choice) {
            if (!self::text_like_value($choice)) {
                throw new \invalid_parameter_exception($prefix . '.choices[' . $index . '] must be a string.');
            }
            $text = trim((string) $choice);
            if ($text === '') {
                throw new \invalid_parameter_exception($prefix . '.choices[' . $index . '] must be non-empty.');
            }
            self::reject_feedback_separator($text, $prefix . '.choices[' . $index . ']');
            $key = strtolower($text);
            if (isset($seen[$key])) {
                throw new \invalid_parameter_exception($prefix . '.choices must be unique.');
            }
            $seen[$key] = true;
        }
    }

    /**
     * Validate a Feedback rated-choice definition.
     *
     * @param array $definition Definition.
     * @param string $prefix Prefix.
     */
    private static function validate_feedback_rated_choice_definition(array $definition, string $prefix): void {
        self::validate_feedback_choice_definition([
            'subtype' => $definition['subtype'] ?? 'radio',
            'choices' => ['placeholder one', 'placeholder two'],
        ], $prefix, false);
        foreach (['horizontal', 'ignore_empty', 'hide_no_select'] as $flag) {
            if (array_key_exists($flag, $definition) && !self::boolean_like_value($definition[$flag])) {
                throw new \invalid_parameter_exception($prefix . '.' . $flag . ' must be a boolean.');
            }
        }

        $choices = $definition['choices'] ?? null;
        if (!is_array($choices) || !array_is_list($choices) || count($choices) < 2) {
            throw new \invalid_parameter_exception($prefix . '.choices must contain at least two rated choices.');
        }

        $seen = [];
        foreach ($choices as $index => $choice) {
            if (!is_array($choice) || array_is_list($choice)) {
                throw new \invalid_parameter_exception($prefix . '.choices[' . $index . '] must be a JSON object.');
            }
            $text = $choice['text'] ?? $choice['label'] ?? null;
            if (!self::text_like_value($text) || trim((string) $text) === '') {
                throw new \invalid_parameter_exception($prefix . '.choices[' . $index . '].text is required.');
            }
            if (!array_key_exists('value', $choice) && !array_key_exists('weight', $choice)) {
                throw new \invalid_parameter_exception($prefix . '.choices[' . $index . '].value is required.');
            }
            $value = $choice['value'] ?? $choice['weight'];
            if (!is_numeric($value)) {
                throw new \invalid_parameter_exception($prefix . '.choices[' . $index . '].value must be numeric.');
            }
            $text = trim((string) $text);
            self::reject_feedback_separator($text, $prefix . '.choices[' . $index . '].text');
            $key = strtolower($text);
            if (isset($seen[$key])) {
                throw new \invalid_parameter_exception($prefix . '.choices text values must be unique.');
            }
            $seen[$key] = true;
        }
    }

    /**
     * Reject Moodle Feedback presentation separators inside public text.
     *
     * @param string $text Text.
     * @param string $path Path.
     */
    private static function reject_feedback_separator(string $text, string $path): void {
        foreach (['|', '####', '>>>>>', '<<<<<'] as $separator) {
            if (strpos($text, $separator) !== false) {
                throw new \invalid_parameter_exception($path . ' contains unsupported separator characters.');
            }
        }
    }

    /**
     * Return a validated list field from a blueprint.
     *
     * @param array $blueprint Blueprint.
     * @param string $field Field.
     * @return array
     */
    private static function validated_list_field(array $blueprint, string $field): array {
        if (!array_key_exists($field, $blueprint)) {
            return [];
        }
        if (!is_array($blueprint[$field]) || !array_is_list($blueprint[$field])) {
            throw new \invalid_parameter_exception('blueprint.' . $field . ' must be a JSON array.');
        }

        return $blueprint[$field];
    }

    /**
     * Return whether a blueprint contains writeable course workflow items.
     *
     * @param array $blueprint Blueprint.
     * @return bool
     */
    private static function blueprint_has_workflow(array $blueprint): bool {
        return !empty(self::validated_list_field($blueprint, 'sections'))
            || !empty(self::validated_list_field($blueprint, 'groups'))
            || !empty(self::validated_list_field($blueprint, 'enrolments'));
    }

    /**
     * Return a positive integer value when the input is strictly integer-like.
     *
     * @param mixed $value Value.
     * @return int|null
     */
    private static function positive_integer_value($value): ?int {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', trim($value))) {
            return (int) trim($value);
        }

        return null;
    }

    /**
     * Return a non-negative integer value when the input is strictly integer-like.
     *
     * @param mixed $value Value.
     * @return int|null
     */
    private static function non_negative_integer_value($value): ?int {
        if (is_int($value) && $value >= 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[0-9]+$/', trim($value))) {
            return (int) trim($value);
        }

        return null;
    }

    /**
     * Return whether a JSON value is safely boolean-like.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private static function boolean_like_value($value): bool {
        return is_bool($value) || $value === 0 || $value === 1;
    }

    /**
     * Return whether a JSON value can be safely used as text.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private static function text_like_value($value): bool {
        return is_string($value) || is_int($value) || is_float($value);
    }

    /**
     * Return course input from root or nested course key.
     *
     * @param array $blueprint Blueprint.
     * @return array
     */
    private static function course_input(array $blueprint): array {
        $course = self::array_or_empty($blueprint['course'] ?? $blueprint);
        foreach (['fullname', 'shortname'] as $field) {
            if (trim((string) ($course[$field] ?? '')) === '') {
                throw new \invalid_parameter_exception('blueprint.course.' . $field . ' is required.');
            }
        }

        return $course;
    }

    /**
     * Return a list value or an empty list.
     *
     * @param mixed $value Value.
     * @return array
     */
    private static function list_or_empty($value): array {
        return is_array($value) && array_is_list($value) ? $value : [];
    }

    /**
     * Return an object-like array or an empty array.
     *
     * @param mixed $value Value.
     * @return array
     */
    private static function array_or_empty($value): array {
        return is_array($value) && !array_is_list($value) ? $value : [];
    }

    /**
     * Build an audit issue.
     *
     * @param string $severity Severity.
     * @param string $code Code.
     * @param string $message Message.
     * @param array $details Details.
     * @return array
     */
    private static function issue(string $severity, string $code, string $message, array $details = []): array {
        return [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ];
    }
}
