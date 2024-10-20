<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || !isAdmin($pdo, $_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$users = $pdo->query('SELECT * FROM users')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';
    $moderatorId = $_SESSION['user_id'];

    switch ($action) {
        case 'suspend':
            suspendUser($pdo, $userId, $moderatorId, $reason);
            break;
        case 'ban':
            banUser($pdo, $userId, $moderatorId, $reason);
            break;
        case 'unban':
            unbanUser($pdo, $userId, $moderatorId);
            break;
    }

    header('Location: manage_users.php');
    exit;
}
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
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
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
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
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