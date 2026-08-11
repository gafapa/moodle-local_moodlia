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
 * Minimal JSON-RPC MCP endpoint for MoodlIA.
 *
 * @package    local_moodlia
 * @copyright  2026 Pablo Gallego
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

const LOCAL_MOODLIA_MCP_PROTOCOL_VERSIONS = [
    '2025-11-25',
    '2025-06-18',
    '2025-03-26',
];
const LOCAL_MOODLIA_MCP_MAX_REQUEST_BYTES = 32 * 1024 * 1024;

require_once(__DIR__ . '/../../config.php');

/**
 * Send common security and cache headers for MCP responses.
 */
function local_moodlia_mcp_response_headers(): void {
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
}

/**
 * Send a JSON-RPC response.
 *
 * @param mixed $id Request id.
 * @param mixed $result Result payload.
 * @return never
 */
function local_moodlia_mcp_result($id, $result): never {
    local_moodlia_mcp_response_headers();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'result' => $result,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a JSON-RPC error.
 *
 * @param mixed $id Request id.
 * @param int $code JSON-RPC error code.
 * @param string $message Error message.
 * @param int $httpstatus HTTP status code.
 * @param string $canonicalcode Canonical transport-independent error code.
 * @param array $details Additional safe error metadata.
 * @return never
 */
function local_moodlia_mcp_error(
    $id,
    int $code,
    string $message,
    int $httpstatus = 200,
    string $canonicalcode = 'moodle_error',
    array $details = []
): never {
    http_response_code($httpstatus);
    local_moodlia_mcp_response_headers();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'error' => [
            'code' => $code,
            'message' => $message,
            'data' => [
                'code' => $canonicalcode,
                'details' => $details,
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Complete an MCP notification without returning a JSON-RPC response body.
 */
function local_moodlia_mcp_notification_accepted(): never {
    http_response_code(202);
    local_moodlia_mcp_response_headers();
    exit;
}

/**
 * Return the scheme and authority portion of a URL.
 *
 * @param string $url Absolute URL.
 * @return string
 */
function local_moodlia_mcp_origin(string $url): string {
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    $origin = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
    if (isset($parts['port'])) {
        $origin .= ':' . (int) $parts['port'];
    }

    return $origin;
}

/**
 * Reject cross-origin browser requests to prevent DNS rebinding and token misuse.
 */
function local_moodlia_mcp_validate_origin(): void {
    global $CFG;

    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return;
    }

    if (hash_equals(local_moodlia_mcp_origin($CFG->wwwroot), local_moodlia_mcp_origin($origin))) {
        return;
    }

    local_moodlia_mcp_error(null, -32003, 'Origin is not allowed.', 403, 'missing_capability');
}

/**
 * Map Moodle REST error payloads to canonical error codes.
 *
 * @param array $payload Moodle REST error payload.
 * @return string
 */
function local_moodlia_mcp_moodle_error_code(array $payload): string {
    $combined = strtolower(($payload['errorcode'] ?? '') . ' ' . ($payload['exception'] ?? ''));
    if (str_contains($combined, 'invalid') || str_contains($combined, 'parameter')) {
        return 'invalid_parameters';
    }
    if (str_contains($combined, 'capability') || str_contains($combined, 'permission') || str_contains($combined, 'access')) {
        return 'missing_capability';
    }
    if (str_contains($combined, 'notfound') || str_contains($combined, 'not_found')) {
        return 'not_found';
    }
    if (str_contains($combined, 'coding_exception')) {
        return 'internal_error';
    }

    return 'moodle_error';
}

/**
 * Read the bearer token from the Authorization header.
 *
 * @return string
 */
function local_moodlia_mcp_bearer_token(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!preg_match('/^Bearer\s+(.+)$/', trim($header), $matches)) {
        local_moodlia_mcp_error(null, -32001, 'Missing bearer token.', 401, 'missing_capability');
    }

    $token = trim($matches[1]);
    if ($token === '' || strlen($token) > 4096) {
        local_moodlia_mcp_error(null, -32001, 'Invalid bearer token.', 401, 'missing_capability');
    }

    return $token;
}

/**
 * Normalize MCP tool arguments for Moodle REST form encoding.
 *
 * @param mixed $id Request id.
 * @param mixed $arguments Tool arguments.
 * @return array
 */
function local_moodlia_mcp_normalize_arguments($id, $arguments): array {
    if ($arguments === null) {
        return [];
    }

    if (!is_array($arguments)) {
        local_moodlia_mcp_error($id, -32602, 'Tool arguments must be an object.', 200, 'invalid_parameters');
    }

    $normalized = [];
    foreach ($arguments as $key => $value) {
        if (is_bool($value)) {
            $normalized[$key] = $value ? '1' : '0';
            continue;
        }

        if (is_array($value) || is_object($value)) {
            $normalized[$key] = json_encode($value, JSON_UNESCAPED_SLASHES);
            continue;
        }

        if ($value !== null) {
            $normalized[$key] = (string) $value;
        }
    }

    return $normalized;
}

/**
 * Wrap an operation response in the MCP CallToolResult shape.
 *
 * @param mixed $payload Canonical operation response.
 * @return array
 */
function local_moodlia_mcp_tool_result($payload): array {
    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        $encoded = 'null';
    }

    return [
        'content' => [
            [
                'type' => 'text',
                'text' => $encoded,
            ],
        ],
        'structuredContent' => $payload,
        'isError' => false,
    ];
}

/**
 * Call the matching Moodle REST web service function.
 *
 * @param string $token Moodle REST token.
 * @param string $toolname Canonical operation name.
 * @param array $arguments Tool arguments.
 * @param mixed $id Request id.
 * @return mixed
 */
function local_moodlia_mcp_call_rest(string $token, string $toolname, array $arguments, $id = null) {
    global $CFG;

    $body = array_merge([
        'wstoken' => $token,
        'wsfunction' => 'local_moodlia_' . $toolname,
        'moodlewsrestformat' => 'json',
    ], $arguments);

    $client = new \core\http_client([
        'connect_timeout' => 10,
        'timeout' => 60,
        'http_errors' => false,
    ]);

    try {
        $response = $client->post($CFG->wwwroot . '/webservice/rest/server.php', [
            'form_params' => $body,
        ]);
    } catch (\Throwable $exception) {
        debugging('MoodlIA MCP REST transport failed: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        local_moodlia_mcp_error($id, -32002, 'Moodle REST transport failed.', 200, 'transport_error');
    }

    $raw = (string) $response->getBody();
    $status = $response->getStatusCode();

    $payload = json_decode($raw, true);
    if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
        local_moodlia_mcp_error($id, -32003, 'REST transport returned invalid JSON.', 200, 'transport_error');
    }

    if ($status < 200 || $status >= 300) {
        local_moodlia_mcp_error($id, -32004, 'REST transport returned HTTP ' . $status . '.', 200, 'transport_error', [
            'http_status' => $status,
        ]);
    }

    if (is_array($payload) && (isset($payload['exception']) || isset($payload['errorcode']))) {
        $canonicalcode = local_moodlia_mcp_moodle_error_code($payload);
        $message = $canonicalcode === 'internal_error'
            ? 'Moodle could not complete the request.'
            : ($payload['message'] ?? $payload['errorcode'] ?? 'Moodle REST error.');
        local_moodlia_mcp_error($id, -32005, $message, 200, $canonicalcode, [
            'moodle_errorcode' => $payload['errorcode'] ?? null,
            'moodle_exception' => $payload['exception'] ?? null,
        ]);
    }

    return $payload;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    local_moodlia_mcp_error(null, -32600, 'Only POST requests are supported.', 405, 'invalid_parameters');
}

local_moodlia_mcp_validate_origin();

$contenttype = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if ($contenttype !== 'application/json') {
    local_moodlia_mcp_error(null, -32600, 'Content-Type must be application/json.', 415, 'invalid_parameters');
}

$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
if ($accept !== '' && !str_contains($accept, '*/*') && !str_contains($accept, 'application/json')) {
    local_moodlia_mcp_error(null, -32600, 'Accept must allow application/json.', 406, 'invalid_parameters');
}

$protocolheader = trim((string) ($_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? ''));
if ($protocolheader !== '' && !in_array($protocolheader, LOCAL_MOODLIA_MCP_PROTOCOL_VERSIONS, true)) {
    local_moodlia_mcp_error(null, -32602, 'Unsupported MCP protocol version.', 400, 'invalid_parameters', [
        'supported' => LOCAL_MOODLIA_MCP_PROTOCOL_VERSIONS,
    ]);
}

$contentlength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentlength > LOCAL_MOODLIA_MCP_MAX_REQUEST_BYTES) {
    local_moodlia_mcp_error(null, -32600, 'MCP request body is too large.', 413, 'invalid_parameters');
}

