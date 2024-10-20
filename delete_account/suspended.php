<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Fetch user details and suspension reason
$stmt = $pdo->prepare('SELECT username, is_banned, ban_reason FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if the user is actually banned
if (!$user['is_banned']) {
    header('Location: ../app/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended - BloxAuth</title>
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/scripts/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/dist/output.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
</head>
<body class="flex flex-col min-h-screen">
    <header class="bg-[#0f0f17] shadow-md">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="../" class="text-2xl font-bold text-blue-500">BloxAuth</a>
                <a href="../app/dashboard.php" class="text-white font-medium text-sm px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition duration-200">Dashboard</a>
            </div>
        </nav>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-[#0f0f17] rounded-xl p-8 shadow-lg">
            <h1 class="text-3xl md:text-4xl font-bold text-center mb-6">
                Account <span class="text-red-500">Suspended</span>
            </h1>
            
            <div class="mb-8 p-4 bg-red-900 bg-opacity-20 border border-red-700 rounded-lg">
                <p class="text-lg text-center">
                    Hello <span class="font-semibold"><?= htmlspecialchars($user['username']) ?></span>, your account has been suspended.
                </p>
            </div>
            
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold mb-2">Suspension Details</h2>
                    <p class="text-gray-300">
                        <strong>Reason:</strong> <?= htmlspecialchars($user['ban_reason']) ?>
                    </p>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-2">Why can't I delete my account?</h2>
                    <p class="text-gray-300">
                        For security reasons, account deletion is not allowed during a suspension period. This helps us:
                    </p>
                    <ul class="list-disc list-inside text-gray-300 mt-2 space-y-1">
                        <li>Preserve important information for ongoing investigations</li>
                        <li>Prevent potential abuse of the deletion process</li>
                        <li>Ensure fair resolution of any pending issues</li>
                    </ul>
                    <p class="text-yellow-400 mt-2 font-semibold">
                        We temporarily prevent account deletion to maintain data integrity and assist in resolving the suspension issue.
                    </p>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-2">What should I do next?</h2>
                    <p class="text-gray-300">
                        To resolve this issue and potentially reinstate your account:
                    </p>
                    <ol class="list-decimal list-inside text-gray-300 mt-2 space-y-1">
                        <li><a href="../terms-of-service.php" class="text-blue-400 hover:underline">Review our terms of service</a></li>
                        <li>Contact our support team for more information</li>
                        <li>Provide any requested information or clarification</li>
                        <li>Wait for our team to review your case</li>
                    </ol>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <a href="../support/contact.php" class="inline-block text-white font-medium text-lg px-6 py-3 bg-blue-600 rounded-lg hover:bg-blue-700 transition duration-200">
                    Contact Support
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-[#0f0f17] text-white py-6 px-4 mt-auto">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 BloxAuth. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>