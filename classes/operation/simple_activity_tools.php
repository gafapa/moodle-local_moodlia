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
 * Shared simple activity detail helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns safe details for simple activities exposed through Moodle APIs.
 */
class simple_activity_tools {
    /**
     * Return page details exposed through course module metadata.
     *
     * @param \cm_info $cm Page course module.
     * @return array
     */
    public static function get_page_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $page = self::find_external_activity(
            'page',
            '\\mod_page_external',
            'get_pages_by_courses',
            'pages',
            (int) $course->id,
            $cm
        );
        $content = self::optional_string($page, 'content');
        if ($content === '') {
            $content = self::rendered_content($cm);
        }

        return [
            'page_id' => (int) $cm->instance,
            'content' => $content,
            'content_format' => self::optional_int($page, 'contentformat'),
            'content_length' => self::content_length($content),
            'display' => self::optional_int($page + $customdata, 'display'),
            'print_intro' => self::optional_bool($page + $customdata, 'printintro'),
            'print_last_modified' => self::optional_bool($page + $customdata, 'printlastmodified'),
            'revision' => self::optional_int($page + $customdata, 'revision'),
            'time_modified' => self::optional_int($page + $customdata, 'timemodified'),
        ];
    }

    /**
     * Return label details exposed through course module metadata.
     *
     * @param \cm_info $cm Label course module.
     * @return array
     */
    public static function get_label_details(\stdClass $course, \cm_info $cm): array {
        $label = self::find_external_activity(
            'label',
            '\\mod_label_external',
            'get_labels_by_courses',
            'labels',
            (int) $course->id,
            $cm
        );
        $content = self::optional_string($label, 'intro');
        if ($content === '') {
            $content = self::rendered_content($cm);
        }

        return [
            'label_id' => (int) $cm->instance,
            'content' => $content,
            'content_format' => self::optional_int($label, 'introformat'),
            'content_length' => self::content_length($content),
        ];
    }

    /**
     * Return question bank activity details through Moodle question APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Question bank course module.
     * @return array
     */
    public static function get_qbank_details(\stdClass $course, \cm_info $cm): array {
        $categories = question_tools::get_question_categories(
            (int) $course->id,
            question_tools::BANK_SCOPE_COURSE_SHARED,
            (int) $cm->id,
            null,
            true
        );
        $questioncount = array_sum(array_map(static function (array $category): int {
            return (int) ($category['question_count'] ?? 0);
        }, $categories));

        return [
            'qbank_id' => (int) $cm->instance,
            'question_bank_module_id' => (int) $cm->id,
            'context_id' => (int) \context_module::instance($cm->id)->id,
            'url' => (new \moodle_url('/question/edit.php', ['cmid' => $cm->id]))->out(false),
            'category_count' => count($categories),
            'question_count' => $questioncount,
            'categories' => array_map(static function (array $category): array {
                return [
                    'category_id' => (int) $category['category_id'],
                    'name' => (string) $category['name'],
                    'parent_id' => (int) $category['parent_id'],
                    'question_count' => (int) $category['question_count'],
                    'is_top' => (bool) $category['is_top'],
                ];
            }, $categories),
        ];
    }

    /**
     * Return URL details exposed through course module metadata.
     *
     * @param \cm_info $cm URL course module.
     * @return array
     */
    public static function get_url_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $url = self::find_external_activity(
            'url',
            '\\mod_url_external',
            'get_urls_by_courses',
            'urls',
            (int) $course->id,
            $cm
        );
        $displayoptions = self::decode_display_options(self::optional_string($url, 'displayoptions'));
        $metadata = $url + $displayoptions + $customdata;

        return [
            'url_id' => (int) $cm->instance,
            'external_url' => self::optional_string($metadata, 'externalurl'),
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'display' => self::optional_int($metadata, 'display'),
            'print_intro' => self::optional_bool($metadata, 'printintro'),
            'popup_width' => self::optional_int($metadata, 'popupwidth'),
            'popup_height' => self::optional_int($metadata, 'popupheight'),
            'revision' => self::optional_int($metadata, 'revision'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
        ];
    }

    /**
     * Return book settings and chapter summaries exposed through Moodle book APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Book course module.
     * @return array
     */
    public static function get_book_details(\stdClass $course, \cm_info $cm): array {
        $book = book_tools::get_book_instance((int) $course->id, $cm);
        $chapters = book_tools::get_chapters($book, $cm, false, false);

        return [
            'book_id' => (int) $book->id,
            'numbering' => (int) $book->numbering,
            'custom_titles' => (bool) $book->customtitles,
            'revision' => (int) $book->revision,
            'chapter_count' => count($chapters),
            'chapters' => array_map(static function (array $chapter): array {
                return [
                    'chapter_id' => (int) $chapter['chapter_id'],
                    'title' => (string) $chapter['title'],
                    'page_number' => (int) $chapter['page_number'],
                    'subchapter' => (bool) $chapter['subchapter'],
                    'hidden' => (bool) $chapter['hidden'],
                    'url' => (string) $chapter['url'],
                ];
            }, $chapters),
        ];
    }

    /**
     * Return folder details and stored file summaries through Moodle File API.
     *
     * @param \cm_info $cm Folder course module.
     * @return array
     */
    public static function get_folder_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $folder = self::find_external_activity(
            'folder',
            '\\mod_folder_external',
            'get_folders_by_courses',
            'folders',
            (int) $course->id,
            $cm
        );
        $metadata = $folder + $customdata;
        $files = module_file_tools::get_folder_files($cm);

        return [
            'folder_id' => (int) $cm->instance,
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'display' => self::optional_int($metadata, 'display'),
            'show_expanded' => self::optional_bool($metadata, 'showexpanded'),
            'show_download_folder' => self::optional_bool($metadata, 'showdownloadfolder'),
            'force_download' => self::optional_bool($metadata, 'forcedownload'),
            'revision' => self::optional_int($metadata, 'revision'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
            'file_count' => count($files),
            'total_size' => self::total_size($files),
            'files' => $files,
        ];
    }

    /**
     * Return resource details and stored file summaries through Moodle File API.
     *
     * @param \cm_info $cm Resource course module.
     * @return array
     */
    public static function get_resource_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $resource = self::find_external_activity(
            'resource',
            '\\mod_resource_external',
            'get_resources_by_courses',
            'resources',
            (int) $course->id,
            $cm
        );
        $displayoptions = self::decode_display_options(self::optional_string($resource, 'displayoptions'));
        $metadata = $resource + $displayoptions + $customdata;
        $files = module_file_tools::get_resource_files($cm);

        return [
            'resource_id' => (int) $cm->instance,
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'display' => self::optional_int($metadata, 'display'),
            'print_intro' => self::optional_bool($metadata, 'printintro'),
            'show_size' => self::optional_bool($metadata, 'showsize'),
            'show_type' => self::optional_bool($metadata, 'showtype'),
            'show_date' => self::optional_bool($metadata, 'showdate'),
            'filter_files' => self::optional_int($metadata, 'filterfiles'),
            'popup_width' => self::optional_int($metadata, 'popupwidth'),
            'popup_height' => self::optional_int($metadata, 'popupheight'),
            'revision' => self::optional_int($metadata, 'revision'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
            'file_count' => count($files),
            'total_size' => self::total_size($files),
            'primary_file' => $files[0] ?? null,
            'files' => $files,
        ];
    }

    /**
     * Return subsection details exposed through course format APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Subsection course module.
     * @return array
     */
    public static function get_subsection_details(\stdClass $course, \cm_info $cm): array {
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info_by_component('mod_subsection', (int) $cm->instance);

        if (!$section) {
            return [
                'subsection_id' => (int) $cm->instance,
                'delegated_section_id' => 0,
                'delegated_section_number' => 0,
                'delegated_section_name' => '',
                'delegated_section_visible' => false,
                'delegated_section_availability' => '',
            ];
        }

        return [
            'subsection_id' => (int) $cm->instance,
            'delegated_section_id' => (int) $section->id,
            'delegated_section_number' => (int) $section->section,
            'delegated_section_name' => get_section_name($course, $section),
            'delegated_section_visible' => (bool) $section->visible,
            'delegated_section_availability' => (string) ($section->availability ?? ''),
        ];
    }

    /**
     * Return feedback settings exposed through Moodle feedback APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Feedback course module.
     * @return array
     */
    public static function get_feedback_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $feedback = self::find_external_activity(
            'feedback',
            '\\mod_feedback\\external',
            'get_feedbacks_by_courses',
            'feedbacks',
            (int) $course->id,
            $cm
        );
        $metadata = $feedback + $customdata;

        return [
            'feedback_id' => (int) $cm->instance,
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'time_open' => self::optional_int($metadata, 'timeopen'),
            'time_close' => self::optional_int($metadata, 'timeclose'),
            'anonymous' => self::optional_int($metadata, 'anonymous'),
            'completion_submit' => self::completion_submit($metadata),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
        ];
    }

    /**
     * Return database activity settings exposed through Moodle data APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Database course module.
     * @return array
     */
    public static function get_data_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $formdata = self::module_form_data($course, $cm);
        $database = self::find_external_activity(
            'data',
            '\\mod_data_external',
            'get_databases_by_courses',
            'databases',
            (int) $course->id,
            $cm
        );
        $metadata = $database + $customdata;
        $fields = data_tools::get_fields($cm);
        $entries = data_tools::get_entries($cm, '', false, 0, 20);

        return [
            'data_id' => (int) $cm->instance,
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'comments' => self::optional_bool($metadata, 'comments'),
            'approval_required' => self::optional_bool($metadata, 'approval'),
            'manage_approved' => self::optional_bool($metadata, 'manageapproved'),
            'available_from' => self::optional_int($metadata, 'timeavailablefrom'),
            'available_to' => self::optional_int($metadata, 'timeavailableto'),
            'view_from' => self::optional_int($metadata, 'timeviewfrom'),
            'view_to' => self::optional_int($metadata, 'timeviewto'),
            'required_entries' => self::optional_int($metadata, 'requiredentries'),
            'required_entries_to_view' => self::optional_int($metadata, 'requiredentriestoview'),
            'max_entries' => self::optional_int($metadata, 'maxentries'),
            'rss_articles' => self::optional_int($metadata, 'rssarticles'),
            'default_sort_field_id' => self::optional_int($metadata, 'defaultsort'),
            'default_sort_direction' => self::optional_int($metadata, 'defaultsortdir'),
            'edit_any' => self::optional_bool($metadata, 'editany'),
            'notification' => self::optional_int($metadata, 'notification'),
            'completion_entries' => self::optional_int($formdata + $metadata, 'completionentries'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
            'field_count' => count($fields),
            'fields' => array_map(static function (array $field): array {
                return [
                    'field_id' => (int) $field['field_id'],
                    'type' => (string) $field['type'],
                    'name' => (string) $field['name'],
                    'required' => (bool) $field['required'],
                ];
            }, $fields),
            'entry_count' => (int) $entries['count'],
            'entries' => array_map(static function (array $entry): array {
                return [
                    'entry_id' => (int) $entry['entry_id'],
                    'user_id' => (int) $entry['user_id'],
                    'approved' => (bool) $entry['approved'],
                    'time_modified' => (int) $entry['time_modified'],
                ];
            }, $entries['entries']),
        ];
    }

    /**
     * Return lesson settings exposed through Moodle lesson APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Lesson course module.
     * @return array
     */
    public static function get_lesson_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $lesson = self::find_external_activity(
            'lesson',
            '\\mod_lesson_external',
            'get_lessons_by_courses',
            'lessons',
            (int) $course->id,
            $cm
        );
        $metadata = $lesson + $customdata;

        return [
            'lesson_id' => (int) $cm->instance,
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'practice' => self::optional_bool($metadata, 'practice'),
            'allow_review' => self::optional_bool($metadata, 'modattempts'),
            'ongoing_score' => self::optional_bool($metadata, 'ongoing'),
            'progress_bar' => self::optional_bool($metadata, 'progressbar'),
            'display_left_menu' => self::optional_bool($metadata, 'displayleft'),
            'display_left_if' => self::optional_int($metadata, 'displayleftif'),
            'slideshow' => self::optional_bool($metadata, 'slideshow'),
            'max_answers' => self::optional_int($metadata, 'maxanswers'),
            'default_feedback' => self::optional_bool($metadata, 'feedback'),
            'available_from' => self::optional_int($metadata, 'available'),
            'deadline' => self::optional_int($metadata, 'deadline'),
            'time_limit_seconds' => self::optional_int($metadata, 'timelimit'),
            'use_password' => self::optional_bool($metadata, 'usepassword'),
            'allow_question_retry' => self::optional_bool($metadata, 'review'),
            'max_attempts' => self::optional_int($metadata, 'maxattempts'),
            'after_correct_answer' => self::optional_int($metadata, 'nextpagedefault'),
            'pages_to_show' => self::optional_int($metadata, 'maxpages'),
            'grade' => self::optional_int($metadata, 'grade'),
            'custom_scoring' => self::optional_bool($metadata, 'custom'),
            'retakes_allowed' => self::optional_bool($metadata, 'retake'),
            'use_max_grade' => self::optional_bool($metadata, 'usemaxgrade'),
            'minimum_questions' => self::optional_int($metadata, 'minquestions'),
            'activity_link' => self::optional_int($metadata, 'activitylink'),
            'completion_end_reached' => self::optional_bool($metadata, 'completionendreached'),
            'completion_time_spent_seconds' => self::optional_int($metadata, 'completiontimespent'),
            'allow_offline_attempts' => self::optional_bool($metadata, 'allowofflineattempts'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
        ];
    }

    /**
     * Return external tool settings exposed through Moodle LTI APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm LTI course module.
     * @return array
     */
    public static function get_lti_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $lti = self::find_external_activity(
            'lti',
            '\\mod_lti_external',
            'get_ltis_by_courses',
            'ltis',
            (int) $course->id,
            $cm
        );
        $metadata = $lti + $customdata;

        return [
            'lti_id' => (int) $cm->instance,
            'type_id' => self::optional_int($metadata, 'typeid'),
            'tool_url' => self::optional_string($metadata, 'toolurl'),
            'secure_tool_url' => self::optional_string($metadata, 'securetoolurl'),
            'launch_container' => self::optional_int($metadata, 'launchcontainer'),
            'send_name' => self::optional_bool($metadata, 'instructorchoicesendname'),
            'send_email' => self::optional_bool($metadata, 'instructorchoicesendemailaddr'),
            'allow_roster' => self::optional_bool($metadata, 'instructorchoiceallowroster'),
            'allow_setting' => self::optional_bool($metadata, 'instructorchoiceallowsetting'),
            'custom_parameters' => self::optional_string($metadata, 'instructorcustomparameters'),
            'accept_grades' => self::optional_bool($metadata, 'instructorchoiceacceptgrades'),
            'grade' => self::optional_float($metadata, 'grade'),
            'debug_launch' => self::optional_bool($metadata, 'debuglaunch'),
            'show_title_launch' => self::optional_bool($metadata, 'showtitlelaunch'),
            'show_description_launch' => self::optional_bool($metadata, 'showdescriptionlaunch'),
            'icon' => self::optional_string($metadata, 'icon'),
            'secure_icon' => self::optional_string($metadata, 'secureicon'),
            'time_created' => self::optional_int($metadata, 'timecreated'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
        ];
    }

    /**
     * Return workshop settings exposed through Moodle workshop APIs.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Workshop course module.
     * @return array
     */
    public static function get_workshop_details(\stdClass $course, \cm_info $cm): array {
        $customdata = self::custom_data($cm);
        $workshop = self::find_external_activity(
            'workshop',
            '\\mod_workshop_external',
            'get_workshops_by_courses',
            'workshops',
            (int) $course->id,
            $cm
        );
        $metadata = $workshop + $customdata;

        return [
            'workshop_id' => (int) $cm->instance,
            'intro' => self::optional_string($metadata, 'intro'),
            'intro_format' => self::optional_int($metadata, 'introformat'),
            'submission_instructions' => self::optional_string($metadata, 'instructauthors'),
            'submission_instructions_format' => self::optional_int($metadata, 'instructauthorsformat'),
            'assessment_instructions' => self::optional_string($metadata, 'instructreviewers'),
            'assessment_instructions_format' => self::optional_int($metadata, 'instructreviewersformat'),
            'phase' => self::optional_int($metadata, 'phase'),
            'example_submissions' => self::optional_bool($metadata, 'useexamples'),
            'peer_assessment' => self::optional_bool($metadata, 'usepeerassessment'),
            'self_assessment' => self::optional_bool($metadata, 'useselfassessment'),
            'submission_grade' => self::optional_float($metadata, 'grade'),
            'assessment_grade' => self::optional_float($metadata, 'gradinggrade'),
            'strategy' => self::optional_string($metadata, 'strategy'),
            'evaluation' => self::optional_string($metadata, 'evaluation'),
            'grade_decimals' => self::optional_int($metadata, 'gradedecimals'),
            'text_submission' => self::optional_int($metadata, 'submissiontypetext'),
            'file_submission' => self::optional_int($metadata, 'submissiontypefile'),
            'max_submission_attachments' => self::optional_int($metadata, 'nattachments'),
            'submission_file_types' => self::optional_string($metadata, 'submissionfiletypes'),
            'late_submissions' => self::optional_bool($metadata, 'latesubmissions'),
            'max_file_size' => self::optional_int($metadata, 'maxbytes'),
            'examples_mode' => self::optional_int($metadata, 'examplesmode'),
            'submission_start' => self::optional_int($metadata, 'submissionstart'),
            'submission_end' => self::optional_int($metadata, 'submissionend'),
            'assessment_start' => self::optional_int($metadata, 'assessmentstart'),
            'assessment_end' => self::optional_int($metadata, 'assessmentend'),
            'switch_to_assessment_after_submission_deadline' => self::optional_bool($metadata, 'phaseswitchassessment'),
            'conclusion' => self::optional_string($metadata, 'conclusion'),
            'conclusion_format' => self::optional_int($metadata, 'conclusionformat'),
            'overall_feedback_mode' => self::optional_int($metadata, 'overallfeedbackmode'),
            'overall_feedback_files' => self::optional_int($metadata, 'overallfeedbackfiles'),
            'overall_feedback_file_types' => self::optional_string($metadata, 'overallfeedbackfiletypes'),
            'overall_feedback_max_file_size' => self::optional_int($metadata, 'overallfeedbackmaxbytes'),
            'time_modified' => self::optional_int($metadata, 'timemodified'),
        ];
    }

    /**
     * Return cm_info custom data as an array.
     *
     * @param \cm_info $cm Course module info.
     * @return array
     */
    private static function custom_data(\cm_info $cm): array {
        $customdata = $cm->customdata ?? [];
        if (is_object($customdata)) {
            $customdata = (array) $customdata;
        }

        return is_array($customdata) ? $customdata : [];
    }

    /**
     * Return module form data reconstructed by Moodle's course module API.
     *
     * @param \stdClass $course Moodle course.
     * @param \cm_info $cm Course module info.
     * @return array
     */
    private static function module_form_data(\stdClass $course, \cm_info $cm): array {
        module_tools::require_module_api();

        try {
            [, , , $moduleinfo] = get_moduleinfo_data($cm, $course);
        } catch (\Throwable $exception) {
            return [];
        }

        return is_object($moduleinfo) ? (array) $moduleinfo : [];
    }

    /**
     * Return activity metadata exposed by a module external API.
     *
     * @param string $component Module component without mod_ prefix.
     * @param string $classname External API class name.
     * @param string $method External API method name.
     * @param string $listkey Result list key.
     * @param int $courseid Moodle course id.
     * @param \cm_info $cm Course module info.
     * @return array
     */
    private static function find_external_activity(
        string $component,
        string $classname,
        string $method,
        string $listkey,
        int $courseid,
        \cm_info $cm
    ): array {
        self::load_external_api($component);
        if (!class_exists($classname) || !method_exists($classname, $method)) {
            return [];
        }

        $result = $classname::$method([$courseid]);
        foreach (($result[$listkey] ?? []) as $activity) {
            $activity = (array) $activity;
            if (
                (int) ($activity['coursemodule'] ?? 0) === (int) $cm->id ||
                (int) ($activity['id'] ?? 0) === (int) $cm->instance
            ) {
                return $activity;
            }
        }

        return [];
    }

    /**
     * Load a standard activity external API class if the module provides one.
     *
     * @param string $component Module component without mod_ prefix.
     */
    private static function load_external_api(string $component): void {
        global $CFG;

        $path = $CFG->dirroot . '/mod/' . $component . '/classes/external.php';
        if (file_exists($path)) {
            require_once($path);
        }
    }

    /**
     * Decode Moodle display option metadata when a module returns it as JSON.
     *
     * @param string $displayoptions Encoded display options.
     * @return array
     */
    private static function decode_display_options(string $displayoptions): array {
        if (trim($displayoptions) === '') {
            return [];
        }

        $decoded = json_decode($displayoptions, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Return rendered content exposed through cm_info or scalar custom data.
     *
     * @param \cm_info $cm Course module info.
     * @return string
     */
    private static function rendered_content(\cm_info $cm): string {
        $content = (string) ($cm->content ?? '');
        if ($content !== '') {
            return $content;
        }

        $customdata = self::custom_data($cm);
        foreach (['content', 'intro'] as $key) {
            if (!empty($customdata[$key]) && is_scalar($customdata[$key])) {
                return (string) $customdata[$key];
            }
        }

        return '';
    }

    /**
     * Return an optional string custom-data value.
     *
     * @param array $customdata Custom data.
     * @param string $key Custom data key.
     * @return string
     */
    private static function optional_string(array $customdata, string $key): string {
        return isset($customdata[$key]) && is_scalar($customdata[$key]) ? (string) $customdata[$key] : '';
    }

    /**
     * Return an optional integer custom-data value.
     *
     * @param array $customdata Custom data.
     * @param string $key Custom data key.
     * @return int
     */
    private static function optional_int(array $customdata, string $key): int {
        return isset($customdata[$key]) && is_scalar($customdata[$key]) ? (int) $customdata[$key] : 0;
    }

    /**
     * Return an optional float custom-data value.
     *
     * @param array $customdata Custom data.
     * @param string $key Custom data key.
     * @return float
     */
    private static function optional_float(array $customdata, string $key): float {
        return isset($customdata[$key]) && is_scalar($customdata[$key]) ? (float) $customdata[$key] : 0.0;
    }

    /**
     * Return an optional boolean custom-data value.
     *
     * @param array $customdata Custom data.
     * @param string $key Custom data key.
     * @return bool
     */
    private static function optional_bool(array $customdata, string $key): bool {
        return isset($customdata[$key]) && is_scalar($customdata[$key]) ? (bool) $customdata[$key] : false;
    }

    /**
     * Return feedback completion-submit setting from either summary or cm custom data.
     *
     * @param array $metadata Feedback metadata.
     * @return bool
     */
    private static function completion_submit(array $metadata): bool {
        if (isset($metadata['completionsubmit']) && is_scalar($metadata['completionsubmit'])) {
            return (bool) $metadata['completionsubmit'];
        }

        $rules = $metadata['customcompletionrules'] ?? [];
        if (is_object($rules)) {
            $rules = (array) $rules;
        }

        return is_array($rules) && isset($rules['completionsubmit']) ? (bool) $rules['completionsubmit'] : false;
    }

    /**
     * Return byte length for rendered content.
     *
     * @param string $content Rendered content.
     * @return int
     */
    private static function content_length(string $content): int {
        return strlen($content);
    }

    /**
     * Return total file size for file response rows.
     *
     * @param array $files File response rows.
     * @return int
     */
    private static function total_size(array $files): int {
        return array_sum(array_map(static function (array $file): int {
            return (int) ($file['filesize'] ?? 0);
        }, $files));
    }
}
