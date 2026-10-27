<?php
// Endpoints used by the in-game Lua client (api/lua.lua):
//   POST api.php?action=validate_license  {license_key, roblox_user_id, roblox_place_id}
//   POST api.php?action=log_usage         {license_key, roblox_user_id, roblox_place_id, action}
// The license key itself is the credential. It can also be sent as "Authorization: Bearer <key>".
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST required'], 405);
}
if (!check_rate_limit('game_api', $_SERVER['REMOTE_ADDR'], API_RATE_LIMIT, 60)) {
    json_response(['error' => 'Too many requests'], 429);
}

$data = read_json_body() ?? [];
$license_key = trim((string) ($data['license_key'] ?? get_request_api_key() ?? ''));
$roblox_id = $data['roblox_user_id'] ?? $data['roblox_id'] ?? null;
$place_id = $data['roblox_place_id'] ?? $data['place_id'] ?? null;

if ($license_key === '') {
    json_response(['error' => 'license_key is required'], 400);
}

// Support both ?action=... and the older /api.php/validate_license style.
$action = $_GET['action'] ?? basename($_SERVER['PATH_INFO'] ?? '');

switch ($action) {
    case 'validate_license':
        $result = validate_license_key($pdo, $license_key, $roblox_id, $place_id);
        json_response($result['valid']
            ? ['status' => 'success']
            : ['status' => 'failure', 'message' => $result['message']]);

    case 'log_usage':
        $stmt = $pdo->prepare('SELECT id FROM licenses_new WHERE `key` = ? AND is_revoked = 0');
        $stmt->execute([$license_key]);
        $license_id = $stmt->fetchColumn();
        if (!$license_id) {
            json_response(['error' => 'Invalid license key'], 401);
        }
        $pdo->prepare('INSERT INTO license_logs (license_id, ip_address, user_agent, action) VALUES (?, ?, ?, ?)')
            ->execute([$license_id, $_SERVER['REMOTE_ADDR'], substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), substr((string) ($data['action'] ?? 'usage'), 0, 50) . ($roblox_id !== null ? ':' . $roblox_id : '')]);
        json_response(['status' => 'success']);

    default:
        json_response(['error' => 'Endpoint not found'], 404);
}
