<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/sellix_integration.php';

// Get the JSON payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid payload');
}

// Get the user's Sellix webhook secret
$stmt = $pdo->prepare("SELECT sellix_webhook_secret FROM users WHERE id = ?");
$stmt->execute([$data['user_id']]);
$webhook_secret = $stmt->fetchColumn();

if (!$webhook_secret) {
    http_response_code(404);
    exit('User not found or webhook secret not set');
}

// Handle the webhook
handle_sellix_webhook($data, $webhook_secret);
