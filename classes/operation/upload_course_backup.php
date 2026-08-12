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
 * Upload native Moodle course backup operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Stores an uploaded .mbz backup for later restore.
 */
class upload_course_backup {
    /**
     * Execute the operation.
     *
     * @param string $filename Filename.
     * @param string $uploadreference Uploadreference.
     * @return array
     */
    public static function execute(string $filename, string $uploadreference): array {
        return course_backup_tools::upload_backup_file($filename, $uploadreference);
    }
}
