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
 * Authored text readback tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\simple_activity_tools;

/**
 * Protects original editor text and format from rendered-text conversion.
 *
 * @covers \local_moodlia\operation\simple_activity_tools
 */
final class authored_content_read_test extends advanced_testcase {
    /**
     * Read every editor format, including empty text, without filtering it to HTML.
     */
    public function test_original_text_and_formats_are_preserved(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        foreach (['page', 'label', 'url'] as $type) {
            $activity = $this->getDataGenerator()->create_module($type, [
                'course' => $course->id,
                'name' => 'Portable content',
                'content' => 'Initial content',
                'intro' => 'Initial introduction',
                'externalurl' => 'https://example.org/',
            ]);
            $field = $type === 'page' ? 'content' : 'intro';
            $formatfield = $type === 'page' ? 'contentformat' : 'introformat';
            foreach ([FORMAT_HTML, FORMAT_PLAIN, FORMAT_MARKDOWN, FORMAT_MOODLE] as $format) {
                foreach (['A & B <literal> **Markdown** @@PLUGINFILE@@/notes.txt', ''] as $text) {
                    $DB->set_field($type, $field, $text, ['id' => $activity->id]);
                    $DB->set_field($type, $formatfield, $format, ['id' => $activity->id]);
                    rebuild_course_cache((int) $course->id, true);
                    $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
                    $method = 'get_' . $type . '_details';
                    $details = simple_activity_tools::$method($course, $cm);
                    $responsefield = $type === 'url' ? 'intro' : 'content';
                    $this->assertSame($text, $details[$responsefield]);
                    $this->assertSame((int) $format, $details[$responsefield . '_format']);
                }
            }
        }
    }
}
