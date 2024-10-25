<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Extract the Bearer token from the Authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$matches = [];
preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
$apiToken = $matches[1] ?? '';

// Validate the API token
if (!validate_api_token($pdo, $apiToken)) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Invalid or missing API token']);
    exit;
}

// Switch statement based on URL's endpoint
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, 'log_usage') !== false) {
    // Handle logging usage
    $data = json_decode(file_get_contents('php://input'), true);
    log_usage($data);
} elseif (strpos($uri, 'validate_license') !== false) {
    // Handle license validation
    $data = json_decode(file_get_contents('php://input'), true);
    validate_license($data);
} else {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Endpoint not found']);
}

function log_usage($data) {
    // Validate data
    if (!isset($data['roblox_user_id'], $data['license_key'], $data['action'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // Implement your logging logic here
    // Example: insert log data into database
    // ...

    echo json_encode(['status' => 'success']);
}

function validate_license($data) {
    if (!isset($data['license_key'], $data['roblox_place_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // Implement your license validation logic here
    // Example: check license in database
    // ...

    echo json_encode(['status' => 'success']);
}

function validate_api_token($pdo, $token) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE token = ?");
    $stmt->execute([$token]);
    return $stmt->fetchColumn() > 0;
}
?>
