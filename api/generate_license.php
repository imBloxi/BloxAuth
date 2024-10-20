<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get the API key from the request headers
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? null;

if (!$api_key) {
    http_response_code(401);
    echo json_encode(['error' => 'API key is required']);
    exit;
}

// Validate the API key
$stmt = $pdo->prepare("SELECT user_id FROM api_keys WHERE api_key = ?");
$stmt->execute([$api_key]);
$user_id = $stmt->fetchColumn();

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Get license data from the request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Generate a new license
$license_key = generate_unique_license_key($pdo);
$license_data = [
    'key' => $license_key,
    'whitelist_id' => $data['whitelist_id'] ?? null,
    'whitelist_type' => $data['whitelist_type'] ?? 'user',
    'description' => $data['description'] ?? '',
    'valid_until' => $data['valid_until'] ?? null,
    'roblox_user_id' => $data['roblox_user_id'] ?? null,
    'max_uses' => $data['max_uses'] ?? null,
    'is_transferable' => $data['is_transferable'] ?? false,
    'custom_tier' => $data['custom_tier'] ?? null
];

if (save_license($pdo, $user_id, $license_data)) {
    http_response_code(201);
    echo json_encode(['success' => true, 'license_key' => $license_key]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create license']);
}
