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
 * Text format, group, forum, glossary, and Lesson file operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

use advanced_testcase;
use local_moodlia\operation\create_forum_discussion;
use local_moodlia\operation\create_forum_discussion_post;
use local_moodlia\operation\create_glossary_entry;
use local_moodlia\operation\create_group;
use local_moodlia\operation\create_module;
use local_moodlia\operation\create_wiki_page;
use local_moodlia\operation\get_groups;
use local_moodlia\operation\text_format_tools;
use local_moodlia\operation\update_forum_discussion_post;
use local_moodlia\operation\update_group;

/**
 * Verifies shared text formats, group visibility and participation, and embedded files.
 *
 * @covers \local_moodlia\operation\text_format_tools
 * @covers \local_moodlia\operation\create_group
 * @covers \local_moodlia\operation\update_group
 * @covers \local_moodlia\operation\get_groups
 * @covers \local_moodlia\operation\create_forum_discussion
 * @covers \local_moodlia\operation\create_forum_discussion_post
 * @covers \local_moodlia\operation\update_forum_discussion_post
 * @covers \local_moodlia\operation\create_glossary_entry
 * @covers \local_moodlia\operation\module_file_tools
 * @covers \local_moodlia\external\create_book_chapter
 * @covers \local_moodlia\external\create_lesson_page
 * @covers \local_moodlia\external\update_lesson_page
 */
final class formats_groups_forums_test extends advanced_testcase {
    /**
     * Public format names and legacy constants map to Moodle formats.
     */
    public function test_text_format_names_and_legacy_constants(): void {
        $this->assertSame((int) FORMAT_HTML, text_format_tools::to_constant('html'));
        $this->assertSame((int) FORMAT_PLAIN, text_format_tools::to_constant('plain'));
        $this->assertSame((int) FORMAT_MARKDOWN, text_format_tools::to_constant('markdown'));
        $this->assertSame((int) FORMAT_MOODLE, text_format_tools::to_constant('moodle'));
        $this->assertSame((int) FORMAT_MARKDOWN, text_format_tools::to_constant('4'));
        $this->assertSame((int) FORMAT_PLAIN, text_format_tools::to_constant(2));
        $this->assertSame((int) FORMAT_HTML, text_format_tools::to_constant(''));
        $this->assertSame('markdown', text_format_tools::to_name(FORMAT_MARKDOWN));
        $this->assertSame('moodle', text_format_tools::to_name(FORMAT_MOODLE));

        $this->expectException(\invalid_parameter_exception::class);
        text_format_tools::to_constant('rtf', 'summary_format');
    }

    /**
     * Groups store format, visibility, participation, and a unique enrolment key without returning it.
     */
    public function test_create_group_with_visibility_participation_and_key(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $group = create_group::execute(
            (int) $course->id,
            'Team A',
            '**Bold** description',
            'team-a',
            'markdown',
            'members',
            false,
            'secret-key'
        );

        $stored = $DB->get_record('groups', ['id' => $group['group_id']], '*', MUST_EXIST);
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $stored->descriptionformat);
        $this->assertSame(GROUPS_VISIBILITY_MEMBERS, (int) $stored->visibility);
        $this->assertSame(0, (int) $stored->participation);
        $this->assertSame('secret-key', $stored->enrolmentkey);
        $this->assertSame('markdown', $group['description_format']);
        $this->assertSame('members', $group['visibility']);
        $this->assertFalse($group['participation']);
        $this->assertTrue($group['has_enrolment_key']);
        $this->assertArrayNotHasKey('enrolment_key', $group);

        $own = create_group::execute((int) $course->id, 'Private', '', '', 'html', 'own', true);
        $this->assertFalse($own['participation'], 'Moodle only allows participation for visible groups.');

        $listed = get_groups::execute((int) $course->id);
        $this->assertCount(2, $listed['groups']);
        $byname = array_column($listed['groups'], null, 'name');
        $this->assertSame('own', $byname['Private']['visibility']);
        $this->assertSame('members', $byname['Team A']['visibility']);

