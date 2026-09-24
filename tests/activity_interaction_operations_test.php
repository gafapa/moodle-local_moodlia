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
 * Book, lesson, wiki, glossary, choice, and forum write operation tests.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');

use advanced_testcase;
use local_moodlia\operation\create_book_chapter;
use local_moodlia\operation\create_forum_discussion;
use local_moodlia\operation\create_forum_discussion_post;
use local_moodlia\operation\create_glossary_entry;
use local_moodlia\operation\create_lesson_page;
use local_moodlia\operation\create_wiki_page;
use local_moodlia\operation\delete_book_chapter;
use local_moodlia\operation\delete_choice_responses;
use local_moodlia\operation\delete_forum_discussion_post;
use local_moodlia\operation\delete_glossary_entry;
use local_moodlia\operation\delete_lesson_page;
use local_moodlia\operation\delete_wiki_page;
use local_moodlia\operation\move_book_chapter;
use local_moodlia\operation\set_forum_discussion_favourite;
use local_moodlia\operation\set_forum_discussion_lock;
use local_moodlia\operation\set_forum_discussion_pin;
use local_moodlia\operation\set_forum_discussion_subscription;
use local_moodlia\operation\submit_choice_response;
use local_moodlia\operation\update_glossary_entry;
use local_moodlia\operation\update_wiki_page;
use local_moodlia\operation\view_book;
use local_moodlia\operation\view_choice;
use local_moodlia\operation\view_forum;
use local_moodlia\operation\view_glossary;
use local_moodlia\operation\view_glossary_entry;
use local_moodlia\operation\view_lesson;
use local_moodlia\operation\view_wiki;
use local_moodlia\operation\view_wiki_page;

/**
 * Verifies view events and write operations of the interactive activity modules.
 *
 * @covers \local_moodlia\operation\view_book
 * @covers \local_moodlia\operation\move_book_chapter
 * @covers \local_moodlia\operation\delete_book_chapter
 * @covers \local_moodlia\operation\view_lesson
 * @covers \local_moodlia\operation\delete_lesson_page
 * @covers \local_moodlia\operation\view_wiki
 * @covers \local_moodlia\operation\view_wiki_page
 * @covers \local_moodlia\operation\update_wiki_page
 * @covers \local_moodlia\operation\delete_wiki_page
 * @covers \local_moodlia\operation\view_glossary
 * @covers \local_moodlia\operation\view_glossary_entry
 * @covers \local_moodlia\operation\update_glossary_entry
 * @covers \local_moodlia\operation\delete_glossary_entry
 * @covers \local_moodlia\operation\view_choice
 * @covers \local_moodlia\operation\submit_choice_response
 * @covers \local_moodlia\operation\delete_choice_responses
 * @covers \local_moodlia\operation\view_forum
 * @covers \local_moodlia\operation\set_forum_discussion_pin
 * @covers \local_moodlia\operation\set_forum_discussion_favourite
 * @covers \local_moodlia\operation\set_forum_discussion_subscription
 * @covers \local_moodlia\operation\set_forum_discussion_lock
 * @covers \local_moodlia\operation\delete_forum_discussion_post
 */
final class activity_interaction_operations_test extends advanced_testcase {
    /**
     * Book chapters are viewed, reordered, and deleted.
     */
    public function test_book_view_move_and_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $first = create_book_chapter::execute((int) $course->id, (int) $book->cmid, 'One', '<p>1</p>');
        $second = create_book_chapter::execute((int) $course->id, (int) $book->cmid, 'Two', '<p>2</p>');
        $third = create_book_chapter::execute((int) $course->id, (int) $book->cmid, 'Three', '<p>3</p>');

        $viewed = view_book::execute((int) $course->id, (int) $book->cmid, (int) $first['chapter_id']);
        $this->assertTrue($viewed['viewed']);

        move_book_chapter::execute((int) $course->id, (int) $book->cmid, (int) $third['chapter_id'], (int) $first['chapter_id']);
        $order = $DB->get_fieldset_select('book_chapters', 'id', 'bookid = ? ORDER BY pagenum', [$book->id]);
        $this->assertSame(
            [(int) $first['chapter_id'], (int) $third['chapter_id'], (int) $second['chapter_id']],
            array_map('intval', $order)
        );

