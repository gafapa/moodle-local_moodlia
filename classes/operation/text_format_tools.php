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
 * Shared public text format names.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Converts between public text format names and Moodle format constants.
 *
 * Public names are html, plain, markdown, and moodle. The numeric Moodle
 * constants (0, 1, 2, 4) are accepted as legacy aliases.
 */
class text_format_tools {
    /** @var string[] Public format names in contract order. */
    public const NAMES = ['html', 'plain', 'markdown', 'moodle'];

    /**
     * Convert a public format name or legacy constant to a Moodle format constant.
     *
     * @param mixed $format Format.
     * @param string $parameter Parameter.
     * @param int $default Default.
     * @return int
     */
    public static function to_constant($format, string $parameter = 'format', int $default = FORMAT_HTML): int {
        if ($format === null || $format === '') {
            return $default;
        }
        if (is_int($format) || (is_string($format) && preg_match('/^\d+$/', trim($format)))) {
            $constant = (int) $format;
            if (in_array($constant, [FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_MARKDOWN], true)) {
                return $constant;
            }
        } else {
            switch (strtolower(trim((string) $format))) {
                case 'html':
                    return FORMAT_HTML;
                case 'plain':
                    return FORMAT_PLAIN;
                case 'markdown':
                    return FORMAT_MARKDOWN;
                case 'moodle':
                    return FORMAT_MOODLE;
            }
        }

        throw new \invalid_parameter_exception($parameter . ' must be one of: ' . implode(', ', self::NAMES) . '.');
    }

    /**
     * Convert a Moodle format constant to its public name.
     *
     * @param int $format Format.
     * @return string
     */
    public static function to_name(int $format): string {
        switch ($format) {
            case FORMAT_PLAIN:
                return 'plain';
            case FORMAT_MARKDOWN:
                return 'markdown';
            case FORMAT_MOODLE:
                return 'moodle';
            default:
                return 'html';
        }
    }
}
