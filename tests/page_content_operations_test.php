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
 * Page content operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\create_module;
use local_moodlia\operation\get_module_details;
use local_moodlia\operation\update_page;


/**
 * Verifies identity-preserving Page authoring updates.
 *
 * @covers \local_moodlia\operation\update_page
 */
final class page_content_operations_test extends advanced_testcase {
    /**
     * Page creation publishes an editor draft after allocating the module identity.
     */
    public function test_create_page_with_editor_draft(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance((int) $USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'created.png',
        ], 'Created bytes');

        $created = create_module::execute((int) $course->id, 0, 'page', 'Created Page', [
            'content' => '<p><img src="@@PLUGINFILE@@/created.png"></p>',
            'filename' => 'created.png',
            'draft_item_id' => $draftitemid,
        ]);

        $context = \context_module::instance((int) $created['module_id']);
        $this->assertNotFalse(get_file_storage()->get_file(
            $context->id,
            'mod_page',
            'content',
            0,
            '/',
            'created.png'
        ));

        $details = get_module_details::execute((int) $course->id, (int) $created['module_id']);
        $activity = json_decode($details['extra_json'], true, 512, JSON_THROW_ON_ERROR)['activity'];
        $this->assertGreaterThan(0, $activity['revision']);
        $this->assertCount(1, $activity['files']);
        $this->assertStringContainsString(
            '/mod_page/content/' . $activity['revision'] . '/created.png',
            $activity['files'][0]['url']
        );
    }

    /**
     * A Page update publishes every file from one draft without replacing the module.
     */
    public function test_update_page_content_with_multiple_files(): void {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Original Page',
            'content' => '<p>Original</p>',
        ]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance((int) $USER->id);
        $filestorage = get_file_storage();
        $filestorage->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'hero image.jpg',
        ], 'Hero bytes');
        $filestorage->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/diagrams/',
            'filename' => 'flow.svg',
        ], '<svg></svg>');
        $content = '<p><img src="@@PLUGINFILE@@/hero%20image.jpg">'
            . '<img src="@@PLUGINFILE@@/diagrams/flow.svg"></p>';

        $updated = update_page::execute(
            (int) $course->id,
            (int) $cm->id,
            'Updated Page',
            $content,
            'html',
            true,
            false,
            'hero image.jpg',
            '',
            $draftitemid
        );

        $stored = $DB->get_record('page', ['id' => $page->id], '*', MUST_EXIST);
        $this->assertSame((int) $cm->id, $updated['module_id']);
        $this->assertSame((int) $page->id, $updated['instance_id']);
        $this->assertSame('Updated Page', $stored->name);
        $this->assertStringContainsString('@@PLUGINFILE@@/hero%20image.jpg', $stored->content);
        $this->assertCount(2, $updated['files']);
        $modulecontext = \context_module::instance((int) $cm->id);
        $this->assertNotFalse($filestorage->get_file(
            $modulecontext->id,
            'mod_page',
            'content',
            0,
            '/diagrams/',
            'flow.svg'
        ));
    }
}
