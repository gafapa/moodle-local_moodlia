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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Configure a disposable loopback Moodle installation for REST qualification.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
if (getenv('MOODLIA_COMPATIBILITY_SITE') !== '1') {
    throw new RuntimeException('MOODLIA_COMPATIBILITY_SITE=1 is required.');
}
require($argv[1] . '/config.php');
if (!in_array(parse_url($CFG->wwwroot, PHP_URL_HOST), ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Only disposable loopback sites are allowed.');
}
require_once($CFG->libdir . '/externallib.php');
$admin = get_admin();
\core\session\manager::set_user($admin);
set_config('enablewebservices', 1);
set_config('webserviceprotocols', 'rest');
$DB->set_field('modules', 'visible', 1, ['name' => 'subsection']);
$service = $DB->get_record('external_services', ['shortname' => 'local_moodlia'], '*', MUST_EXIST);
$DB->set_field('external_services', 'enabled', 1, ['id' => $service->id]);
if (!$DB->record_exists('external_services_users', ['externalserviceid' => $service->id, 'userid' => $admin->id])) {
    $DB->insert_record('external_services_users', (object) [
        'externalserviceid' => $service->id,
        'userid' => $admin->id,
        'iprestriction' => '',
        'validuntil' => 0,
        'timecreated' => time(),
    ]);
}
$token = \core_external\util::generate_token(
    EXTERNAL_TOKEN_PERMANENT, $service, (int) $admin->id, \context_system::instance(), 0, ''
);
// The caller captures stdout privately and masks the token before running HTTP tests.
echo $token;
