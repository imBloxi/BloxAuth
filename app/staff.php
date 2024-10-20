<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if the user is a staff member
$stmt = $pdo->prepare('SELECT is_staff FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['is_staff']) {
    echo "Access denied.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $ban_reason = $_POST['ban_reason'] ?? null;

    if ($user_id && $ban_reason) {
        $stmt = $pdo->prepare('UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?');
        $stmt->execute([$ban_reason, $user_id]);
        echo "User banned successfully.";
    } else {
        echo "Invalid request.";
    }
} else {
    echo "Invalid request method.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ban User</title>
</head>
<body>
    <h1>Ban User</h1>
    <form action="ban_user.php" method="post">
        <label for="user_id">User ID:</label>
        <input type="text" id="user_id" name="user_id" required>
        <br>
        <label for="ban_reason">Ban Reason:</label>
        <input type="text" id="ban_reason" name="ban_reason" required>
        <br>
        <button type="submit">Ban User</button>
    </form>
</body>
</html>
