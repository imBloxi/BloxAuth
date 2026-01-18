<?php
require '../includes/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $license_key = $_POST['license_key'];
    $roblox_id = $_POST['roblox_id'];
    $place_id = $_POST['place_id'] ?? null;

    // Create a unique cache key for this validation request
    $cache_key = 'license_validation_' . md5($license_key . '_' . $roblox_id . '_' . $place_id);
    
    // Try to get cached validation result
    $cached_result = get_cache($cache_key);
    if ($cached_result !== null) {
        header('Content-Type: application/json');
        header('X-Cache: HIT');
        echo json_encode($cached_result);
        exit;
    }

    // Query the database if not cached
    $stmt = $pdo->prepare('SELECT * FROM licenses WHERE `key` = ? AND roblox_id = ?');
    $stmt->execute([$license_key, $roblox_id]);
    $license = $stmt->fetch();

    if ($license && ($place_id === null || $license['place_id'] === $place_id)) {
        // Check if license is expired
        if ($license['valid_until'] && strtotime($license['valid_until']) < time()) {
            $result = ['status' => 'failure', 'message' => 'License has expired'];
        } 
        // Check if license is revoked or banned
        elseif (isset($license['is_revoked']) && $license['is_revoked']) {
            $result = ['status' => 'failure', 'message' => 'License has been revoked'];
        }
        elseif (isset($license['is_banned']) && $license['is_banned']) {
            $result = ['status' => 'failure', 'message' => 'License has been banned'];
        }
        // Check max uses if applicable
        elseif ($license['max_uses'] && $license['current_uses'] >= $license['max_uses']) {
            $result = ['status' => 'failure', 'message' => 'License usage limit exceeded'];
        }
        else {
            // Log the successful validation
            $stmt = $pdo->prepare('INSERT INTO usage_logs (license_id, roblox_id, place_id, success, created_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$license['id'], $roblox_id, $place_id, 1, date('Y-m-d H:i:s')]);
            
            // Update last_used timestamp and increment current_uses
            $stmt = $pdo->prepare('UPDATE licenses SET last_used = NOW(), current_uses = current_uses + 1 WHERE id = ?');
            $stmt->execute([$license['id']]);
            
            $result = ['status' => 'success', 'license_id' => $license['id']];
            
            // Cache successful validations for the configured TTL
            set_cache($cache_key, $result, CACHE_TTL_LICENSE_VALIDATION);
        }
    } else {
        $result = ['status' => 'failure', 'message' => 'Invalid license key or Roblox ID'];
    }
    
    header('Content-Type: application/json');
    header('X-Cache: MISS');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
}
?>
