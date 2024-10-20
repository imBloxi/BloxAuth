<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $license_key = $_POST['license_key'];
    $roblox_id = $_POST['roblox_id'];
    $place_id = $_POST['place_id'] ?? null;

    $stmt = $pdo->prepare('SELECT * FROM licenses WHERE `key` = ? AND roblox_id = ?');
    $stmt->execute([$license_key, $roblox_id]);
    $license = $stmt->fetch();

    if ($license && ($place_id === null || $license['place_id'] === $place_id)) {
        // Log the successful validation
        $stmt = $pdo->prepare('INSERT INTO usage_logs (license_id, roblox_id, place_id, success, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$license['id'], $roblox_id, $place_id, 1, date('Y-m-d H:i:s')]);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'Invalid license key or Roblox ID']);
    }
} else {
    echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
}
?>
