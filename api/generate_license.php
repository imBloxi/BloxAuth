<?php
// Create a license. Auth: X-API-Key header (or Authorization: Bearer <key>).
// JSON body: roblox_user_id, whitelist_type, whitelist_id, valid_until, max_uses,
//            description, custom_tier, is_transferable
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method Not Allowed'], 405);
}

$api_key = get_request_api_key();
if (!$api_key) {
    json_response(['error' => 'API key is required'], 401);
}
$user_id = validate_api_key($api_key);
if (!$user_id) {
    json_response(['error' => 'Invalid API key'], 401);
}

$data = read_json_body();
if ($data === null) {
    json_response(['error' => 'Invalid JSON data'], 400);
}

$result = create_license($pdo, $user_id, $data);
if (!$result['success']) {
    json_response(['error' => $result['error']], 400);
}
json_response(['success' => true, 'license_key' => $result['license_key']], 201);
