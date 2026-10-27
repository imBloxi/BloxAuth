<?php
require_once '../includes/config.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int) ($_POST['user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $moderator_id = $_SESSION['user_id'];

    switch (get_user_by_id($target_id) ? ($_POST['action'] ?? '') : '') {
        case 'suspend':
            suspendUser($pdo, $target_id, $moderator_id, $reason);
            break;
        case 'ban':
            banUser($pdo, $target_id, $moderator_id, $reason);
            break;
        case 'unban':
            unbanUser($pdo, $target_id, $moderator_id);
            break;
    }
    redirect('/admin/index.php');
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/dist/output.css">
</head>
<body>
    <h1>Manage Users</h1>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo e($user['username']); ?></td>
                    <td><?php echo e($user['email']); ?></td>
                    <td>
                        <?php
                        if ($user['is_banned']) {
                            echo 'Banned';
                        } elseif ($user['is_suspended']) {
                            echo 'Suspended';
                        } else {
                            echo 'Active';
                        }
                        ?>
                    </td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                            <select name="action">
                                <option value="suspend">Suspend</option>
                                <option value="ban">Ban</option>
                                <option value="unban">Unban</option>
                            </select>
                            <input type="text" name="reason" placeholder="Reason (optional)">
                            <button type="submit">Apply</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>