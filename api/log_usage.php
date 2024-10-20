<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $license_id = $_POST['license_id'];
    $roblox_id = $_POST['roblox_id'];
    $place_id = $_POST['place_id'];
    $success = $_POST['success'];

    $stmt = $pdo->prepare('INSERT INTO usage_logs (license_id, roblox_id, place_id, success, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$license_id, $roblox_id, $place_id, $success, date('Y-m-d H:i:s')]);

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
}
?>
