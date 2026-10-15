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
 * Session-authenticated WebMCP browser endpoint.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new invalid_parameter_exception('WebMCP requests must use POST.');
    }
    \local_moodlia\webmcp\bridge::require_access();
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    if ($action === 'list') {
        $result = \local_moodlia\webmcp\bridge::tools();
    } else if ($action === 'upload') {
        $result = \local_moodlia\webmcp\bridge::upload();
    } else if ($action === 'execute') {
        $name = required_param('tool', PARAM_ALPHANUMEXT);
        $arguments = json_decode(required_param('arguments', PARAM_RAW), false, 512, JSON_THROW_ON_ERROR);
        if (!$arguments instanceof stdClass) {
            throw new invalid_parameter_exception('Tool arguments must be a JSON object.');
        }
        $result = \local_moodlia\webmcp\bridge::execute($name, (array) $arguments);
    } else {
        throw new invalid_parameter_exception('Unknown WebMCP request action.');
    }
    echo json_encode(['ok' => true, 'result' => $result], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $exception instanceof moodle_exception ? $exception->getMessage() : get_string('webmcpfailed', 'local_moodlia'),
    ], JSON_THROW_ON_ERROR);
}