        $this->expectException(\invalid_parameter_exception::class);
        create_group::execute((int) $course->id, 'Team B', '', '', 'html', 'all', true, 'secret-key');
    }

    /**
     * Updates keep unspecified fields and refuse a visibility change once the group has members.
     */
    public function test_update_group_keeps_fields_and_protects_visibility_with_members(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $group = create_group::execute((int) $course->id, 'Team', 'Plain text', '', 'plain', 'all', true, 'key-1');

        $renamed = update_group::execute((int) $course->id, (int) $group['group_id'], 'Renamed');
        $this->assertSame('Renamed', $renamed['name']);
        $this->assertSame('plain', $renamed['description_format']);
        $this->assertTrue($renamed['has_enrolment_key']);

        $hidden = update_group::execute(
            (int) $course->id,
            (int) $group['group_id'],
            null,
            null,
            null,
            null,
            'none',
            null,
            ''
        );
        $this->assertSame('none', $hidden['visibility']);
        $this->assertFalse($hidden['participation']);
        $this->assertFalse($hidden['has_enrolment_key']);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        groups_add_member((int) $group['group_id'], (int) $user->id);
        $this->assertSame(
            GROUPS_VISIBILITY_NONE,
            (int) $DB->get_field('groups', 'visibility', ['id' => $group['group_id']])
        );

        $this->expectException(\invalid_parameter_exception::class);
        update_group::execute((int) $course->id, (int) $group['group_id'], null, null, null, null, 'all');
    }

    /**
     * Forum discussions publish inline draft files and replies honour the message format.
     */
    public function test_forum_messages_accept_formats_and_draft_files(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id, false, MUST_EXIST);
        $inline = $this->create_draft_file('diagram ü.png', 'image bytes');

        $discussion = create_forum_discussion::execute(
            (int) $course->id,
            (int) $cm->id,
            'Welcome',
            '<p><img src="@@PLUGINFILE@@/diagram%20%C3%BC.png"></p>',
            $inline
        );
        $firstpost = $DB->get_record('forum_posts', ['discussion' => $discussion['discussion_id'], 'parent' => 0], '*', MUST_EXIST);
        $modulecontext = \context_module::instance($cm->id);
        $files = get_file_storage()->get_area_files($modulecontext->id, 'mod_forum', 'post', $firstpost->id, 'filename', false);
        $this->assertSame(['diagram ü.png'], array_values(array_map(static fn($file) => $file->get_filename(), $files)));

        $reply = create_forum_discussion_post::execute(
            (int) $course->id,
            (int) $cm->id,
            (int) $discussion['discussion_id'],
            null,
            'Re: Welcome',
            '*Markdown* reply',
            'markdown'
        );
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $DB->get_field('forum_posts', 'messageformat', ['id' => $reply['post_id']]));

        update_forum_discussion_post::execute(
            (int) $course->id,
            (int) $cm->id,
            (int) $discussion['discussion_id'],
            (int) $reply['post_id'],
            null,
            'Edited *markdown* reply'
        );
        $this->assertSame(
            (int) FORMAT_MARKDOWN,
            (int) $DB->get_field('forum_posts', 'messageformat', ['id' => $reply['post_id']]),
            'An update without message_format keeps the stored format.'
        );
    }

    /**
     * Glossary definitions publish inline draft files through Moodle's glossary API.
     */
    public function test_glossary_entry_publishes_inline_draft_files(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $glossary = $this->getDataGenerator()->create_module('glossary', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('glossary', $glossary->id, $course->id, false, MUST_EXIST);
        $inline = $this->create_draft_file('term.png', 'term bytes');

        $entry = create_glossary_entry::execute(
            (int) $course->id,
            (int) $cm->id,
            'Term',
            '<p><img src="@@PLUGINFILE@@/term.png"></p>',
            'html',
            [],
            $inline
        );

        $files = get_file_storage()->get_area_files(
            \context_module::instance($cm->id)->id,
            'mod_glossary',
            'entry',
            $entry['entry_id'],
            'filename',
            false
        );
        $this->assertSame(['term.png'], array_values(array_map(static fn($file) => $file->get_filename(), $files)));
    }

    /**
     * Book chapters and Lesson pages accept format names and legacy constants, and Lesson pages publish drafts.
     */
    public function test_book_and_lesson_formats_and_lesson_files(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $bookcm = get_coursemodule_from_instance('book', $book->id, $course->id, false, MUST_EXIST);

        $named = \local_moodlia\external\create_book_chapter::execute(
            (int) $course->id,
            (int) $bookcm->id,
            'Named',
            '*text*',
            'markdown'
        );
        $legacy = \local_moodlia\external\create_book_chapter::execute(
            (int) $course->id,
            (int) $bookcm->id,
            'Legacy',
            'text',
            '2'
        );
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $DB->get_field('book_chapters', 'contentformat', ['id' => $named['chapter_id']]));
        $this->assertSame((int) FORMAT_PLAIN, (int) $DB->get_field('book_chapters', 'contentformat', ['id' => $legacy['chapter_id']]));

        $lesson = $this->getDataGenerator()->create_module('lesson', ['course' => $course->id]);
        $lessoncm = get_coursemodule_from_instance('lesson', $lesson->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($lessoncm->id);
        $draft = $this->create_draft_file('slide.png', 'slide bytes');
        $created = \local_moodlia\external\create_lesson_page::execute(
            (int) $course->id,
            (int) $lessoncm->id,
            'Intro',
            '<p><img src="@@PLUGINFILE@@/slide.png"></p>',
            'plain',
            '{"branches":[{"title":"Next","jump_to":-1}]}',
            0,
            true,
            true,
            'content',
            null,
            $draft
        );
        $pageid = (int) $created['page']['page_id'];
        $this->assertSame(1, $created['page']['files_count']);
        $this->assertSame((int) FORMAT_PLAIN, (int) $DB->get_field('lesson_pages', 'contentsformat', ['id' => $pageid]));
        $names = static fn(array $files): array => array_values(array_map(static fn($file) => $file->get_filename(), $files));
        $fs = get_file_storage();
        $this->assertSame(['slide.png'], $names($fs->get_area_files($context->id, 'mod_lesson', 'page_contents', $pageid, 'filename', false)));

        $second = $this->create_draft_file('chart.svg', '<svg></svg>');
        \local_moodlia\external\update_lesson_page::execute(
            (int) $course->id,
            (int) $lessoncm->id,
            $pageid,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $second
        );
        $this->assertSame(
            ['chart.svg', 'slide.png'],
            $names($fs->get_area_files($context->id, 'mod_lesson', 'page_contents', $pageid, 'filename', false)),
            'Updating a page adds draft files and keeps the existing ones.'
        );

        $this->expectException(\invalid_parameter_exception::class);
        \local_moodlia\external\create_book_chapter::execute((int) $course->id, (int) $bookcm->id, 'Bad', 'x', 'rtf');
    }

    /**
     * Wiki pages publish draft files to the subwiki, and new modules publish intro drafts.
     */
    public function test_wiki_and_module_intro_publish_drafts(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $wiki = $this->getDataGenerator()->create_module('wiki', ['course' => $course->id, 'firstpagetitle' => 'Home']);
        $wikicm = get_coursemodule_from_instance('wiki', $wiki->id, $course->id, false, MUST_EXIST);
        $page = create_wiki_page::execute(
            (int) $course->id,
            (int) $wikicm->id,
            'Diagram',
            '<p><img src="@@PLUGINFILE@@/wiki.png"></p>',
            'html',
            -1,
            0,
            $this->create_draft_file('wiki.png', 'wiki bytes')
        );
        $subwikiid = (int) $DB->get_field('wiki_pages', 'subwikiid', ['id' => $page['page_id']], MUST_EXIST);
        $files = get_file_storage()->get_area_files(
            \context_module::instance($wikicm->id)->id,
            'mod_wiki',
            'attachments',
            $subwikiid,
            'filename',
            false
        );
        $this->assertSame(['wiki.png'], array_values(array_map(static fn($file) => $file->get_filename(), $files)));

        $module = create_module::execute((int) $course->id, 1, 'page', 'Intro files', [
            'intro' => '*See the diagram*',
            'intro_format' => 'markdown',
            'intro_draft_item_id' => $this->create_draft_file('intro.png', 'intro bytes'),
            'content' => '<p>Body</p>',
        ]);
        $pagecm = get_coursemodule_from_id('page', $module['module_id'], $course->id, false, MUST_EXIST);
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $DB->get_field('page', 'introformat', ['id' => $pagecm->instance]));
        $introfiles = get_file_storage()->get_area_files(
            \context_module::instance($pagecm->id)->id,
            'mod_page',
            'intro',
            0,
            'filename',
            false
        );
        $this->assertSame(['intro.png'], array_values(array_map(static fn($file) => $file->get_filename(), $introfiles)));
    }

    /**
     * Create one file in the current user's draft area.
     *
     * @param string $filename Filename.
     * @param string $content Content.
     * @return int Draft item id.
     */
    private function create_draft_file(string $filename, string $content): int {
        global $USER;

        $draftitemid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance((int) $USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
        return $draftitemid;
    }
}
