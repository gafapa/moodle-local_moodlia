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
 * Get course glossaries external function.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_moodlia\operation\get_course_glossaries as get_course_glossaries_operation;

/**
 * External API adapter for get_course_glossaries.
 */
class get_course_glossaries extends external_api {
    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param int $courseid Moodle course id.
     * @return array
     */
    public static function execute(int $courseid): array {
        ['course_id' => $courseid] = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $courseid,
        ]);

        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/moodlia:useapi', $systemcontext);

        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('moodle/course:view', $coursecontext);

        return get_course_glossaries_operation::execute((int) $courseid);
    }

    /**
     * Define output structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'count' => new external_value(PARAM_INT, 'Returned Glossary count'),
            'glossaries' => new external_multiple_structure(self::glossary_summary_structure()),
            'warnings' => self::warnings_structure(),
        ]);
    }

    /**
     * Shared Glossary summary structure.
     *
     * @return external_single_structure
     */
    public static function glossary_summary_structure(): external_single_structure {
        return new external_single_structure([
            'glossary_id' => new external_value(PARAM_INT, 'Glossary instance id'),
            'module_id' => new external_value(PARAM_INT, 'Glossary course module id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_RAW, 'Glossary name'),
            'intro' => new external_value(PARAM_RAW, 'Glossary introduction'),
            'intro_format' => new external_value(PARAM_INT, 'Moodle intro format'),
            'allow_duplicated_entries' => new external_value(PARAM_BOOL, 'Whether duplicate concepts are allowed'),
            'display_format' => new external_value(PARAM_TEXT, 'Display format'),
            'main_glossary' => new external_value(PARAM_BOOL, 'Whether this is the main glossary'),
            'show_special' => new external_value(PARAM_BOOL, 'Whether special-character browsing is enabled'),
            'show_alphabet' => new external_value(PARAM_BOOL, 'Whether alphabet browsing is enabled'),
            'show_all' => new external_value(PARAM_BOOL, 'Whether all-entry browsing is enabled'),
            'allow_comments' => new external_value(PARAM_BOOL, 'Whether comments are allowed'),
            'allow_print_view' => new external_value(PARAM_BOOL, 'Whether print view is allowed'),
            'use_dynamic_linking' => new external_value(PARAM_BOOL, 'Whether dynamic linking is enabled'),
            'default_approval' => new external_value(PARAM_BOOL, 'Whether entries are approved by default'),
            'approval_display_format' => new external_value(PARAM_TEXT, 'Approval display format'),
            'global_glossary' => new external_value(PARAM_BOOL, 'Whether this is a global glossary'),
            'entries_per_page' => new external_value(PARAM_INT, 'Entries per page'),
            'edit_always' => new external_value(PARAM_BOOL, 'Whether entries can always be edited'),
            'rss_type' => new external_value(PARAM_INT, 'RSS type'),
            'rss_articles' => new external_value(PARAM_INT, 'RSS articles'),
            'assessed' => new external_value(PARAM_INT, 'Rating aggregate type'),
            'scale' => new external_value(PARAM_INT, 'Scale id'),
            'time_created' => new external_value(PARAM_INT, 'Creation timestamp'),
            'time_modified' => new external_value(PARAM_INT, 'Modification timestamp'),
            'completion_entries' => new external_value(PARAM_INT, 'Completion entry count'),
            'browse_modes' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Browse mode')),
            'can_add_entry' => new external_value(PARAM_BOOL, 'Whether the current user can add entries'),
        ]);
    }

    /**
     * Shared warning structure.
     *
     * @return external_multiple_structure
     */
    public static function warnings_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'item' => new external_value(PARAM_TEXT, 'Warning item'),
            'item_id' => new external_value(PARAM_INT, 'Warning item id'),
            'warning_code' => new external_value(PARAM_TEXT, 'Warning code'),
            'message' => new external_value(PARAM_TEXT, 'Warning message'),
        ]));
    }
}
