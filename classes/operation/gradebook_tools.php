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
 * Shared gradebook helpers.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Helper methods for Moodle gradebook operations.
 */
class gradebook_tools {
    /**
     * Load Moodle gradebook APIs.
     */
    public static function require_gradebook_api(): void {
        global $CFG;

        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/grade/grade_category.php');
        require_once($CFG->libdir . '/grade/grade_item.php');
    }

    /**
     * Convert Moodle grade item data to the canonical response shape.
     *
     * @param mixed $item Item.
     * @return array
     */
    public static function grade_item_to_response($item): array {
        $item = self::to_array($item);

        return [
            'item_id' => (int) ($item['id'] ?? 0),
            'name' => (string) ($item['itemname'] ?? ''),
            'category' => (string) ($item['category'] ?? ''),
        ];
    }

    /**
     * Convert a grade category object to the canonical response shape.
     *
     * @param \grade_category $category Category.
     * @return array
     */
    public static function grade_category_to_response(\grade_category $category): array {
        return [
            'category_id' => (int) $category->id,
            'course_id' => (int) $category->courseid,
            'name' => (string) $category->fullname,
            'aggregation' => (int) $category->aggregation,
            'hidden' => (bool) $category->hidden,
            'time_modified' => (int) $category->timemodified,
        ];
    }

    /**
     * Convert a grade item object to an advanced canonical response shape.
     *
     * @param \grade_item $item Item.
     * @return array
     */
    public static function manual_grade_item_to_response(\grade_item $item): array {
        return [
            'item_id' => (int) $item->id,
            'course_id' => (int) $item->courseid,
            'category_id' => (int) ($item->categoryid ?? 0),
            'name' => (string) ($item->itemname ?? ''),
            'item_type' => (string) ($item->itemtype ?? ''),
            'grade_min' => (float) ($item->grademin ?? 0),
            'grade_max' => (float) ($item->grademax ?? 0),
            'grade_pass' => (float) ($item->gradepass ?? 0),
            'hidden' => (bool) ($item->hidden ?? false),
            'locked' => (bool) ($item->locked ?? false),
            'time_modified' => (int) ($item->timemodified ?? 0),
        ];
    }

    /**
     * Convert Moodle user grade item data to the canonical response shape.
     *
     * @param mixed $item Item.
     * @return array
     */
    public static function user_grade_item_to_response($item): array {
        $item = self::to_array($item);
        $graderaw = $item['graderaw'] ?? null;

        return [
            'item_id' => (int) ($item['id'] ?? 0),
            'name' => (string) ($item['itemname'] ?? ''),
            'item_type' => (string) ($item['itemtype'] ?? ''),
            'item_module' => (string) ($item['itemmodule'] ?? ''),
            'item_instance' => (int) ($item['iteminstance'] ?? 0),
            'course_module_id' => (int) ($item['cmid'] ?? 0),
            'grade_raw' => $graderaw === null ? 0.0 : (float) $graderaw,
            'grade_formatted' => (string) ($item['gradeformatted'] ?? ''),
            'grade_min' => (float) ($item['grademin'] ?? 0),
            'grade_max' => (float) ($item['grademax'] ?? 0),
            'range_formatted' => (string) ($item['rangeformatted'] ?? ''),
            'percentage_formatted' => (string) ($item['percentageformatted'] ?? ''),
            'feedback' => (string) ($item['feedback'] ?? ''),
            'hidden' => (bool) ($item['gradeishidden'] ?? false),
            'locked' => (bool) ($item['gradeislocked'] ?? false),
        ];
    }

    /**
     * Throw when Moodle returns external API warnings.
     *
     * @param array $warnings Warnings.
     */
    public static function fail_on_warnings(array $warnings): void {
        if (empty($warnings)) {
            return;
        }

        $warning = self::to_array(reset($warnings));
        $message = (string) ($warning['message'] ?? $warning['warningcode'] ?? 'Moodle gradebook operation returned warnings.');
        throw new \moodle_exception('error', 'local_moodlia', '', null, $message);
    }

    /**
     * Load and validate a grade category in a course.
     *
     * @param int $courseid Courseid.
     * @param int $categoryid Categoryid.
     * @return \grade_category
     */
    public static function get_grade_category(int $courseid, int $categoryid): \grade_category {
        self::require_gradebook_api();

        $category = \grade_category::fetch([
            'id' => $categoryid,
            'courseid' => $courseid,
        ]);

        if (!$category) {
            throw new \invalid_parameter_exception('Grade category not found in the selected course.');
        }

        return $category;
    }

    /**
     * Load and validate a grade item in a course.
     *
     * @param int $courseid Courseid.
     * @param int $itemid Itemid.
     * @return \grade_item
     */
    public static function get_grade_item(int $courseid, int $itemid): \grade_item {
        self::require_gradebook_api();

        $item = \grade_item::fetch([
            'id' => $itemid,
            'courseid' => $courseid,
        ]);

        if (!$item) {
            throw new \invalid_parameter_exception('Grade item not found in the selected course.');
        }

        return $item;
    }

    /**
     * Ensure that a grade item is manually owned.
     *
     * @param \grade_item $item Item.
     */
    public static function require_manual_grade_item(\grade_item $item): void {
        if (($item->itemtype ?? '') !== 'manual') {
            throw new \invalid_parameter_exception('Only manual grade items can be changed by this operation.');
        }
    }

    /**
     * Convert objects and nested arrays to arrays.
     *
     * @param mixed $value Value.
     * @return array
     */
    private static function to_array($value): array {
        if (is_array($value)) {
            return array_map(static function ($item) {
                return is_object($item) || is_array($item) ? self::to_array($item) : $item;
            }, $value);
        }

        if (is_object($value)) {
            return self::to_array(get_object_vars($value));
        }

        return [];
    }
}
