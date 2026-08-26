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
 * Gradebook response structures.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\external;

use core_external\external_single_structure;
use core_external\external_value;

/**
 * Shared Gradebook external response structures.
 */
class gradebook_response {
    /**
     * Grade category response structure.
     *
     * @return external_single_structure
     */
    public static function category_structure(): external_single_structure {
        return new external_single_structure([
            'category_id' => new external_value(PARAM_INT, 'Grade category id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'name' => new external_value(PARAM_RAW, 'Grade category name'),
            'aggregation' => new external_value(PARAM_INT, 'Moodle aggregation constant'),
            'exclude_empty_grades' => new external_value(PARAM_BOOL, 'Whether ungraded items are excluded from aggregation'),
            'keep_highest' => new external_value(PARAM_INT, 'Number of highest grades kept for aggregation'),
            'drop_lowest' => new external_value(PARAM_INT, 'Number of lowest grades dropped for aggregation'),
            'total_item_id' => new external_value(PARAM_INT, 'Category total grade item id'),
            'grade_min' => new external_value(PARAM_FLOAT, 'Minimum category total grade'),
            'grade_max' => new external_value(PARAM_FLOAT, 'Maximum category total grade'),
            'grade_pass' => new external_value(PARAM_FLOAT, 'Passing category total grade'),
            'hidden' => new external_value(PARAM_BOOL, 'Whether the category is hidden'),
            'locked' => new external_value(PARAM_BOOL, 'Whether the category total is locked'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification timestamp'),
        ]);
    }

    /**
     * Grade item response structure.
     *
     * @return external_single_structure
     */
    public static function manual_item_structure(): external_single_structure {
        return new external_single_structure([
            'item_id' => new external_value(PARAM_INT, 'Grade item id'),
            'course_id' => new external_value(PARAM_INT, 'Moodle course id'),
            'category_id' => new external_value(PARAM_INT, 'Grade category id'),
            'name' => new external_value(PARAM_RAW, 'Grade item name'),
            'item_type' => new external_value(PARAM_ALPHA, 'Grade item type'),
            'item_module' => new external_value(PARAM_PLUGIN, 'Owning Moodle module type'),
            'item_instance' => new external_value(PARAM_INT, 'Owning Moodle module instance id'),
            'item_number' => new external_value(PARAM_INT, 'Owning Moodle grade item number'),
            'course_module_id' => new external_value(PARAM_INT, 'Owning Moodle course module id'),
            'grade_min' => new external_value(PARAM_FLOAT, 'Minimum grade'),
            'grade_max' => new external_value(PARAM_FLOAT, 'Maximum grade'),
            'grade_pass' => new external_value(PARAM_FLOAT, 'Passing grade'),
            'weight' => new external_value(PARAM_FLOAT, 'Moodle aggregation weight for the parent total'),
            'weight_overridden' => new external_value(PARAM_BOOL, 'Whether the item weight is manually overridden'),
            'hidden' => new external_value(PARAM_BOOL, 'Whether the item is hidden'),
            'locked' => new external_value(PARAM_BOOL, 'Whether the item is locked'),
            'contributes_to_course_total' => new external_value(PARAM_BOOL, 'Whether the item can contribute to the course total'),
            'time_modified' => new external_value(PARAM_INT, 'Last modification timestamp'),
        ]);
    }
}
