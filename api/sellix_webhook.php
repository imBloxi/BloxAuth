<?php
// Sellix webhook for licenses sold through a user's own Sellix store.
// The signature is checked with the secret of the user who owns the product,
// so nothing in the payload is trusted before verification.
require_once '../includes/config.php';
require_once '../includes/sellix_integration.php';

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!is_array($data)) {
    http_response_code(400);
    exit('Invalid payload');
}

handle_sellix_webhook($pdo, $payload, $data);
