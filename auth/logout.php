<?php
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    $pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')->execute([$_SESSION['user_id']]);
}
setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);

$_SESSION = [];
session_destroy();

redirect('/auth/login.php');
