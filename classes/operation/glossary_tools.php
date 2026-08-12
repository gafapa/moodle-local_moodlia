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
 * Shared glossary helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper methods for Moodle glossary operations.
 */
class glossary_tools {
    /**
     * Load Moodle glossary APIs.
     */
    public static function require_glossary_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/glossary/lib.php');
        require_once($CFG->dirroot . '/mod/glossary/classes/external.php');
        require_once($CFG->dirroot . '/mod/glossary/classes/external/update_entry.php');
        require_once($CFG->dirroot . '/mod/glossary/classes/external/delete_entry.php');
    }

    /**
     * Verify that a course module belongs to a glossary activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_glossary_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'glossary') {
            throw new \invalid_parameter_exception('module_id must reference a glossary activity.');
        }

        return $cm;
    }

    /**
     * Convert a public format name to a Moodle editor format constant.
     *
     * @param string $format Public format name.
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

        throw new \invalid_parameter_exception('definition_format must be one of: html, plain.');
    }

    /**
     * Convert a Moodle editor format constant to a public format name.
     *
     * @param int $format Moodle format constant.
     * @return string
     */
    public static function format_from_constant(int $format): string {
        return $format === FORMAT_PLAIN ? 'plain' : 'html';
    }

    /**
     * Convert public entry options to Moodle external option pairs.
     *
     * @param array $options Public options.
     * @return array
     */
    public static function options_to_external(array $options): array {
        $external = [];

        if (array_key_exists('aliases', $options)) {
            $aliases = $options['aliases'];
            if (is_array($aliases)) {
                $aliases = implode(',', array_map(static fn($alias): string => (string) $alias, $aliases));
            }
            $external[] = ['name' => 'aliases', 'value' => (string) $aliases];
        }

        foreach (['usedynalink', 'casesensitive', 'fullmatch'] as $name) {
            if (array_key_exists($name, $options)) {
                $external[] = ['name' => $name, 'value' => (string) (int) (bool) $options[$name]];
            }
        }

        return $external;
    }

    /**
     * Return a canonical glossary entry response.
     *
     * @param \cm_info $cm Glossary course module.
     * @param array|\stdClass $entry Moodle external entry record.
     * @return array
     */
    public static function entry_to_response(\cm_info $cm, $entry): array {
        $entry = (array) $entry;
        $url = new \moodle_url('/mod/glossary/showentry.php', ['eid' => (int) $entry['id']]);

        return [
            'entry_id' => (int) $entry['id'],
            'glossary_id' => (int) $entry['glossaryid'],
            'module_id' => (int) $cm->id,
            'concept' => (string) $entry['concept'],
            'definition' => (string) $entry['definition'],
            'definition_format' => self::format_from_constant((int) ($entry['definitionformat'] ?? FORMAT_HTML)),
            'approved' => (bool) ($entry['approved'] ?? false),
            'url' => $url->out(false),
        ];
    }

    /**
     * Normalize Moodle warning payloads.
     *
     * @param array $warnings Moodle warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $mapped = [];
        foreach ($warnings as $warning) {
            $item = is_array($warning) ? $warning : (array) $warning;
            $mapped[] = [
                'item' => (string) ($item['item'] ?? ''),
                'item_id' => (int) ($item['itemid'] ?? 0),
                'warning_code' => (string) ($item['warningcode'] ?? ''),
                'message' => (string) ($item['message'] ?? ''),
            ];
        }

        return $mapped;
    }

    /**
     * Return a canonical glossary summary response.
     *
     * @param \stdClass $course Moodle course.
     * @param array|\stdClass $glossary Moodle external glossary record.
     * @return array
     */
    public static function glossary_summary_to_response(\stdClass $course, $glossary): array {
        $glossary = (array) $glossary;

        return [
            'glossary_id' => (int) ($glossary['id'] ?? 0),
            'module_id' => (int) ($glossary['coursemodule'] ?? $glossary['cmid'] ?? $glossary['coursemoduleid'] ?? 0),
            'course_id' => (int) ($glossary['course'] ?? $course->id),
            'name' => (string) ($glossary['name'] ?? ''),
            'intro' => (string) ($glossary['intro'] ?? ''),
            'intro_format' => (int) ($glossary['introformat'] ?? FORMAT_MOODLE),
            'allow_duplicated_entries' => (bool) ($glossary['allowduplicatedentries'] ?? false),
            'display_format' => (string) ($glossary['displayformat'] ?? ''),
            'main_glossary' => (bool) ($glossary['mainglossary'] ?? false),
            'show_special' => (bool) ($glossary['showspecial'] ?? false),
            'show_alphabet' => (bool) ($glossary['showalphabet'] ?? false),
            'show_all' => (bool) ($glossary['showall'] ?? false),
            'allow_comments' => (bool) ($glossary['allowcomments'] ?? false),
            'allow_print_view' => (bool) ($glossary['allowprintview'] ?? false),
            'use_dynamic_linking' => (bool) ($glossary['usedynalink'] ?? false),
            'default_approval' => (bool) ($glossary['defaultapproval'] ?? false),
            'approval_display_format' => (string) ($glossary['approvaldisplayformat'] ?? ''),
            'global_glossary' => (bool) ($glossary['globalglossary'] ?? false),
            'entries_per_page' => (int) ($glossary['entbypage'] ?? 0),
            'edit_always' => (bool) ($glossary['editalways'] ?? false),
            'rss_type' => (int) ($glossary['rsstype'] ?? 0),
            'rss_articles' => (int) ($glossary['rssarticles'] ?? 0),
            'assessed' => (int) ($glossary['assessed'] ?? 0),
            'scale' => (int) ($glossary['scale'] ?? 0),
            'time_created' => (int) ($glossary['timecreated'] ?? 0),
            'time_modified' => (int) ($glossary['timemodified'] ?? 0),
            'completion_entries' => (int) ($glossary['completionentries'] ?? 0),
            'browse_modes' => array_values(array_map('strval', (array) ($glossary['browsemodes'] ?? []))),
            'can_add_entry' => (bool) ($glossary['canaddentry'] ?? false),
        ];
    }

    /**
     * Return a canonical course glossary listing response.
     *
     * @param \stdClass $course Moodle course.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function course_glossaries_to_response(\stdClass $course, array $result): array {
        $glossaries = [];
        foreach (($result['glossaries'] ?? []) as $glossary) {
            $glossaries[] = self::glossary_summary_to_response($course, $glossary);
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($glossaries),
            'glossaries' => $glossaries,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical glossary entry listing response.
     *
     * @param \cm_info $cm Glossary course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function entries_result_to_response(\cm_info $cm, array $result): array {
        return [
            'module_id' => (int) $cm->id,
            'glossary_id' => (int) $cm->instance,
            'count' => (int) ($result['count'] ?? 0),
            'entries' => array_map(
                static fn($entry): array => self::entry_to_response($cm, $entry),
                $result['entries'] ?? []
            ),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical glossary category listing response.
     *
     * @param \cm_info $cm Glossary course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function categories_to_response(\cm_info $cm, array $result): array {
        $categories = [];
        foreach (($result['categories'] ?? []) as $category) {
            $item = (array) $category;
            $categories[] = [
                'category_id' => (int) ($item['id'] ?? 0),
                'glossary_id' => (int) ($item['glossaryid'] ?? $cm->instance),
                'module_id' => (int) $cm->id,
                'name' => (string) ($item['name'] ?? ''),
                'use_dynamic_linking' => (bool) ($item['usedynalink'] ?? false),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'glossary_id' => (int) $cm->instance,
            'count' => (int) ($result['count'] ?? count($categories)),
            'categories' => $categories,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a canonical glossary author listing response.
     *
     * @param \cm_info $cm Glossary course module.
     * @param array $result Moodle external API result.
     * @return array
     */
    public static function authors_to_response(\cm_info $cm, array $result): array {
        $authors = [];
        foreach (($result['authors'] ?? []) as $author) {
            $item = (array) $author;
            $authors[] = [
                'user_id' => (int) ($item['id'] ?? 0),
                'full_name' => (string) ($item['fullname'] ?? ''),
                'picture_url' => (string) ($item['pictureurl'] ?? ''),
            ];
        }

        return [
            'module_id' => (int) $cm->id,
            'glossary_id' => (int) $cm->instance,
            'count' => (int) ($result['count'] ?? count($authors)),
            'authors' => $authors,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return a glossary entry and ensure it belongs to the selected module.
     *
     * @param \cm_info $cm Glossary course module.
     * @param int $entryid Glossary entry id.
     * @return array
     */
    public static function get_entry(\cm_info $cm, int $entryid): array {
        self::require_glossary_api();
        $result = \mod_glossary_external::get_entry_by_id($entryid);
        $entry = (array) ($result['entry'] ?? []);

        if ((int) ($entry['glossaryid'] ?? 0) !== (int) $cm->instance) {
            throw new \invalid_parameter_exception('entry_id must reference an entry in the selected glossary module.');
        }

        return $entry;
    }

    /**
     * Search entries in a glossary module.
     *
     * @param \cm_info $cm Glossary course module.
     * @param string $query Search query.
     * @param bool $fullsearch Whether to search definitions too.
     * @param string $order Sort field.
     * @param string $sort Sort direction.
     * @param int $from Offset.
     * @param int $limit Limit.
     * @param bool $includenotapproved Include entries pending approval where allowed.
     * @return array
     */
    public static function search_entries(
        \cm_info $cm,
        string $query,
        bool $fullsearch,
        string $order,
        string $sort,
        int $from,
        int $limit,
        bool $includenotapproved
    ): array {
        self::require_glossary_api();
        $result = \mod_glossary_external::get_entries_by_search(
            (int) $cm->instance,
            $query,
            $fullsearch,
            $order,
            $sort,
            max(0, $from),
            max(1, $limit),
            ['includenotapproved' => $includenotapproved]
        );

        return [
            'count' => (int) ($result['count'] ?? 0),
            'entries' => array_map(
                static fn($entry): array => self::entry_to_response($cm, $entry),
                $result['entries'] ?? []
            ),
        ];
    }

    /**
     * Return glossary settings and entry summaries exposed through Moodle glossary APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Glossary course module.
     * @return array
     */
    public static function get_glossary_details(\stdClass $course, \cm_info $cm): array {
        self::require_glossary_api();

        self::get_glossary_module($course, (int) $cm->id);

        $glossary = self::find_glossary_instance($course, $cm);
        $entriesresult = self::search_entries($cm, '', true, 'CONCEPT', 'ASC', 0, 20, true);
        $entries = [];
        foreach ($entriesresult['entries'] as $entry) {
            $entries[] = [
                'entry_id' => (int) $entry['entry_id'],
                'concept' => (string) $entry['concept'],
                'definition_format' => (string) $entry['definition_format'],
                'approved' => (bool) $entry['approved'],
                'url' => (string) $entry['url'],
            ];
        }

        return [
            'glossary_id' => (int) $cm->instance,
            'globalglossary' => (int) ($glossary['globalglossary'] ?? 0),
            'mainglossary' => (int) ($glossary['mainglossary'] ?? 0),
            'defaultapproval' => (int) ($glossary['defaultapproval'] ?? 0),
            'editalways' => (int) ($glossary['editalways'] ?? 0),
            'allowduplicatedentries' => (int) ($glossary['allowduplicatedentries'] ?? 0),
            'allowcomments' => (int) ($glossary['allowcomments'] ?? 0),
            'usedynalink' => (int) ($glossary['usedynalink'] ?? 0),
            'displayformat' => (string) ($glossary['displayformat'] ?? self::custom_data_value($cm, 'displayformat', '')),
            'approvaldisplayformat' => (string) ($glossary['approvaldisplayformat'] ?? ''),
            'entbypage' => (int) ($glossary['entbypage'] ?? 0),
            'showalphabet' => (int) ($glossary['showalphabet'] ?? 0),
            'showall' => (int) ($glossary['showall'] ?? 0),
            'showspecial' => (int) ($glossary['showspecial'] ?? 0),
            'allowprintview' => (int) ($glossary['allowprintview'] ?? 0),
            'assessed' => (int) ($glossary['assessed'] ?? 0),
            'scale' => (int) ($glossary['scale'] ?? 0),
            'entry_count' => (int) $entriesresult['count'],
            'entries' => $entries,
        ];
    }

    /**
     * Return a glossary instance payload from Moodle external APIs where available.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Glossary course module.
     * @return array
     */
    private static function find_glossary_instance(\stdClass $course, \cm_info $cm): array {
        if (!method_exists(\mod_glossary_external::class, 'get_glossaries_by_courses')) {
            return [];
        }

        $result = \mod_glossary_external::get_glossaries_by_courses([(int) $course->id]);
        $glossaries = (array) ($result['glossaries'] ?? $result);
        foreach ($glossaries as $glossary) {
            $glossary = (array) $glossary;
            if (
                (int) ($glossary['id'] ?? 0) === (int) $cm->instance ||
                (int) ($glossary['coursemodule'] ?? $glossary['cmid'] ?? $glossary['coursemoduleid'] ?? 0) === (int) $cm->id
            ) {
                return $glossary;
            }
        }

        return [];
    }

    /**
     * Return a scalar value from cm_info custom data when Moodle exposes it there.
     *
     * @param \cm_info $cm Course module info.
     * @param string $key Custom data key.
     * @param mixed $default Default value.
     * @return mixed
     */
    private static function custom_data_value(\cm_info $cm, string $key, $default) {
        $customdata = $cm->customdata ?? [];
        if (is_object($customdata)) {
            $customdata = (array) $customdata;
        }
        if (!is_array($customdata) || !array_key_exists($key, $customdata)) {
            return $default;
        }

        $value = $customdata[$key];
        return is_scalar($value) || $value === null ? $value : $default;
    }
}
