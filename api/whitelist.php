<?php
// Check that a license key exists and belongs to the API key's owner.
// Auth: Authorization header (raw key or "Bearer <key>") or X-API-Key. JSON body: {license_key}
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$user_id = validate_api_key(get_request_api_key());
if (!$user_id) {
    json_response(['error' => 'Unauthorized'], 401);
}

$data = read_json_body() ?? [];
$stmt = $pdo->prepare('SELECT * FROM licenses_new WHERE `key` = ? AND user_id = ?');
$stmt->execute([(string) ($data['license_key'] ?? ''), $user_id]);
$license = $stmt->fetch();

if (!$license) {
    json_response(['status' => 'error', 'message' => 'License not found'], 404);
}
$status = license_status($license);
json_response([
    'status' => $status === 'Active' ? 'success' : 'error',
    'message' => $status === 'Active' ? 'License is valid' : "License is $status",
]);
