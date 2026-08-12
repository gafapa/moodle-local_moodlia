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
 * Shared completion helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle completion operations.
 */
class completion_tools {
    /**
     * Load Moodle completion APIs.
     */
    public static function require_completion_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/completion/classes/external.php');
        require_once($CFG->libdir . '/completionlib.php');
    }

    /**
     * Convert Moodle warnings to the canonical response shape.
     *
     * @param array $warnings Moodle warning payloads.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $items = [];
        foreach ($warnings as $warning) {
            $warning = self::to_array($warning);
            $items[] = [
                'item' => (string) ($warning['item'] ?? ''),
                'item_id' => (int) ($warning['itemid'] ?? 0),
                'warning_code' => (string) ($warning['warningcode'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Return Moodle's flexible values as stable JSON.
     *
     * @param mixed $value Value to encode.
     * @return string
     */
    public static function json_value($value): string {
        $encoded = json_encode(self::to_array($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Convert objects and nested arrays to arrays.
     *
     * @param mixed $value Value to convert.
     * @return array
     */
    public static function to_array($value): array {
        if (is_array($value)) {
            return array_map(static function($item) {
                return is_object($item) || is_array($item) ? self::to_array($item) : $item;
            }, $value);
        }

        if (is_object($value)) {
            return self::to_array(get_object_vars($value));
        }

        return [];
    }
}
