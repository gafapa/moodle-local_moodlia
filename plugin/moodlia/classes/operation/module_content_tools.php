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
 * Shared helpers for simple Moodle content modules.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles module options for content-oriented activities and resources.
 */
class module_content_tools {
    /**
     * Add page-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_page_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->content = (string) ($options['content'] ?? '');
        $moduleinfo->contentformat = FORMAT_HTML;
        $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
        $moduleinfo->printintro = self::optional_bool($options, 'print_intro', 0);
        $moduleinfo->printlastmodified = self::optional_bool($options, 'print_last_modified', 1);
        $moduleinfo->revision = 1;
    }

    /**
     * Add question-bank-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_qbank_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;
    }

    /**
     * Add book-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_book_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = (string) ($options['intro'] ?? $options['description'] ?? '');
        $moduleinfo->introformat = FORMAT_HTML;

        $numbering = clean_param((string) ($options['numbering'] ?? 'numbers'), PARAM_ALPHA);
        $numberingmap = [
            'none' => BOOK_NUM_NONE,
            'numbers' => BOOK_NUM_NUMBERS,
            'bullets' => BOOK_NUM_BULLETS,
            'indented' => BOOK_NUM_INDENTED,
        ];
        if (!array_key_exists($numbering, $numberingmap)) {
            throw new \invalid_parameter_exception('options.numbering must be one of: none, numbers, bullets, indented.');
        }

        $moduleinfo->numbering = $numberingmap[$numbering];
        $moduleinfo->customtitles = array_key_exists('custom_titles', $options)
            ? (int) (bool) $options['custom_titles']
            : 0;
    }

    /**
     * Add folder-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_folder_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->files = 0;
        $moduleinfo->showexpanded = self::optional_bool($options, 'show_expanded', 1);
        $moduleinfo->showdownloadfolder = self::optional_bool($options, 'show_download_folder', 1);
        $moduleinfo->forcedownload = self::optional_bool($options, 'force_download', 0);
        $moduleinfo->display = self::normalise_folder_display($options['display'] ?? 'separate');
        $moduleinfo->revision = 1;
    }

    /**
     * Add label-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_label_options(\stdClass $moduleinfo, array $options): void {
        $content = (string) ($options['content'] ?? $options['intro'] ?? '');
        if (trim(strip_tags($content)) === '') {
            throw new \invalid_parameter_exception('options.content is required for label modules.');
        }

        $moduleinfo->intro = $content;
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->showdescription = 0;
    }

    /**
     * Add file resource-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_resource_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->files = module_file_tools::create_resource_draft_file(
            (string) ($options['filename'] ?? ''),
            (string) ($options['upload_reference'] ?? '')
        );
        $moduleinfo->display = self::normalise_resource_display((string) ($options['display'] ?? 'auto'), true);
        $moduleinfo->printintro = array_key_exists('print_intro', $options) ? (int) (bool) $options['print_intro'] : 1;
        $moduleinfo->showsize = array_key_exists('show_size', $options) ? (int) (bool) $options['show_size'] : 1;
        $moduleinfo->showtype = array_key_exists('show_type', $options) ? (int) (bool) $options['show_type'] : 1;
        $moduleinfo->showdate = array_key_exists('show_date', $options) ? (int) (bool) $options['show_date'] : 0;
        $moduleinfo->popupwidth = self::optional_int($options, 'popup_width', 620, 1);
        $moduleinfo->popupheight = self::optional_int($options, 'popup_height', 450, 1);
        $moduleinfo->filterfiles = self::normalise_filter_files((string) ($options['filter_files'] ?? 'none'));
        $moduleinfo->revision = 1;
    }

    /**
     * Add subsection-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_subsection_options(\stdClass $moduleinfo, array $options): void {
        $moduleinfo->intro = '';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->showdescription = 0;
    }

    /**
     * Add URL-specific fields to module info.
     *
     * @param \stdClass $moduleinfo Module info object.
     * @param array $options Module options.
     */
    public static function apply_url_options(\stdClass $moduleinfo, array $options): void {
        $externalurl = trim((string) ($options['external_url'] ?? $options['url'] ?? ''));
        if ($externalurl === '') {
            throw new \invalid_parameter_exception('options.external_url is required for url modules.');
        }

        if (!preg_match('/^https?:\/\/[^\s]+$/i', $externalurl)) {
            throw new \invalid_parameter_exception('options.external_url must be an absolute http or https URL.');
        }

        $moduleinfo->externalurl = $externalurl;
        $moduleinfo->display = self::normalise_resource_display((string) ($options['display'] ?? 'open'), false);
        $moduleinfo->printintro = array_key_exists('print_intro', $options) ? (int) (bool) $options['print_intro'] : 1;
        $moduleinfo->popupwidth = self::optional_int($options, 'popup_width', 620, 1);
        $moduleinfo->popupheight = self::optional_int($options, 'popup_height', 450, 1);
        $moduleinfo->revision = 1;
    }

