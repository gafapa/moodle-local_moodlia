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
 * Native Moodle course restore operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Restores a native Moodle .mbz course backup.
 */
class restore_course_backup {
    /**
     * Execute the operation.
     *
     * @param int $backupfileid Stored backup file id.
     * @param string $target Restore target.
     * @param int $targetcourseid Existing target course id.
     * @param int $categoryid Category id for new course restores.
     * @param string $fullname New course fullname.
     * @param string $shortname New course shortname.
     * @return array
     */
    public static function execute(
        int $backupfileid,
        string $target = 'new_course',
        int $targetcourseid = 0,
        int $categoryid = 0,
        string $fullname = '',
        string $shortname = ''
    ): array {
        return course_backup_tools::restore_course_backup(
            $backupfileid,
            $target,
            $targetcourseid,
            $categoryid,
            $fullname,
            $shortname
        );
    }
}
