<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$headers = getallheaders();
$apiKey = $headers['Authorization'] ?? '';

$stmt = $pdo->prepare('SELECT user_id FROM api_keys WHERE api_key = ?');
$stmt->execute([$apiKey]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = $data['license_key'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM licenses_new WHERE license_key = ? AND user_id = ?');
$stmt->execute([$licenseKey, $user['user_id']]);
$license = $stmt->fetch();

if ($license) {
    echo json_encode(['status' => 'success', 'message' => 'License is valid']);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'License not found']);
}
?>