    /**
     * Return an optional boolean module option as an integer.
     *
     * @param array $options Module options.
     * @param string $name Public option name.
     * @param int $default Default integer value.
     * @return int
     */
    private static function optional_bool(array $options, string $name, int $default): int {
        return array_key_exists($name, $options) ? (int) (bool) $options[$name] : $default;
    }

    /**
     * Return an optional positive integer module option.
     *
     * @param array $options Module options.
     * @param string $name Public option name.
     * @param int $default Default integer value.
     * @param int $minimum Minimum accepted value.
     * @return int
     */
    private static function optional_int(array $options, string $name, int $default, int $minimum = 0): int {
        $value = array_key_exists($name, $options) ? (int) $options[$name] : $default;
        if ($value < $minimum) {
            throw new \invalid_parameter_exception("options.$name must be at least $minimum.");
        }

        return $value;
    }

    /**
     * Map a public resource display value to Moodle constants.
     *
     * @param string $value Public display value.
     * @param bool $allowdownload Whether the file-only download mode is allowed.
     * @return int
     */
    private static function normalise_resource_display(string $value, bool $allowdownload): int {
        $display = clean_param($value, PARAM_ALPHA);
        $displaymap = [
            'auto' => RESOURCELIB_DISPLAY_AUTO,
            'embed' => RESOURCELIB_DISPLAY_EMBED,
            'new' => RESOURCELIB_DISPLAY_NEW,
            'open' => RESOURCELIB_DISPLAY_OPEN,
            'popup' => RESOURCELIB_DISPLAY_POPUP,
        ];
        if ($allowdownload) {
            $displaymap['download'] = defined('RESOURCELIB_DISPLAY_DOWNLOAD') ? RESOURCELIB_DISPLAY_DOWNLOAD : 4;
        }
        if (!array_key_exists($display, $displaymap)) {
            $allowed = $allowdownload ? 'auto, embed, download, new, open, popup' : 'auto, embed, new, open, popup';
            throw new \invalid_parameter_exception("options.display must be one of: $allowed.");
        }

        return $displaymap[$display];
    }

    /**
     * Map a public folder display value to Moodle constants.
     *
     * @param mixed $value Public display value.
     * @return int
     */
    private static function normalise_folder_display($value): int {
        if (is_int($value) || ctype_digit((string) $value)) {
            $display = (int) $value;
            if (in_array($display, [0, 1], true)) {
                return $display;
            }
        }

        $display = clean_param((string) $value, PARAM_ALPHAEXT);
        $displaymap = [
            'separate' => defined('FOLDER_DISPLAY_PAGE') ? FOLDER_DISPLAY_PAGE : 0,
            'page' => defined('FOLDER_DISPLAY_PAGE') ? FOLDER_DISPLAY_PAGE : 0,
            'course' => defined('FOLDER_DISPLAY_INLINE') ? FOLDER_DISPLAY_INLINE : 1,
            'inline' => defined('FOLDER_DISPLAY_INLINE') ? FOLDER_DISPLAY_INLINE : 1,
        ];
        if (!array_key_exists($display, $displaymap)) {
            throw new \invalid_parameter_exception('options.display must be one of: separate, course.');
        }

        return $displaymap[$display];
    }

    /**
     * Map public resource filter values.
     *
     * @param string $value Public filter value.
     * @return int
     */
    private static function normalise_filter_files(string $value): int {
        $key = clean_param($value, PARAM_ALPHA);
        $map = [
            'none' => 0,
            'all' => 1,
            'html' => 2,
        ];
        if (!array_key_exists($key, $map)) {
            throw new \invalid_parameter_exception('options.filter_files must be one of: none, all, html.');
        }

        return $map[$key];
    }
}
