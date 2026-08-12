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
 * Shared wiki helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle wiki operations.
 */
class wiki_tools {
    /**
     * Load Moodle wiki APIs.
     */
    public static function require_wiki_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/wiki/lib.php');
        require_once($CFG->dirroot . '/mod/wiki/locallib.php');
        require_once($CFG->dirroot . '/mod/wiki/classes/external.php');
    }

    /**
     * Verify that a course module belongs to a wiki activity.
     *
     * @param \stdClass $course Moodle course.
     * @param int $cmid Course module id.
     * @return \cm_info
     */
    public static function get_wiki_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'wiki') {
            throw new \invalid_parameter_exception('module_id must reference a wiki activity.');
        }

        return $cm;
    }

    /**
     * Validate a wiki content format.
     *
     * @param string $format Wiki content format.
     * @return string
     */
    public static function validate_content_format(string $format): string {
        self::require_wiki_api();

        $format = clean_param($format, PARAM_ALPHA);
        if (!in_array($format, wiki_get_formats(), true)) {
            throw new \invalid_parameter_exception('content_format must reference an installed wiki format.');
        }

        return $format;
    }

    /**
     * Return a canonical wiki page response.
     *
     * @param \cm_info $cm Wiki course module.
     * @param array $page Moodle wiki page data.
     * @return array
     */
    public static function page_to_response(\cm_info $cm, array $page): array {
        $url = new \moodle_url('/mod/wiki/view.php', ['pageid' => (int) $page['id']]);

        return [
            'page_id' => (int) $page['id'],
            'wiki_id' => (int) ($page['wikiid'] ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'subwiki_id' => (int) ($page['subwikiid'] ?? 0),
            'title' => (string) ($page['title'] ?? ''),
            'content' => (string) ($page['cachedcontent'] ?? ''),
            'content_format' => (string) ($page['contentformat'] ?? ''),
            'can_edit' => (bool) ($page['caneditpage'] ?? false),
            'first_page' => (bool) ($page['firstpage'] ?? false),
            'time_created' => (int) ($page['timecreated'] ?? 0),
            'time_modified' => (int) ($page['timemodified'] ?? 0),
            'url' => $url->out(false),
        ];
    }

    /**
     * Return a wiki page and ensure it belongs to the selected module.
     *
     * @param \cm_info $cm Wiki course module.
     * @param int $pageid Wiki page id.
     * @return array
     */
    public static function get_page(\cm_info $cm, int $pageid): array {
        self::require_wiki_api();

        $result = \mod_wiki_external::get_page_contents($pageid);
        $page = $result['page'] ?? [];
        if ((int) ($page['wikiid'] ?? 0) !== (int) $cm->instance) {
            throw new \invalid_parameter_exception('page_id must reference a page in the selected wiki module.');
        }

        return $page;
    }

    /**
     * Return visible pages for a wiki module.
     *
     * @param \cm_info $cm Wiki course module.
     * @param int $groupid Group id.
     * @param int $userid User id.
     * @param string $sortby Sort field.
     * @param string $sortdirection Sort direction.
     * @param bool $includecontent Include rendered content.
     * @return array
     */
    public static function get_pages(
        \cm_info $cm,
        int $groupid = -1,
        int $userid = 0,
        string $sortby = 'title',
        string $sortdirection = 'ASC',
        bool $includecontent = true
    ): array {
        self::require_wiki_api();

        $sortby = clean_param($sortby, PARAM_ALPHA);
        $sortdirection = strtoupper(clean_param($sortdirection, PARAM_ALPHA));
        if (!in_array($sortdirection, ['ASC', 'DESC'], true)) {
            throw new \invalid_parameter_exception('sort_direction must be one of: ASC, DESC.');
        }

        $result = \mod_wiki_external::get_subwiki_pages((int) $cm->instance, $groupid, $userid, [
            'sortby' => $sortby,
            'sortdirection' => $sortdirection,
            'includecontent' => $includecontent ? 1 : 0,
        ]);

        return $result['pages'] ?? [];
    }

    /**
     * Return subwikis visible to the current user.
     *
     * @param \cm_info $cm Wiki course module.
     * @return array
     */
    public static function get_subwikis(\cm_info $cm): array {
        self::require_wiki_api();

        $result = \mod_wiki_external::get_subwikis((int) $cm->instance);
        $subwikis = [];
        foreach (($result['subwikis'] ?? []) as $subwiki) {
            $subwikis[] = self::subwiki_to_response($cm, (array) $subwiki);
        }

        return [
            'subwikis' => $subwikis,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return files attached to a visible subwiki.
     *
     * @param \cm_info $cm Wiki course module.
     * @param int $groupid Group id.
     * @param int $userid User id.
     * @return array
     */
    public static function get_files(\cm_info $cm, int $groupid = -1, int $userid = 0): array {
        self::require_wiki_api();

        $result = \mod_wiki_external::get_subwiki_files((int) $cm->instance, $groupid, $userid);
        $files = [];
        foreach (($result['files'] ?? []) as $file) {
            $files[] = self::file_to_response((array) $file);
        }

        return [
            'files' => $files,
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Register a wiki activity view through Moodle's external API.
     *
     * @param \cm_info $cm Wiki course module.
     * @return array
     */
    public static function view_wiki(\cm_info $cm): array {
        self::require_wiki_api();

        $result = \mod_wiki_external::view_wiki((int) $cm->instance);
        return self::view_to_response($result);
    }

    /**
     * Register a wiki page view through Moodle's external API.
     *
     * @param \cm_info $cm Wiki course module.
     * @param int $pageid Wiki page id.
     * @return array
     */
    public static function view_page(\cm_info $cm, int $pageid): array {
        self::get_page($cm, $pageid);

        $result = \mod_wiki_external::view_page($pageid);
        return self::view_to_response($result);
    }

    /**
     * Convert Moodle external warnings to the canonical response shape.
     *
     * @param array $warnings Moodle warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $items = [];
        foreach ($warnings as $warning) {
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
     * Convert a Moodle subwiki payload to the canonical response shape.
     *
     * @param \cm_info $cm Wiki course module.
     * @param array $subwiki Moodle subwiki data.
     * @return array
     */
    public static function subwiki_to_response(\cm_info $cm, array $subwiki): array {
        return [
            'subwiki_id' => (int) ($subwiki['id'] ?? 0),
            'wiki_id' => (int) ($subwiki['wikiid'] ?? $cm->instance),
            'module_id' => (int) $cm->id,
            'group_id' => (int) ($subwiki['groupid'] ?? 0),
            'user_id' => (int) ($subwiki['userid'] ?? 0),
            'can_edit' => (bool) ($subwiki['canedit'] ?? false),
        ];
    }

    /**
     * Convert a Moodle file payload to the canonical response shape.
     *
     * @param array $file Moodle file data.
     * @return array
     */
    public static function file_to_response(array $file): array {
        return [
            'file_name' => (string) ($file['filename'] ?? ''),
            'file_path' => (string) ($file['filepath'] ?? ''),
            'file_size' => (int) ($file['filesize'] ?? 0),
            'file_url' => (string) ($file['fileurl'] ?? ''),
            'time_modified' => (int) ($file['timemodified'] ?? 0),
            'mime_type' => (string) ($file['mimetype'] ?? ''),
            'is_external_file' => (bool) ($file['isexternalfile'] ?? false),
            'repository_type' => (string) ($file['repositorytype'] ?? ''),
        ];
    }

    /**
     * Convert a Moodle view result to the canonical response shape.
     *
     * @param array $result Moodle view result.
     * @return array
     */
    public static function view_to_response(array $result): array {
        return [
            'status' => (bool) ($result['status'] ?? false),
            'warnings' => self::warnings_to_response($result['warnings'] ?? []),
        ];
    }

    /**
     * Return wiki settings and page summaries exposed through Moodle wiki APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Wiki course module.
     * @return array
     */
    public static function get_wiki_details(\stdClass $course, \cm_info $cm): array {
        self::require_wiki_api();

        self::get_wiki_module($course, (int) $cm->id);

        $wiki = self::find_wiki_instance($course, $cm);
        $rawpages = self::get_pages($cm, -1, 0, 'title', 'ASC', false);
        $pages = [];

        foreach ($rawpages as $page) {
            $response = self::page_to_response($cm, (array) $page);
            $pages[] = [
                'page_id' => (int) $response['page_id'],
                'subwiki_id' => (int) $response['subwiki_id'],
                'title' => (string) $response['title'],
                'content_format' => (string) $response['content_format'],
                'can_edit' => (bool) $response['can_edit'],
                'first_page' => (bool) $response['first_page'],
                'time_created' => (int) $response['time_created'],
                'time_modified' => (int) $response['time_modified'],
                'url' => (string) $response['url'],
            ];
        }

        return [
            'wiki_id' => (int) $cm->instance,
            'wiki_mode' => (string) ($wiki['wikimode'] ?? $wiki['wiki_mode'] ?? ''),
            'first_page_title' => (string) ($wiki['firstpagetitle'] ?? $wiki['first_page_title'] ?? ''),
            'default_format' => (string) ($wiki['defaultformat'] ?? $wiki['default_format'] ?? ''),
            'force_format' => (int) ($wiki['forceformat'] ?? $wiki['force_format'] ?? 0),
            'edit_begin' => (int) ($wiki['editbegin'] ?? 0),
            'edit_end' => (int) ($wiki['editend'] ?? 0),
            'page_count' => count($pages),
            'pages' => $pages,
        ];
    }

    /**
     * Return a wiki instance payload from Moodle external APIs where available.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Wiki course module.
     * @return array
     */
    private static function find_wiki_instance(\stdClass $course, \cm_info $cm): array {
        if (!method_exists(\mod_wiki_external::class, 'get_wikis_by_courses')) {
            return [];
        }

        $result = \mod_wiki_external::get_wikis_by_courses([(int) $course->id]);
        $wikis = (array) ($result['wikis'] ?? $result);
        foreach ($wikis as $wiki) {
            $wiki = (array) $wiki;
            if (
                (int) ($wiki['id'] ?? 0) === (int) $cm->instance ||
                (int) ($wiki['coursemodule'] ?? $wiki['cmid'] ?? $wiki['coursemoduleid'] ?? 0) === (int) $cm->id
            ) {
                return $wiki;
            }
        }

        return [];
    }
}
