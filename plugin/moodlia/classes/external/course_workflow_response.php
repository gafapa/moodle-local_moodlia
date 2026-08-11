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
 * MoodlIA plugin implementation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_single_structure;
use core_external\external_value;

/**
 * Shared external return structures for course workflow operations.
 */
class course_workflow_response {
    public static function created_course_structure(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'publish_state' => new external_value(PARAM_ALPHA, 'Applied publishing state'),
            'course_json' => new external_value(PARAM_RAW, 'JSON-encoded created course'),
            'sections_json' => new external_value(PARAM_RAW, 'JSON array with created sections'),
            'modules_json' => new external_value(PARAM_RAW, 'JSON array with created modules'),
            'groups_json' => new external_value(PARAM_RAW, 'JSON array with created groups'),
            'enrolments_json' => new external_value(PARAM_RAW, 'JSON array with enrolment results'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with non-fatal workflow warnings'),
        ]);
    }

    public static function applied_blueprint_structure(): external_single_structure {
        return new external_single_structure([
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'sections_json' => new external_value(PARAM_RAW, 'JSON array with created sections'),
            'modules_json' => new external_value(PARAM_RAW, 'JSON array with created modules'),
            'groups_json' => new external_value(PARAM_RAW, 'JSON array with created groups'),
            'enrolments_json' => new external_value(PARAM_RAW, 'JSON array with enrolment results'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with non-fatal workflow warnings'),
        ]);
    }

    public static function copied_structure(): external_single_structure {
        return new external_single_structure([
            'source_course_id' => new external_value(PARAM_INT, 'Source Moodle course id'),
            'target_course_id' => new external_value(PARAM_INT, 'Target Moodle course id'),
            'sections_json' => new external_value(PARAM_RAW, 'JSON array with created sections'),
            'modules_json' => new external_value(PARAM_RAW, 'JSON array with created modules'),
            'groups_json' => new external_value(PARAM_RAW, 'JSON array with created groups'),
            'warnings_json' => new external_value(PARAM_RAW, 'JSON array with non-fatal workflow warnings'),
        ]);
    }
}
