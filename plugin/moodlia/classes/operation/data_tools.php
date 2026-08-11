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
 * Shared database activity helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle Database activity operations.
 */
class data_tools {
    /** @var array Supported field types for initial safe entry CRUD. */
    private const SUPPORTED_FIELD_TYPES = ['text', 'textarea', 'number', 'menu', 'checkbox', 'radiobutton', 'multimenu', 'url'];

    /**
     * Load Moodle Database activity APIs.
     */
    public static function require_data_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/data/lib.php');
        require_once($CFG->dirroot . '/mod/data/locallib.php');
        require_once($CFG->dirroot . '/mod/data/classes/external.php');
    }

    /**
     * Verify that a course module belongs to a Database activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_data_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'data') {
            throw new \invalid_parameter_exception('module_id must reference a database activity.');
        }

        return $cm;
    }

    /**
     * Return a Database activity object suitable for Moodle data field APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Course module.
     * @return \stdClass
     */
    public static function get_database_instance(\stdClass $course, \cm_info $cm): \stdClass {
        self::require_data_api();

        $result = \mod_data_external::get_databases_by_courses([(int) $course->id]);
        $databases = (array) ($result['databases'] ?? $result);
        foreach ($databases as $database) {
            $database = (object) (array) $database;
            if (
                (int) ($database->id ?? 0) === (int) $cm->instance ||
                (int) ($database->coursemodule ?? $database->cmid ?? $database->coursemoduleid ?? 0) === (int) $cm->id
            ) {
                if (empty($database->course)) {
                    $database->course = (int) $course->id;
                }
                return $database;
            }
        }

        $database = new \stdClass();
        $database->id = (int) $cm->instance;
        $database->course = (int) $course->id;
        $database->name = (string) $cm->name;
        return $database;
    }

    /**
     * Decode a JSON object string.
     *
     * @param string $json JSON object.
     * @param string $name Parameter name.
     * @return array
     */
    public static function decode_json_object(string $json, string $name): array {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new \invalid_parameter_exception($name . ' must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * Return fields exposed by Moodle's Database activity external API.
     *
     * @param \cm_info $cm Database course module.
     * @return array
     */
    public static function get_fields(\cm_info $cm): array {
        self::require_data_api();

        $result = \mod_data_external::get_fields((int) $cm->instance);
        $fields = [];
        foreach (($result['fields'] ?? $result) as $field) {
            $fields[] = self::field_to_response($cm, (array) $field);
        }

        return $fields;
    }

    /**
     * Create a Database activity field through Moodle field APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Database course module.
     * @param string $type Field type.
     * @param string $name Field name.
     * @param string $description Field description.
     * @param bool $required Whether the field is required.
     * @param array $options Field options.
     * @return array
     */
    public static function create_field(
        \stdClass $course,
        \cm_info $cm,
        string $type,
        string $name,
        string $description,
        bool $required,
        array $options
    ): array {
        self::require_data_api();

        $type = clean_param(strtolower(trim($type)), PARAM_ALPHA);
        if (!in_array($type, self::SUPPORTED_FIELD_TYPES, true)) {
            throw new \invalid_parameter_exception('field_type must be one of: ' . implode(', ', self::SUPPORTED_FIELD_TYPES) . '.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        $database = self::get_database_instance($course, $cm);
        if (data_get_field_from_name($name, $database)) {
            throw new \invalid_parameter_exception('A field with this name already exists in the selected database activity.');
        }

        $fielddata = new \stdClass();
        $fielddata->name = $name;
        $fielddata->description = trim($description);
        $fielddata->required = $required ? 1 : 0;
        self::apply_field_options($fielddata, $type, $options);

        $field = data_get_field_new($type, $database);
        $field->define_field($fielddata);
        if (!$field->insert_field()) {
            throw new \moodle_exception('invalidfieldtype', 'data');
        }

        if (function_exists('data_append_new_field_to_templates')) {
            data_append_new_field_to_templates($database, $fielddata->name);
        }
        rebuild_course_cache($course->id, true);

        foreach (self::get_fields($cm) as $created) {
            if ((string) $created['name'] === $name) {
                return $created;
            }
        }

        throw new \moodle_exception('invalidfieldid', 'data');
    }

    /**
     * Update a Database activity field through Moodle field APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Database course module.
     * @param int $fieldid Database field id.
     * @param string $name Field name.
     * @param string $description Field description.
     * @param bool $required Whether the field is required.
     * @param array $options Field options.
     * @return array
     */
    public static function update_field(
        \stdClass $course,
        \cm_info $cm,
        int $fieldid,
        string $name,
        string $description,
        bool $required,
        array $options
    ): array {
        self::require_data_api();

        $name = trim($name);
        if ($name === '') {
            throw new \invalid_parameter_exception('name is required.');
        }

        $database = self::get_database_instance($course, $cm);
        $field = self::get_field_object($database, $fieldid);
        $type = (string) ($field->field->type ?? '');
        if (!in_array($type, self::SUPPORTED_FIELD_TYPES, true)) {
            throw new \invalid_parameter_exception('Only supported database field types can be updated through MoodlIA.');
        }

        $duplicate = data_get_field_from_name($name, $database);
        if ($duplicate && (int) ($duplicate->field->id ?? 0) !== (int) $fieldid) {
            throw new \invalid_parameter_exception('A field with this name already exists in the selected database activity.');
        }

        $fielddata = new \stdClass();
        $fielddata->fid = (int) $fieldid;
        $fielddata->name = $name;
        $fielddata->description = trim($description);
        $fielddata->required = $required ? 1 : 0;
        self::apply_field_options($fielddata, $type, $options);

        if (method_exists($field, 'validate')) {
            $validationerrors = $field->validate($fielddata);
            if (!empty($validationerrors)) {
                throw new \invalid_parameter_exception(implode(' ', array_map('strval', (array) $validationerrors)));
            }
        }

        $oldname = (string) ($field->field->name ?? '');
        $field->field->name = $fielddata->name;
        $field->field->description = $fielddata->description;
        $field->field->required = $fielddata->required;
        for ($index = 1; $index <= 10; $index++) {
            $param = 'param' . $index;
            $field->field->{$param} = isset($fielddata->{$param}) ? trim((string) $fielddata->{$param}) : '';
        }

        if (!$field->update_field()) {
            throw new \moodle_exception('invalidfieldid', 'data');
        }

        if ($oldname !== $fielddata->name && function_exists('data_replace_field_in_templates')) {
            data_replace_field_in_templates($database, $oldname, $fielddata->name);
        }
        rebuild_course_cache($course->id, true);

        return self::get_field_response($cm, $fieldid);
    }

    /**
     * Delete a Database activity field through Moodle field APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Database course module.
     * @param int $fieldid Database field id.
     * @return array
     */
    public static function delete_field(\stdClass $course, \cm_info $cm, int $fieldid): array {
        self::require_data_api();

        $database = self::get_database_instance($course, $cm);
        if ((int) ($database->defaultsort ?? 0) === (int) $fieldid) {
            throw new \invalid_parameter_exception('The selected field is the default sort field and cannot be deleted through MoodlIA.');
        }

        $field = self::get_field_object($database, $fieldid);
        $name = (string) ($field->field->name ?? '');
        if (!$field->delete_field()) {
            throw new \moodle_exception('invalidfieldid', 'data');
        }

        if (function_exists('data_replace_field_in_templates')) {
            data_replace_field_in_templates($database, $name, '');
        }
        rebuild_course_cache($course->id, true);

        return [
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'data_id' => (int) $cm->instance,
            'field_id' => (int) $fieldid,
            'deleted' => true,
        ];
    }

    /**
     * Return a field object and validate that it belongs to the selected database.
     *
     * @param \stdClass $database Database activity instance.
     * @param int $fieldid Database field id.
     * @return object
     */
    private static function get_field_object(\stdClass $database, int $fieldid): object {
        $field = data_get_field_from_id($fieldid, $database);
        if (!$field) {
            throw new \invalid_parameter_exception('field_id must reference a field in the selected database activity.');
        }

        return $field;
    }

    /**
     * Return a field response from the public field listing.
     *
     * @param \cm_info $cm Database course module.
     * @param int $fieldid Database field id.
     * @return array
     */
    private static function get_field_response(\cm_info $cm, int $fieldid): array {
        foreach (self::get_fields($cm) as $field) {
            if ((int) $field['field_id'] === (int) $fieldid) {
                return $field;
            }
        }

        throw new \moodle_exception('invalidfieldid', 'data');
    }

    /**
     * Convert public field options to Moodle field parameters.
     *
     * @param \stdClass $fielddata Field data.
     * @param string $type Field type.
     * @param array $options Public options.
     */
    private static function apply_field_options(\stdClass $fielddata, string $type, array $options): void {
        if (in_array($type, ['menu', 'checkbox', 'radiobutton', 'multimenu'], true)) {
            $choices = self::normalise_choices($options['choices'] ?? $options['options'] ?? []);
            if (!$choices) {
                throw new \invalid_parameter_exception('options.choices must contain at least one choice for this field type.');
            }
            $fielddata->param1 = implode("\n", $choices);
        }

        if ($type === 'textarea') {
            $fielddata->param1 = (string) (int) max(1, (int) ($options['rows'] ?? 10));
            $fielddata->param2 = (string) (int) max(1, (int) ($options['columns'] ?? 60));
        }

        if ($type === 'url') {
            $fielddata->param1 = !empty($options['auto_link']) ? '1' : '0';
            $fielddata->param2 = trim((string) ($options['forced_link_text'] ?? ''));
            $fielddata->param3 = !empty($options['open_in_new_window']) ? '1' : '0';
        }

        foreach (['param1', 'param2', 'param3', 'param4', 'param5'] as $param) {
            if (array_key_exists($param, $options) && is_scalar($options[$param])) {
                $fielddata->{$param} = (string) $options[$param];
            }
        }
    }

    /**
     * Return a cleaned list of choices.
     *
     * @param mixed $choices Public choices.
     * @return array
     */
    private static function normalise_choices($choices): array {
        if (is_string($choices)) {
            $choices = preg_split('/\r\n|\r|\n/', $choices);
        }
        if (!is_array($choices)) {
            return [];
        }

        $result = [];
        foreach ($choices as $choice) {
            if (!is_scalar($choice)) {
                continue;
            }
            $value = trim((string) $choice);
            if ($value !== '') {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Convert public values to Moodle external entry data.
     *
     * @param \cm_info $cm Database course module.
     * @param array $values Field values keyed by field id or name.
     * @return array
     */
    public static function values_to_external(\cm_info $cm, array $values): array {
        $fields = self::get_fields($cm);
        $byname = [];
        $byid = [];
        foreach ($fields as $field) {
            $byname[(string) $field['name']] = $field;
            $byid[(string) $field['field_id']] = $field;
        }

        $external = [];
        foreach ($values as $key => $value) {
            $key = (string) $key;
            $subfield = '';
            $fieldkey = $key;
            if (str_contains($key, '.')) {
                [$fieldkey, $subfield] = explode('.', $key, 2);
            }

            $field = $byid[$fieldkey] ?? $byname[$fieldkey] ?? null;
            if (!$field) {
                throw new \invalid_parameter_exception('values contains an unknown database field: ' . $fieldkey . '.');
            }

            $external[] = [
                'fieldid' => (int) $field['field_id'],
                'subfield' => self::normalise_value_subfield($field, $subfield),
                'value' => json_encode($value, JSON_UNESCAPED_SLASHES),
            ];
        }

        return $external;
    }

    /**
     * Normalize public Database entry subfield aliases to Moodle's field indexes.
     *
     * @param array $field Field metadata.
     * @param string $subfield Public subfield.
     * @return string
     */
    private static function normalise_value_subfield(array $field, string $subfield): string {
        $subfield = clean_param($subfield, PARAM_NOTAGS);
        if ((string) ($field['type'] ?? '') !== 'url') {
            return $subfield;
        }

        $key = strtolower(trim($subfield));
        $aliases = [
            '' => '0',
            'url' => '0',
            'href' => '0',
            'link' => '0',
            '0' => '0',
            'text' => '1',
            'label' => '1',
            'title' => '1',
            '1' => '1',
        ];
        if (!array_key_exists($key, $aliases)) {
            throw new \invalid_parameter_exception('URL database fields only support url and text subfields.');
        }

        return $aliases[$key];
    }

    /**
     * Return entries from a Database activity.
     *
     * @param \cm_info $cm Database course module.
     * @param string $search Search text.
     * @param bool $includecontents Include field contents.
     * @param int $page Page number.
     * @param int $perpage Page size.
     * @return array
     */
    public static function get_entries(\cm_info $cm, string $search, bool $includecontents, int $page, int $perpage): array {
        self::require_data_api();

        if (trim($search) !== '') {
            $result = \mod_data_external::search_entries((int) $cm->instance, 0, $includecontents, $search, [], null, 'ASC', max(0, $page), max(0, $perpage));
        } else {
            $result = \mod_data_external::get_entries((int) $cm->instance, 0, $includecontents, null, 'ASC', max(0, $page), max(0, $perpage));
        }

        return [
            'count' => (int) ($result['totalcount'] ?? $result['count'] ?? count($result['entries'] ?? [])),
            'entries' => array_map(
                static fn($entry): array => self::entry_to_response($cm, (array) $entry),
                $result['entries'] ?? []
            ),
        ];
    }

    /**
     * Return a Database entry and ensure it belongs to the selected module.
     *
     * @param \cm_info $cm Database course module.
     * @param int $entryid Entry id.
     * @param bool $includecontents Include field contents.
     * @return array
     */
    public static function get_entry(\cm_info $cm, int $entryid, bool $includecontents = true): array {
        self::require_data_api();

        $result = \mod_data_external::get_entry($entryid, $includecontents);
        $entry = (array) ($result['entry'] ?? $result);
        if ((int) ($entry['dataid'] ?? $entry['databaseid'] ?? 0) !== (int) $cm->instance) {
            throw new \invalid_parameter_exception('entry_id must reference an entry in the selected database activity.');
        }

        return self::entry_to_response($cm, $entry);
    }

    /**
     * Convert a Database field payload to the canonical response shape.
     *
     * @param \cm_info $cm Database course module.
     * @param array $field Moodle field payload.
     * @return array
     */
    public static function field_to_response(\cm_info $cm, array $field): array {
        return [
            'field_id' => (int) ($field['id'] ?? 0),
            'data_id' => (int) ($field['dataid'] ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'type' => (string) ($field['type'] ?? ''),
            'name' => (string) ($field['name'] ?? ''),
            'description' => (string) ($field['description'] ?? ''),
            'required' => (bool) ($field['required'] ?? false),
            'params_json' => self::encode_params($field),
        ];
    }

    /**
     * Convert a Database entry payload to the canonical response shape.
     *
     * @param \cm_info $cm Database course module.
     * @param array $entry Moodle entry payload.
     * @return array
     */
    public static function entry_to_response(\cm_info $cm, array $entry): array {
        return [
            'entry_id' => (int) ($entry['id'] ?? 0),
            'data_id' => (int) ($entry['dataid'] ?? $entry['databaseid'] ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'user_id' => (int) ($entry['userid'] ?? 0),
            'group_id' => (int) ($entry['groupid'] ?? 0),
            'approved' => (bool) ($entry['approved'] ?? false),
            'time_created' => (int) ($entry['timecreated'] ?? 0),
            'time_modified' => (int) ($entry['timemodified'] ?? 0),
            'contents_json' => self::encode_contents($entry['contents'] ?? []),
        ];
    }

    /**
     * Convert field parameters to JSON.
     *
     * @param array $field Field payload.
     * @return string
     */
    private static function encode_params(array $field): string {
        $params = [];
        foreach ($field as $key => $value) {
            if (preg_match('/^param\d+$/', (string) $key) && (is_scalar($value) || $value === null)) {
                $params[$key] = $value;
            }
        }

        $encoded = json_encode($params, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Convert entry contents to JSON.
     *
     * @param mixed $contents Entry contents.
     * @return string
     */
    private static function encode_contents($contents): string {
        $encoded = json_encode($contents, JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '[]' : $encoded;
    }
}
