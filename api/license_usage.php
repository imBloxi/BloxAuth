<?php
require_once '../includes/config.php';

$license_id = $_GET['license_id'] ?? null;

if (!$license_id) {
    api_response('error', 'Missing license ID');
}

$stmt = $pdo->prepare("SELECT DATE(timestamp) as date, COUNT(*) as count FROM license_usage WHERE license_id = ? GROUP BY DATE(timestamp) ORDER BY date");
$stmt->execute([$license_id]);
$usage_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = array_column($usage_data, 'date');
$usage = array_column($usage_data, 'count');

api_response('success', 'Usage data retrieved', ['labels' => $labels, 'usage' => $usage]);