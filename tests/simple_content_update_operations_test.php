<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Simple content update operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\update_label;
use local_moodlia\operation\update_url;

/**
 * Verifies identity-preserving Text and media and URL updates.
 *
 * @covers \local_moodlia\operation\update_label
 * @covers \local_moodlia\operation\update_url
 */
final class simple_content_update_operations_test extends advanced_testcase {
    /** A Text and media update preserves identity and publishes every draft file. */
    public function test_update_label_content_with_multiple_files(): void {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $label = $this->getDataGenerator()->create_module('label', [
            'course' => $course->id,
            'intro' => '<p>Original</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $cm = get_coursemodule_from_instance('label', $label->id, $course->id, false, MUST_EXIST);
        $draftitemid = $this->create_draft_files([
            '/images/' => ['hero image.jpg' => 'Hero bytes'],
            '/' => ['diagram.svg' => '<svg></svg>'],
        ]);

        $updated = update_label::execute(
            (int) $course->id,
            (int) $cm->id,
            '<p><img src="@@PLUGINFILE@@/images/hero%20image.jpg"></p>',
            'html',
            'diagram.svg',
            '',
            $draftitemid
        );

        $stored = $DB->get_record('label', ['id' => $label->id], '*', MUST_EXIST);
        $this->assertSame((int) $cm->id, $updated['module_id']);
        $this->assertSame((int) $label->id, $updated['instance_id']);
        $this->assertStringContainsString('@@PLUGINFILE@@/images/hero%20image.jpg', $stored->intro);
        $this->assertCount(2, $updated['files']);
    }

    /** A URL update preserves dynamic parameters and unrelated display settings. */
    public function test_update_url_preserves_parameters_and_identity(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', [
            'course' => $course->id,
            'name' => 'Original URL',
            'externalurl' => 'https://example.com/original',
            'display' => RESOURCELIB_DISPLAY_POPUP,
            'popupwidth' => 777,
            'popupheight' => 555,
        ]);
        $url->parameters = serialize(['id' => 'courseid']);
        $DB->update_record('url', $url);
        $cm = get_coursemodule_from_instance('url', $url->id, $course->id, false, MUST_EXIST);

        $updated = update_url::execute(
            (int) $course->id,
            (int) $cm->id,
            'Updated URL',
            'https://example.org/updated'
        );

        $stored = $DB->get_record('url', ['id' => $url->id], '*', MUST_EXIST);
        $this->assertSame((int) $cm->id, $updated['module_id']);
        $this->assertSame((int) $url->id, $updated['instance_id']);
        $this->assertSame('Updated URL', $stored->name);
        $this->assertSame('https://example.org/updated', $stored->externalurl);
        $this->assertSame(['id' => 'courseid'], unserialize($stored->parameters));
        $displayoptions = unserialize($stored->displayoptions);
        $this->assertSame(777, $displayoptions['popupwidth']);
        $this->assertSame(555, $displayoptions['popupheight']);
    }

    /**
     * Create a Moodle user draft containing files grouped by filepath.
     *
     * @param array $files Files.
     * @return int
     */
    private function create_draft_files(array $files): int {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        $context = \context_user::instance((int) $USER->id);
        foreach ($files as $filepath => $pathfiles) {
            foreach ($pathfiles as $filename => $content) {
                get_file_storage()->create_file_from_string([
                    'contextid' => $context->id,
                    'component' => 'user',
                    'filearea' => 'draft',
                    'itemid' => $draftitemid,
                    'filepath' => $filepath,
                    'filename' => $filename,
                ], $content);
            }
        }
        return $draftitemid;
    }
}