$token = local_moodlia_mcp_bearer_token();
$rawrequest = file_get_contents('php://input');
if ($rawrequest !== false && strlen($rawrequest) > LOCAL_MOODLIA_MCP_MAX_REQUEST_BYTES) {
    local_moodlia_mcp_error(null, -32600, 'MCP request body is too large.', 413, 'invalid_parameters');
}
$request = json_decode($rawrequest ?: '', true);

if (!is_array($request)) {
    local_moodlia_mcp_error(null, -32700, 'Invalid JSON request.', 200, 'invalid_parameters');
}

$id = $request['id'] ?? null;
$method = $request['method'] ?? null;
$params = $request['params'] ?? [];
$isnotification = !array_key_exists('id', $request);

if (($request['jsonrpc'] ?? null) !== '2.0' || !is_string($method)) {
    local_moodlia_mcp_error($id, -32600, 'Invalid JSON-RPC request.', 200, 'invalid_parameters');
}

if ($method === 'initialize') {
    $requestedversion = is_array($params) ? (string) ($params['protocolVersion'] ?? '') : '';
    if ($requestedversion === '') {
        local_moodlia_mcp_error($id, -32602, 'initialize requires protocolVersion.', 200, 'invalid_parameters');
    }
    $protocolversion = in_array($requestedversion, LOCAL_MOODLIA_MCP_PROTOCOL_VERSIONS, true)
        ? $requestedversion
        : LOCAL_MOODLIA_MCP_PROTOCOL_VERSIONS[0];

    local_moodlia_mcp_call_rest($token, 'get_current_user', [], $id);
    local_moodlia_mcp_result($id, [
        'protocolVersion' => $protocolversion,
        'capabilities' => [
            'tools' => [
                'listChanged' => false,
            ],
        ],
        'serverInfo' => [
            'name' => 'MoodlIA',
            'title' => 'MoodlIA Moodle MCP Server',
            'version' => '0.1.186',
        ],
        'instructions' => 'Use MoodlIA tools to operate Moodle within the capabilities of the authenticated user.',
    ]);
}

