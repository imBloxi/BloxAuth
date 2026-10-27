<?php
// Daily validation counts for one of the logged-in user's licenses (used by dashboard charts).
require_once '../includes/config.php';

if (!is_user_logged_in()) {
    api_response('error', 'Not logged in', null, 401);
}

$license_id = $_GET['license_id'] ?? null;
if (!$license_id || !get_license_details($pdo, $license_id, $_SESSION['user_id'])) {
    api_response('error', 'License not found', null, 404);
}

$stmt = $pdo->prepare("SELECT DATE(created_at) AS date, COUNT(*) AS count FROM usage_logs WHERE license_id = ? GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute([$license_id]);
$usage_data = $stmt->fetchAll();

api_response('success', 'Usage data retrieved', [
    'labels' => array_column($usage_data, 'date'),
    'usage' => array_map('intval', array_column($usage_data, 'count')),
]);
