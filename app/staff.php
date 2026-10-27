<?php
require_once '../includes/config.php';
ensure_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int) ($_POST['user_id'] ?? 0);
    $ban_reason = trim($_POST['ban_reason'] ?? '');

    if ($target_id && $ban_reason !== '' && get_user_by_id($target_id)) {
        banUser($pdo, $target_id, $_SESSION['user_id'], $ban_reason);
        $message = "User banned successfully.";
    } else {
        $message = "Invalid request.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ban User</title>
</head>
<body>
    <h1>Ban User</h1>
    <?php if ($message): ?><p><?php echo e($message); ?></p><?php endif; ?>
    <form action="" method="post">
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
