<?php
require_once '../includes/config.php';

if (!is_user_logged_in()) {
    redirect('/auth/login.php');
}

$stmt = $pdo->prepare('SELECT username, is_banned, ban_reason FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/auth/logout.php');
}
if (!$user['is_banned']) {
    redirect('/app/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_account'])) {
        // Soft delete: licenses and logs reference the user row.
        $pdo->prepare('UPDATE users SET is_deleted = 1, delete_reason = ? WHERE id = ?')->execute(['Deleted while banned', $_SESSION['user_id']]);
    }
    if (isset($_POST['delete_account']) || isset($_POST['logout'])) {
        redirect('/auth/logout.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banned</title>
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/scripts/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/dist/output.css">
</head>
<body>
    <header>
        <nav class="border-gray-200 px-4 lg:px-6 py-2.5 mb-14">
            <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl">
                <a href="../" class="flex items-center">
                    <!-- Optional logo or brand name -->
                </a>
                <div class="flex items-center lg:order-2">
                    <span class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2">
                        Banned User: <?php echo e($user['username']); ?>
                    </span>
                    <form action="" method="post">
                        <button type="submit" name="logout" class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-red-600 rounded-lg hover:opacity-80 transition duration-200">Log Out</button>
                        <a href="../delete_account/"class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-red-600 rounded-lg hover:opacity-80 transition duration-200">Delete Account</a>
                    </form>
                </div>
            </div>
        </nav>
    </header>
    
    <main>
        <h2 class="mb-7 md:mb-12 text-3xl md:text-6xl font-bold font-heading tracking-px-n leading-tight text-center">
            Your account has been <span class="text-transparent bg-clip-text bg-gradient-to-r to-red-600 from-pink-400 inline-block">banned</span>
        </h2>
        
        <section class="py-8 max-w-screen-xl px-4 mx-auto lg:py-16">
            <div class="bg-[#0f0f17] rounded-xl p-6 text-center">
                <h3 class="text-xl font-semibold mb-4">Ban Reason</h3>
                <p class="text-gray-400 mb-4"><?php echo e($user['ban_reason']); ?></p>
            </div>
        </section>
    </main>

    <footer class="bg-[#0f0f17] text-white py-6 px-4 mt-16">
        <div class="max-w-screen-xl mx-auto text-center">
            <p>&copy; 2024 BloxAuth. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
