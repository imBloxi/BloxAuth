<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();
ensure_admin(); // Ensure the user is an admin

// Fetch all users
$stmt = $pdo->prepare('SELECT * FROM users ORDER BY created_at DESC');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle user suspension
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suspend_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare('UPDATE users SET is_suspended = 1 WHERE id = ?');
    $stmt->execute([$user_id]);
    $_SESSION['message'] = 'User suspended successfully!';
    header('Location: moderation.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation Panel - BloxAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="text-gray-300">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-center text-purple-400">Moderation Panel</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
            <h2 class="text-2xl font-semibold mb-4 text-purple-300">User Management</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="px-4 py-2 text-left">Username</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="border px-4 py-2"><?= htmlspecialchars($user['username']) ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="border px-4 py-2"><?= $user['is_suspended'] ? 'Suspended' : 'Active' ?></td>
                                <td class="border px-4 py-2">
                                    <form action="moderation.php" method="post" class="inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="suspend_user" class="bg-red-600 text-white py-1 px-2 rounded hover:bg-red-700">Suspend</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>