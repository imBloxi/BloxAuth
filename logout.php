<?php
require_once 'includes/config.php';

// Log the logout time
if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
    $stmt = $pdo->prepare("UPDATE user_sessions SET logout_time = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['session_id']]);
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();