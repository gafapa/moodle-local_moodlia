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
 * Native Moodle activity or section backup import operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Imports an activity or section .mbz backup into an existing course.
 */
class restore_course_item_backup {
    /**
     * Execute the operation.
     *
     * @param int $backupfileid Backupfileid.
     * @param int $targetcourseid Targetcourseid.
     * @param int|null $targetsectionnumber Targetsectionnumber.
     * @return array
     */
    public static function execute(int $backupfileid, int $targetcourseid, ?int $targetsectionnumber = null): array {
        return course_backup_tools::restore_course_item_backup($backupfileid, $targetcourseid, $targetsectionnumber);
    }
}
