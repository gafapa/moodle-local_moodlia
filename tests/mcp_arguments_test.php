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
 * MCP argument normalisation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\mcp\arguments;
use local_moodlia\mcp\manifest;

/**
 * Verifies that decoded MCP arguments map onto the REST parameter shapes.
 *
 * @covers \local_moodlia\mcp\arguments
 */
final class mcp_arguments_test extends advanced_testcase {
    /**
     * Return the input schema published for one tool.
     *
     * @param string $name Name.
     * @return array
     */
    private function schema(string $name): array {
        foreach (manifest::tools() as $tool) {
            if ($tool['name'] === $name) {
                return $tool['inputSchema'];
            }
        }
        $this->fail('Missing MCP tool ' . $name);
    }

    /**
     * Object-typed options from a JSON-decoded request become one JSON string (issue #3).
     */
    public function test_object_options_are_json_encoded(): void {
        $decoded = json_decode(
            '{"course_id":11,"module_id":44,"options":{"content":"Página «generada»","visible":true}}',
            true
        );

        $normalized = arguments::normalize($decoded, $this->schema('update_module'));

        $this->assertSame('11', $normalized['course_id']);
        $this->assertSame('44', $normalized['module_id']);
        $this->assertArrayNotHasKey('options[0]', $normalized);
        $this->assertSame(['content' => 'Página «generada»', 'visible' => true], json_decode($normalized['options'], true));
    }

    /**
     * Object-typed parameters that carry JSON lists are encoded instead of rejected.
     */
    public function test_object_typed_list_is_json_encoded(): void {
        $criteria = [['description' => 'Clarity', 'levels' => [['score' => 0, 'definition' => 'None']]]];

        $normalized = arguments::normalize(['criteria' => $criteria], $this->schema('set_assignment_rubric'));

        $this->assertSame($criteria, json_decode($normalized['criteria'], true));
    }

    /**
     * Pre-encoded JSON strings, empty objects, and scalars keep their REST form.
     */
    public function test_strings_empty_objects_and_scalars(): void {
        $normalized = arguments::normalize([
            'options' => '{"content":"x"}',
            'module_id' => 5,
            'visible' => false,
            'name' => null,
        ], $this->schema('update_module'));

        $this->assertSame('{"content":"x"}', $normalized['options']);
        $this->assertSame('5', $normalized['module_id']);
        $this->assertSame('0', $normalized['visible']);
        $this->assertArrayNotHasKey('name', $normalized);
        $this->assertSame('{}', arguments::normalize(['options' => []], $this->schema('update_module'))['options']);
    }

    /**
     * Scalar lists keep Moodle's indexed form parameters.
     */
    public function test_scalar_lists_use_indexed_parameters(): void {
        $normalized = arguments::normalize(['required_module_ids' => [3, 7]]);

        $this->assertSame(['required_module_ids[0]' => '3', 'required_module_ids[1]' => '7'], $normalized);
    }

    /**
     * Non-object arguments and nested untyped lists are rejected.
     */
    public function test_invalid_shapes_are_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        arguments::normalize(['ids' => [[1]]]);
    }

    /**
     * A scalar in place of the arguments object is rejected.
     */
    public function test_non_object_arguments_are_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        arguments::normalize('course_id=1');
    }
}
