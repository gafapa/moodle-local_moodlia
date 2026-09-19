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
 * Assignment update failure event.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\event;

/**
 * Records server-side assignment update diagnostics under a correlation id.
 */
class assignment_update_failed extends \core\event\base {
    /**
     * Initialise event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventassignmentupdatefailed', 'local_moodlia');
    }

    /**
     * Describe the recorded failure without repeating SQL in rendered output.
     *
     * @return string
     */
    public function get_description(): string {
        return "Assignment update {$this->other['module_id']} failed with correlation ID "
            . "{$this->other['correlation_id']} ({$this->other['failure_type']}).";
    }
}
