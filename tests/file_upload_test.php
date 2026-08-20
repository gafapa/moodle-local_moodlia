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
 * File upload operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use local_moodlia\operation\course_backup_tools;

/**
 * Exercises streamed Moodle draft uploads and legacy base64 compatibility.
 */
final class file_upload_test extends \advanced_testcase {
    /**
     * A draft file is moved into the current user's private backup area.
     */
    public function test_upload_backup_file_consumes_current_user_draft(): void {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $filename = 'streamed-backup.mbz';
        $content = 'native Moodle backup bytes';
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance((int) $USER->id);
        $fs = get_file_storage();
        $draftfile = $fs->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);

        $result = course_backup_tools::upload_backup_file($filename, '', $draftitemid);

        $this->assertSame($filename, $result['filename']);
        $this->assertSame(strlen($content), $result['filesize']);
        $this->assertFalse($fs->file_exists_by_hash($draftfile->get_pathnamehash()));
        $privatefile = $fs->get_file($usercontext->id, 'user', 'private', 0, '/', $filename);
        $this->assertInstanceOf(\stored_file::class, $privatefile);
        $this->assertSame($content, $privatefile->get_content());
    }

    /**
     * Existing integrations can continue sending base64 content.
     */
    public function test_upload_backup_file_retains_legacy_base64_support(): void {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $filename = 'legacy-backup.mbz';
        $content = 'legacy backup bytes';
        $result = course_backup_tools::upload_backup_file($filename, base64_encode($content));

        $this->assertSame($filename, $result['filename']);
        $usercontext = \context_user::instance((int) $USER->id);
        $privatefile = get_file_storage()->get_file($usercontext->id, 'user', 'private', 0, '/', $filename);
        $this->assertInstanceOf(\stored_file::class, $privatefile);
        $this->assertSame($content, $privatefile->get_content());
    }

    /**
     * Draft ids cannot be used to access another user's files.
     */
    public function test_upload_backup_file_rejects_another_users_draft(): void {
        $this->resetAfterTest(true);

        $otheruser = $this->getDataGenerator()->create_user();
        $othercontext = \context_user::instance((int) $otheruser->id);
        $draftitemid = 97431;
        get_file_storage()->create_file_from_string([
            'contextid' => $othercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'foreign-backup.mbz',
        ], 'foreign backup bytes');

        $this->setAdminUser();
        $this->expectException(\invalid_parameter_exception::class);
        course_backup_tools::upload_backup_file('foreign-backup.mbz', '', $draftitemid);
    }
}
