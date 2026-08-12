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
 * Shared forum helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle forum operations.
 */
class forum_tools {
    /**
     * Load Moodle forum APIs.
     */
    public static function require_forum_api(): void {
        global $CFG;

        require_once($CFG->dirroot . '/mod/forum/lib.php');
        require_once($CFG->dirroot . '/mod/forum/externallib.php');
    }

    /**
     * Verify that a course module belongs to a forum activity.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Cmid.
     * @return \cm_info
     */
    public static function get_forum_module(\stdClass $course, int $cmid): \cm_info {
        $cm = module_tools::get_course_module($course, $cmid);
        if ($cm->modname !== 'forum') {
            throw new \invalid_parameter_exception('module_id must reference a forum activity.');
        }

        return $cm;
    }

    /**
     * Return discussions through Moodle's forum external API.
     *
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_raw_discussions(\cm_info $cm): array {
        self::require_forum_api();

        $result = \mod_forum_external::get_forum_discussions((int) $cm->instance);
        return $result['discussions'] ?? [];
    }

    /**
     * Return a Moodle external warning list in the canonical response shape.
     *
     * @param array $warnings Warnings.
     * @return array
     */
    public static function warnings_to_response(array $warnings): array {
        $items = [];
        foreach ($warnings as $warning) {
            $warning = (array) $warning;
            $items[] = [
                'item' => (string) ($warning['item'] ?? ''),
                'item_id' => (int) ($warning['itemid'] ?? $warning['item_id'] ?? 0),
                'warning_code' => (string) ($warning['warningcode'] ?? $warning['warning_code'] ?? ''),
                'message' => (string) ($warning['message'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Return a canonical forum summary from Moodle's forum external API payload.
     *
     * @param mixed $forum Forum.
     * @return array
     */
    public static function forum_summary_to_response($forum): array {
        $forum = (array) $forum;
        $moduleid = (int) ($forum['cmid'] ?? $forum['coursemodule'] ?? $forum['coursemoduleid'] ?? 0);
        $url = $moduleid > 0 ? (new \moodle_url('/mod/forum/view.php', ['id' => $moduleid]))->out(false) : '';

        return [
            'forum_id' => (int) ($forum['id'] ?? 0),
            'module_id' => $moduleid,
            'course_id' => (int) ($forum['course'] ?? 0),
            'forum_type' => (string) ($forum['type'] ?? ''),
            'name' => (string) ($forum['name'] ?? ''),
            'intro' => (string) ($forum['intro'] ?? ''),
            'intro_format' => (int) ($forum['introformat'] ?? 0),
            'due_date' => (int) ($forum['duedate'] ?? 0),
            'cutoff_date' => (int) ($forum['cutoffdate'] ?? 0),
            'assessed' => (int) ($forum['assessed'] ?? 0),
            'scale' => (int) ($forum['scale'] ?? 0),
            'grade_forum' => (int) ($forum['grade_forum'] ?? 0),
            'grade_forum_notify' => (int) ($forum['grade_forum_notify'] ?? 0),
            'max_bytes' => (int) ($forum['maxbytes'] ?? 0),
            'max_attachments' => (int) ($forum['maxattachments'] ?? 0),
            'force_subscribe' => (int) ($forum['forcesubscribe'] ?? 0),
            'tracking_type' => (int) ($forum['trackingtype'] ?? 0),
            'rss_type' => (int) ($forum['rsstype'] ?? 0),
            'rss_articles' => (int) ($forum['rssarticles'] ?? 0),
            'time_modified' => (int) ($forum['timemodified'] ?? 0),
            'warn_after' => (int) ($forum['warnafter'] ?? 0),
            'block_after' => (int) ($forum['blockafter'] ?? 0),
            'block_period' => (int) ($forum['blockperiod'] ?? 0),
            'completion_discussions' => (int) ($forum['completiondiscussions'] ?? 0),
            'completion_replies' => (int) ($forum['completionreplies'] ?? 0),
            'completion_posts' => (int) ($forum['completionposts'] ?? 0),
            'discussion_count' => (int) ($forum['numdiscussions'] ?? 0),
            'can_create_discussions' => (bool) ($forum['cancreatediscussions'] ?? false),
            'lock_discussion_after' => (int) ($forum['lockdiscussionafter'] ?? 0),
            'tracked' => (bool) ($forum['istracked'] ?? false),
            'unread_posts' => (int) ($forum['unreadpostscount'] ?? 0),
            'show_immediately' => (bool) ($forum['showimmediately'] ?? false),
            'url' => $url,
        ];
    }

    /**
     * Return course forums in the canonical response shape.
     *
     * @param \stdClass $course Course.
     * @param array $forums Forums.
     * @return array
     */
    public static function course_forums_to_response(\stdClass $course, array $forums): array {
        $items = [];
        foreach ($forums as $forum) {
            $summary = self::forum_summary_to_response($forum);
            if ((int) $summary['course_id'] === (int) $course->id) {
                $items[] = $summary;
            }
        }

        return [
            'course_id' => (int) $course->id,
            'count' => count($items),
            'forums' => $items,
            'warnings' => [],
        ];
    }

    /**
     * Find a discussion in a forum.
     *
     * @param \cm_info $cm Cm.
     * @param int $discussionid Discussionid.
     * @return array
     */
    public static function get_raw_discussion(\cm_info $cm, int $discussionid): array {
        foreach (self::get_raw_discussions($cm) as $discussion) {
            $currentid = (int) ($discussion['discussion'] ?? $discussion['discussionid'] ?? 0);
            if ($currentid === $discussionid) {
                return $discussion;
            }
        }

        throw new \moodle_exception('invaliddiscussionid', 'forum');
    }

    /**
     * Return a canonical discussion response.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param array $discussion Discussion.
     * @return array
     */
    public static function discussion_to_response(\stdClass $course, \cm_info $cm, array $discussion): array {
        $discussionid = (int) ($discussion['discussion'] ?? $discussion['discussionid'] ?? 0);
        $firstpostid = (int) ($discussion['id'] ?? $discussion['firstpost'] ?? 0);
        $url = new \moodle_url('/mod/forum/discuss.php', ['d' => $discussionid]);

        return [
            'discussion_id' => $discussionid,
            'forum_id' => (int) $cm->instance,
            'course_id' => (int) $course->id,
            'module_id' => (int) $cm->id,
            'first_post_id' => $firstpostid,
            'name' => (string) ($discussion['name'] ?? $discussion['subject'] ?? ''),
            'message' => (string) ($discussion['message'] ?? ''),
            'user_id' => (int) ($discussion['userid'] ?? 0),
            'reply_count' => (int) ($discussion['numreplies'] ?? 0),
            'created' => (int) ($discussion['created'] ?? 0),
            'modified' => (int) ($discussion['timemodified'] ?? $discussion['modified'] ?? 0),
            'can_reply' => (bool) ($discussion['canreply'] ?? false),
            'url' => $url->out(false),
        ];
    }

    /**
     * Return discussion posts through Moodle's forum external API.
     *
     * @param int $discussionid Discussionid.
     * @return array
     */
    public static function get_raw_posts(int $discussionid): array {
        self::require_forum_api();

        $result = \mod_forum_external::get_discussion_posts($discussionid, 'created', 'ASC');
        return $result['posts'] ?? [];
    }

    /**
     * Find a post in a discussion.
     *
     * @param int $discussionid Discussionid.
     * @param int $postid Postid.
     * @return array
     */
    public static function get_raw_post(int $discussionid, int $postid): array {
        foreach (self::get_raw_posts($discussionid) as $post) {
            $post = (array) $post;
            if ((int) ($post['id'] ?? 0) === $postid) {
                return $post;
            }
        }

        throw new \moodle_exception('invalidpostid', 'forum');
    }

    /**
     * Return a canonical post response.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @param int $discussionid Discussionid.
     * @param mixed $post Post.
     * @return array
     */
    public static function post_to_response(\stdClass $course, \cm_info $cm, int $discussionid, $post): array {
        $post = (array) $post;
        $author = (array) ($post['author'] ?? []);
        $postid = (int) ($post['id'] ?? $post['postid'] ?? 0);
        $url = new \moodle_url('/mod/forum/discuss.php', ['d' => $discussionid], 'p' . $postid);

        return [
            'post_id' => $postid,
            'discussion_id' => (int) ($post['discussionid'] ?? $post['discussion'] ?? $discussionid),
            'parent_post_id' => (int) ($post['parentid'] ?? $post['parent'] ?? 0),
            'subject' => (string) ($post['subject'] ?? ''),
            'message' => (string) ($post['message'] ?? ''),
            'user_id' => (int) ($author['id'] ?? $post['userid'] ?? 0),
            'created' => (int) ($post['timecreated'] ?? $post['created'] ?? 0),
            'modified' => (int) ($post['timemodified'] ?? $post['modified'] ?? 0),
            'url' => $url->out(false),
        ];
    }

    /**
     * Return forum settings and discussion totals exposed through Moodle forum APIs.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @return array
     */
    public static function get_forum_details(\stdClass $course, \cm_info $cm): array {
        self::require_forum_api();

        self::get_forum_module($course, (int) $cm->id);

        $forum = self::find_forum_instance($course, $cm);
        $rawdiscussions = self::get_raw_discussions($cm);
        $discussions = [];
        $totalposts = 0;

        foreach ($rawdiscussions as $discussion) {
            $response = self::discussion_to_response($course, $cm, (array) $discussion);
            $totalposts += 1 + (int) $response['reply_count'];
            $discussions[] = [
                'discussion_id' => (int) $response['discussion_id'],
                'first_post_id' => (int) $response['first_post_id'],
                'name' => (string) $response['name'],
                'reply_count' => (int) $response['reply_count'],
                'created' => (int) $response['created'],
                'modified' => (int) $response['modified'],
                'can_reply' => (bool) $response['can_reply'],
                'url' => (string) $response['url'],
            ];
        }

        $forumtype = (string) ($forum['type'] ?? $forum['forumtype'] ?? self::custom_data_value($cm, 'type', ''));

        return [
            'forum_id' => (int) $cm->instance,
            'forum_type' => $forumtype,
            'maxbytes' => (int) ($forum['maxbytes'] ?? 0),
            'maxattachments' => (int) ($forum['maxattachments'] ?? 0),
            'forcesubscribe' => (int) ($forum['forcesubscribe'] ?? 0),
            'trackingtype' => (int) ($forum['trackingtype'] ?? 0),
            'assessed' => (int) ($forum['assessed'] ?? 0),
            'scale' => (int) ($forum['scale'] ?? 0),
            'grade_forum' => (int) ($forum['grade_forum'] ?? 0),
            'grade_forum_notify' => (int) ($forum['grade_forum_notify'] ?? 0),
            'warnafter' => (int) ($forum['warnafter'] ?? 0),
            'blockafter' => (int) ($forum['blockafter'] ?? 0),
            'blockperiod' => (int) ($forum['blockperiod'] ?? 0),
            'completiondiscussions' => (int) ($forum['completiondiscussions'] ?? 0),
            'completionreplies' => (int) ($forum['completionreplies'] ?? 0),
            'completionposts' => (int) ($forum['completionposts'] ?? 0),
            'displaywordcount' => (int) ($forum['displaywordcount'] ?? 0),
            'lockdiscussionafter' => (int) ($forum['lockdiscussionafter'] ?? 0),
            'duedate' => (int) ($forum['duedate'] ?? 0),
            'cutoffdate' => (int) ($forum['cutoffdate'] ?? 0),
            'can_creatediscussions' => (bool) ($forum['cancreatediscussions'] ?? false),
            'tracked' => (bool) ($forum['istracked'] ?? false),
            'unread_posts' => (int) ($forum['unreadpostscount'] ?? 0),
            'discussion_count' => count($discussions),
            'total_post_count' => $totalposts,
            'discussions' => $discussions,
        ];
    }

    /**
     * Return a forum instance payload from Moodle external APIs where available.
     *
     * @param \stdClass $course Course.
     * @param \cm_info $cm Cm.
     * @return array
     */
    private static function find_forum_instance(\stdClass $course, \cm_info $cm): array {
        if (!method_exists(\mod_forum_external::class, 'get_forums_by_courses')) {
            return [];
        }

        $result = \mod_forum_external::get_forums_by_courses([(int) $course->id]);
        $forums = (array) ($result['forums'] ?? $result);

        foreach ($forums as $forum) {
            $forum = (array) $forum;
            if (
                (int) ($forum['id'] ?? 0) === (int) $cm->instance ||
                (int) ($forum['cmid'] ?? $forum['coursemodule'] ?? $forum['coursemoduleid'] ?? 0) === (int) $cm->id
            ) {
                return $forum;
            }
        }

        return [];
    }

    /**
     * Return a scalar value from cm_info custom data when Moodle exposes it there.
     *
     * @param \cm_info $cm Cm.
     * @param string $key Key.
     * @param mixed $default Default.
     * @return mixed
     */
    private static function custom_data_value(\cm_info $cm, string $key, $default) {
        $customdata = $cm->customdata ?? [];
        if (is_object($customdata)) {
            $customdata = (array) $customdata;
        }
        if (!is_array($customdata) || !array_key_exists($key, $customdata)) {
            return $default;
        }

        $value = $customdata[$key];
        return is_scalar($value) || $value === null ? $value : $default;
    }
}
