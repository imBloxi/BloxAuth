<?php
// Validate a license key from a game server.
// POST license_key, roblox_id, place_id, optional recheck=1 (form fields or JSON).
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'failure', 'message' => 'Invalid request method'], 405);
}

if (!check_rate_limit('validate_key', $_SERVER['REMOTE_ADDR'], API_RATE_LIMIT, 60)) {
    json_response(['status' => 'failure', 'message' => 'Too many requests'], 429);
}

$input = read_json_body() ?? $_POST;
$license_key = trim((string) ($input['license_key'] ?? ''));
$roblox_id = $input['roblox_id'] ?? $input['roblox_user_id'] ?? null;
$place_id = $input['place_id'] ?? $input['roblox_place_id'] ?? null;

if ($license_key === '') {
    json_response(['status' => 'failure', 'message' => 'license_key is required'], 400);
}

// Re-checks from a server that already passed don't count as a new use.
$recheck = filter_var($input['recheck'] ?? false, FILTER_VALIDATE_BOOLEAN);
$result = validate_license_key($pdo, $license_key, $roblox_id, $place_id, !$recheck);

if ($result['valid']) {
    json_response(['status' => 'success']);
}
json_response(['status' => 'failure', 'message' => $result['message']]);
