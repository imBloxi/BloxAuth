<?php
// Record a usage event for a license. POST license_key, roblox_id, place_id, success.
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'failure', 'message' => 'Invalid request method'], 405);
}
if (!check_rate_limit('log_usage', $_SERVER['REMOTE_ADDR'], API_RATE_LIMIT, 60)) {
    json_response(['status' => 'failure', 'message' => 'Too many requests'], 429);
}

$input = read_json_body() ?? $_POST;
$stmt = $pdo->prepare('SELECT id FROM licenses_new WHERE `key` = ?');
$stmt->execute([(string) ($input['license_key'] ?? '')]);
$license_id = $stmt->fetchColumn();

if (!$license_id) {
    json_response(['status' => 'failure', 'message' => 'Invalid license key'], 401);
}

log_usage($license_id, $input['roblox_id'] ?? '', $input['place_id'] ?? '', !empty($input['success']));
json_response(['status' => 'success']);
