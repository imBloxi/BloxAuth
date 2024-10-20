<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$passkey = $_POST['passkey'];

// Store the passkey securely
$stmt = $pdo->prepare("UPDATE users SET passkey = ? WHERE id = ?");
$stmt->execute([$passkey, $user_id]);

echo json_encode(['success' => true]);
?>
