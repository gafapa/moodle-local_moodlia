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
 * Course backup operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\course_backup_tools;

/**
 * Exercises restore precheck result handling.
 */
final class course_backup_tools_test extends \advanced_testcase {
    /**
     * Warnings retain their useful messages and stable operation code.
     */
    public function test_normalise_precheck_notices_preserves_messages(): void {
        $method = new \ReflectionMethod(course_backup_tools::class, 'normalise_precheck_notices');
        $result = $method->invoke(null, [
            '<strong>Question bank</strong> warning',
            ['message' => 'Missing optional activity'],
            'Question bank warning',
        ], 'restore_precheck_warning');

        $this->assertSame([
            [
                'code' => 'restore_precheck_warning',
                'message' => 'Question bank warning',
            ],
            [
                'code' => 'restore_precheck_warning',
                'message' => 'Missing optional activity',
            ],
        ], $result);
    }

    /**
     * Fatal errors are combined into the detail returned by Moodle REST.
     */
    public function test_precheck_message_combines_normalised_errors(): void {
        $method = new \ReflectionMethod(course_backup_tools::class, 'precheck_message');
        $result = $method->invoke(null, [
            ['code' => 'restore_precheck_error', 'message' => 'Unsupported activity type'],
            ['code' => 'restore_precheck_error', 'message' => 'Missing required plugin'],
        ]);

        $this->assertSame('Unsupported activity type; Missing required plugin', $result);
    }
}
