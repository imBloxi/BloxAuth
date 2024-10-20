<?php
require '../includes/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$license_key = $data['license_key'] ?? null;
$place_id = $data['place_id'] ?? null;
$group_id = $data['group_id'] ?? null;
$developer_id = $data['developer_id'] ?? null;
$seller_id = $data['seller_id'] ?? null;

if (!$license_key || !$place_id || !$group_id || !$developer_id || !$seller_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
    exit();
}

try {
    // Log the request for auditing
    $log_stmt = $pdo->prepare('INSERT INTO api_logs (license_key, place_id, group_id, developer_id, seller_id, request_time) VALUES (?, ?, ?, ?, ?, NOW())');
    $log_stmt->execute([$license_key, $place_id, $group_id, $developer_id, $seller_id]);

    // Validate the license
    $stmt = $pdo->prepare('SELECT * FROM licenses_new WHERE `key` = ? AND whitelist_id = ? AND whitelist_type = ?');
    $stmt->execute([$license_key, $place_id, 'place']);
    $license = $stmt->fetch();

    if ($license) {
        // Additional checks for developer_id and group_id
        if ($license['developer_id'] == $developer_id && $license['group_id'] == $group_id) {
            echo json_encode(['status' => 'success', 'message' => 'License is valid.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Developer ID or Group ID mismatch.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'License not found or invalid.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'General error: ' . $e->getMessage()]);
}
?>