if ($method === 'notifications/initialized') {
    local_moodlia_mcp_call_rest($token, 'get_current_user', [], $id);
    local_moodlia_mcp_notification_accepted();
}

if ($isnotification) {
    local_moodlia_mcp_notification_accepted();
}

if ($method === 'ping') {
    local_moodlia_mcp_call_rest($token, 'get_current_user', [], $id);
    local_moodlia_mcp_result($id, (object) []);
}

if ($method === 'tools/list') {
    local_moodlia_mcp_call_rest($token, 'get_current_user', [], $id);
    local_moodlia_mcp_result($id, [
        'tools' => \local_moodlia\mcp\manifest::tools(),
    ]);
}

if ($method === 'tools/call') {
    if (!is_array($params) || !isset($params['name']) || !is_string($params['name'])) {
        local_moodlia_mcp_error($id, -32602, 'tools/call requires a tool name.', 200, 'invalid_parameters');
    }

    $toolname = $params['name'];
    if (!in_array($toolname, \local_moodlia\mcp\manifest::tool_names(), true)) {
        local_moodlia_mcp_error($id, -32601, 'Unknown tool: ' . $toolname, 200, 'invalid_parameters', [
            'tool' => $toolname,
        ]);
    }

    $arguments = local_moodlia_mcp_normalize_arguments($id, $params['arguments'] ?? []);
    $result = local_moodlia_mcp_call_rest($token, $toolname, $arguments, $id);
    local_moodlia_mcp_result($id, local_moodlia_mcp_tool_result($result));
}

local_moodlia_mcp_error($id, -32601, 'Unknown method: ' . $method, 200, 'invalid_parameters', [
    'method' => $method,
]);
