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
 * MoodlIA status operation.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodlia\operation;

/**
 * Returns diagnostic information available to a MoodlIA-scoped token.
 */
class get_moodlia_status {
    /**
     * Execute the operation.
     *
     * @return array
     */
    public static function execute(): array {
        global $CFG, $USER, $SITE;

        $plugin = self::plugin_metadata();
        $functions = self::service_functions();

        return [
            'component' => 'local_moodlia',
            'site_url' => (string) $CFG->wwwroot,
            'site_name' => format_string($SITE->fullname ?? ''),
            'moodle_release' => (string) ($CFG->release ?? ''),
            'moodle_version' => (string) ($CFG->version ?? ''),
            'plugin_release' => (string) ($plugin->release ?? ''),
            'plugin_version' => (int) ($plugin->version ?? 0),
            'user_id' => (int) ($USER->id ?? 0),
            'username' => (string) ($USER->username ?? ''),
            'can_use_api' => has_capability('local/moodlia:useapi', \context_system::instance()),
            'rest_service' => 'local_moodlia',
            'function_count' => count($functions),
            'functions_json' => course_workflow_tools::encode_json($functions),
        ];
    }

    /**
     * Return plugin metadata from version.php without relying on native webservice introspection.
     *
     * @return \stdClass
     */
    private static function plugin_metadata(): \stdClass {
        global $CFG;

        $plugin = new \stdClass();
        $versionfile = $CFG->dirroot . '/local/moodlia/version.php';
        if (is_readable($versionfile)) {
            include($versionfile);
        }

        return $plugin;
    }

    /**
     * Return MoodlIA service function names declared by the plugin.
     *
     * @return array
     */
    private static function service_functions(): array {
        global $CFG;

        $functions = [];
        $servicesfile = $CFG->dirroot . '/local/moodlia/db/services.php';
        if (is_readable($servicesfile)) {
            include($servicesfile);
        }

        return array_values(array_filter(array_keys($functions), static fn($name) => strpos($name, 'local_moodlia_') === 0));
    }
}
