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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * MCP tool argument normalisation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\mcp;

/**
 * Converts decoded MCP tool arguments into Moodle REST form parameters.
 *
 * Contract parameters declared as JSON objects are exposed by the REST
 * functions as JSON-encoded strings, so they are re-encoded here. Scalar
 * lists keep Moodle's indexed form encoding.
 */
final class arguments {
    /**
     * Normalise decoded tool arguments for the REST transport.
     *
     * @param mixed $arguments Arguments.
     * @param array $schema Schema.
     * @return array Form parameters keyed by Moodle REST parameter name.
     * @throws \invalid_parameter_exception When the arguments cannot be represented.
     */
    public static function normalize($arguments, array $schema = []): array {
        if ($arguments === null) {
            return [];
        }
        if (!is_array($arguments)) {
            throw new \invalid_parameter_exception('Tool arguments must be an object.');
        }

        $properties = $schema['properties'] ?? [];
        $normalized = [];
        foreach ($arguments as $key => $value) {
            if ($value === null) {
                continue;
            }

            $declaredtype = $properties[$key]['type'] ?? null;
            if ($declaredtype === 'object' || (is_array($value) && !array_is_list($value))) {
                $normalized[$key] = is_string($value) ? $value : self::encode_json($value);
                continue;
            }

            if (is_bool($value)) {
                $normalized[$key] = $value ? '1' : '0';
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $index => $item) {
                    if (is_array($item) || is_object($item)) {
                        throw new \invalid_parameter_exception('Nested tool argument arrays are not supported.');
                    }
                    $normalized[$key . '[' . $index . ']'] = is_bool($item) ? ($item ? '1' : '0') : (string) $item;
                }
                continue;
            }

            $normalized[$key] = is_object($value) ? self::encode_json($value) : (string) $value;
        }

        return $normalized;
    }

    /**
     * Encode a JSON-object argument, keeping an empty value as an object.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function encode_json($value): string {
        if ($value === []) {
            return '{}';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