        delete_book_chapter::execute((int) $course->id, (int) $book->cmid, (int) $second['chapter_id']);
        $this->assertFalse($DB->record_exists('book_chapters', ['id' => $second['chapter_id']]));
    }

    /**
     * Lessons are viewed and their pages deleted.
     */
    public function test_lesson_view_and_page_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $lesson = $this->getDataGenerator()->create_module('lesson', ['course' => $course->id]);
        $page = create_lesson_page::execute(
            (int) $course->id,
            (int) $lesson->cmid,
            'Intro',
            '<p>Welcome</p>',
            FORMAT_HTML,
            '{"branches":[{"title":"Next","jump_to":-1}]}'
        );

        $viewed = view_lesson::execute((int) $course->id, (int) $lesson->cmid);
        $this->assertTrue($viewed['viewed']);

        delete_lesson_page::execute((int) $course->id, (int) $lesson->cmid, (int) $page['page']['page_id']);
        $this->assertFalse($DB->record_exists('lesson_pages', ['id' => $page['page']['page_id']]));
    }

    /**
     * Wiki pages are viewed, updated, and deleted.
     */
    public function test_wiki_view_update_and_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $wiki = $this->getDataGenerator()->create_module('wiki', ['course' => $course->id, 'firstpagetitle' => 'Home']);
        $home = create_wiki_page::execute((int) $course->id, (int) $wiki->cmid, 'Home', '<p>Home</p>');
        $extra = create_wiki_page::execute((int) $course->id, (int) $wiki->cmid, 'Extra', '<p>Extra</p>');

        $this->assertSame((int) $wiki->id, view_wiki::execute((int) $course->id, (int) $wiki->cmid)['wiki_id']);
        $this->assertSame(
            (int) $home['page_id'],
            view_wiki_page::execute((int) $course->id, (int) $wiki->cmid, (int) $home['page_id'])['page_id']
        );

        update_wiki_page::execute((int) $course->id, (int) $wiki->cmid, (int) $home['page_id'], '<p>Updated home</p>');
        $this->assertStringContainsString('Updated home', $DB->get_field('wiki_pages', 'cachedcontent', ['id' => $home['page_id']]));

        delete_wiki_page::execute((int) $course->id, (int) $wiki->cmid, (int) $extra['page_id']);
        $this->assertFalse($DB->record_exists('wiki_pages', ['id' => $extra['page_id']]));
    }

    /**
     * Glossaries and entries are viewed, and entries updated and deleted.
     */
    public function test_glossary_view_update_and_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $glossary = $this->getDataGenerator()->create_module('glossary', ['course' => $course->id]);
        $entry = create_glossary_entry::execute((int) $course->id, (int) $glossary->cmid, 'Term', '<p>Meaning</p>');

        $this->assertTrue(view_glossary::execute((int) $course->id, (int) $glossary->cmid)['viewed']);
        $this->assertTrue(view_glossary_entry::execute((int) $course->id, (int) $glossary->cmid, (int) $entry['entry_id'])['viewed']);

        update_glossary_entry::execute((int) $course->id, (int) $glossary->cmid, (int) $entry['entry_id'], 'Renamed term', '*Meaning*', 'markdown');
        $stored = $DB->get_record('glossary_entries', ['id' => $entry['entry_id']], '*', MUST_EXIST);
        $this->assertSame('Renamed term', $stored->concept);
        $this->assertSame((int) FORMAT_MARKDOWN, (int) $stored->definitionformat);

        $this->assertTrue(delete_glossary_entry::execute((int) $course->id, (int) $glossary->cmid, (int) $entry['entry_id'])['deleted']);
        $this->assertFalse($DB->record_exists('glossary_entries', ['id' => $entry['entry_id']]));
    }

    /**
     * Students view a choice, answer it, and withdraw their answer.
     */
    public function test_choice_view_submit_and_delete_responses(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $choice = $this->getDataGenerator()->create_module('choice', [
            'course' => $course->id,
            'option' => ['Red', 'Blue'],
            'allowupdate' => 1,
        ]);
        $optionid = (int) $DB->get_field_select(
            'choice_options',
            'id',
            'choiceid = ? AND ' . $DB->sql_compare_text('text') . ' = ?',
            [$choice->id, 'Blue'],
            MUST_EXIST
        );
        $this->setUser($student);

        $this->assertTrue(view_choice::execute((int) $course->id, (int) $choice->cmid)['viewed']);
        submit_choice_response::execute((int) $course->id, (int) $choice->cmid, json_encode([$optionid]));
        $this->assertTrue($DB->record_exists('choice_answers', ['choiceid' => $choice->id, 'userid' => $student->id, 'optionid' => $optionid]));

        $this->assertTrue(delete_choice_responses::execute((int) $course->id, (int) $choice->cmid)['deleted']);
        $this->assertFalse($DB->record_exists('choice_answers', ['choiceid' => $choice->id, 'userid' => $student->id]));
    }

    /**
     * Forum discussions are pinned, favourited, subscribed, locked, and replies deleted.
     */
    public function test_forum_discussion_states_and_post_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $discussion = create_forum_discussion::execute((int) $course->id, (int) $forum->cmid, 'Topic', '<p>Body</p>');
        $discussionid = (int) $discussion['discussion_id'];
        $reply = create_forum_discussion_post::execute((int) $course->id, (int) $forum->cmid, $discussionid, null, 'Re: Topic', 'Reply');

        $this->assertTrue(view_forum::execute((int) $course->id, (int) $forum->cmid)['viewed']);

        $this->assertTrue(set_forum_discussion_pin::execute((int) $course->id, (int) $forum->cmid, $discussionid, true)['pinned']);
        $this->assertSame(FORUM_DISCUSSION_PINNED, (int) $DB->get_field('forum_discussions', 'pinned', ['id' => $discussionid]));

        $this->assertTrue(set_forum_discussion_favourite::execute((int) $course->id, (int) $forum->cmid, $discussionid, true)['favourite']);
        $this->assertFalse(set_forum_discussion_favourite::execute((int) $course->id, (int) $forum->cmid, $discussionid, false)['favourite']);

        $forumrecord = $DB->get_record('forum', ['id' => $forum->id], '*', MUST_EXIST);
        set_forum_discussion_subscription::execute((int) $course->id, (int) $forum->cmid, $discussionid, true);
        $this->assertTrue(\mod_forum\subscriptions::is_subscribed(get_admin()->id, $forumrecord, $discussionid));
        set_forum_discussion_subscription::execute((int) $course->id, (int) $forum->cmid, $discussionid, false);
        $this->assertFalse(\mod_forum\subscriptions::is_subscribed(get_admin()->id, $forumrecord, $discussionid));

        $locked = set_forum_discussion_lock::execute((int) $course->id, (int) $forum->cmid, $discussionid, true);
        $this->assertTrue($locked['locked']);
        $this->assertGreaterThan(0, (int) $DB->get_field('forum_discussions', 'timelocked', ['id' => $discussionid]));

        delete_forum_discussion_post::execute((int) $course->id, (int) $forum->cmid, $discussionid, (int) $reply['post_id']);
        $this->assertFalse($DB->record_exists('forum_posts', ['id' => $reply['post_id']]));
    }
}
