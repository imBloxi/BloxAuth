<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$passkey = $_POST['passkey'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($passkey) || $passkey === '') {
    json_response(['success' => false, 'error' => 'passkey is required'], 400);
}

// Stored hashed, like a password.
$pdo->prepare("UPDATE users SET passkey = ? WHERE id = ?")->execute([password_hash($passkey, PASSWORD_DEFAULT), $_SESSION['user_id']]);
json_response(['success' => true]);
