<?php
// Accept or reject a ban appeal from review_applications.php.
require_once '../includes/config.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'accept') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($target_id = $stmt->fetchColumn()) {
            unbanUser($pdo, $target_id, $_SESSION['user_id']);
        }
    }
    if ($action === 'accept' || $action === 'reject') {
        $pdo->prepare('DELETE FROM applications WHERE username = ?')->execute([$username]);
    }
}
redirect('/app/review_applications.php');
