<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if the user is banned
$stmt = $pdo->prepare('SELECT is_banned, username FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['is_banned']) {
    header('Location: suspended.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $reason = $_POST['delete_reason'] ?? 'Not specified';
    $stmt = $pdo->prepare('UPDATE users SET is_deleted = 1, delete_reason = ? WHERE id = ?');
    $stmt->execute([$reason, $_SESSION['user_id']]);
    session_destroy();
    header('Location: ../auth/login.php?deleted=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deletion - BloxAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
</head>
<body class="font-sans text-gray-100 antialiased" x-data="{ showConfirmModal: false, showToSModal: false, deleteReason: '', confirmPassword: '' }">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-red-600 p-4">
                <h2 class="text-3xl font-bold text-center text-white">
                    Account Deletion
                </h2>
            </div>
            <div class="p-6">
                <div class="mb-6 text-sm text-gray-700">
                    <p class="mb-4">We're sorry to see you go, <span class="font-semibold"><?= htmlspecialchars($user['username']) ?></span>. Before you proceed, please consider the following:</p>
                    <ul class="list-disc list-inside space-y-2">
                        <li>All your personal data and settings will be permanently deleted.</li>
                        <li>Your activity history will be removed.</li>
                        <li>Any subscriptions or recurring payments will be cancelled.</li>
                        <li>You will lose access to any purchased content or services.</li>
                        <li>This action is irreversible.</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2 text-gray-800">Alternative Options:</h3>
                    <ul class="list-disc list-inside text-sm text-gray-700">
                        <li>Temporarily deactivate your account</li>
                        <li>Update your notification settings</li>
                        <li><a href="../support/contact.php" class="text-blue-600 hover:underline">Contact support for assistance</a></li>
                    </ul>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <a href="../app/dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                        Cancel
                    </a>
                    <button @click="showToSModal = true" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div x-show="showToSModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Terms of Service</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Please review our Terms of Service before proceeding with account deletion.
                    </p>
                    <div class="mt-2 overflow-y-auto h-40 border p-2 text-sm text-gray-600">
                        <!-- Insert your Terms of Service content here -->
                        <p>Welcome to BloxAuth. By using our services, you agree to comply with and be bound by the following terms and conditions. Please read them carefully.

1. Acceptance of Terms
By accessing or using BloxAuth, you agree to be bound by these Terms of Service and our Privacy Policy. If you do not agree with any part of these terms, you must not use our services.

2. Account Termination
BloxAuth reserves the right to suspend or terminate your account at any time, for any reason, including but not limited to:

Violation of these Terms of Service
Engaging in fraudulent or illegal activities
Failing to provide accurate information during registration
Inactivity for an extended period
Any conduct that we believe is harmful to other users or our business
2.1 User Termination: You may terminate your account at any time by contacting our support team. Upon termination, all of your data will be removed from our systems in accordance with our data retention policy.

2.2 Notice of Termination: In the event of account termination, you will receive an email notification detailing the reason for termination and any steps you may take to address the issue (if applicable).

3. Limitation of Liability
To the fullest extent permitted by law, BloxAuth, its affiliates, officers, directors, employees, agents, and licensors will not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or other intangible losses, arising from:

Your use or inability to use our services
Any unauthorized access to or use of our servers and/or any personal information stored therein
Any interruption or cessation of transmission to or from our services
Any bugs, viruses, or the like that may be transmitted to or through our services by any third party
Any errors or omissions in any content or for any loss or damage of any kind incurred as a result of your use of any content posted, emailed, transmitted, or otherwise made available via our services
4. Indemnification
You agree to indemnify, defend, and hold harmless BloxAuth and its affiliates, officers, directors, employees, agents, and licensors from and against any claims, liabilities, damages, losses, costs, or expenses (including reasonable attorneys’ fees) arising out of or in any way connected with:

Your access to or use of the services
Your violation of these Terms of Service
Your violation of any rights of another party, including any intellectual property or privacy rights
Your violation of any applicable law or regulation</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button @click="showToSModal = false; showConfirmModal = true" class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md w-24 mr-2">
                        Agree
                    </button>
                    <button @click="showToSModal = false" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Confirm Account Deletion</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        This action cannot be undone. Please provide a reason for deleting your account and confirm your password:
                    </p>
                    <textarea x-model="deleteReason" class="mt-2 w-full p-2 border rounded text-gray-700" rows="3" placeholder="Reason for deletion (optional)"></textarea>
                    <input type="password" x-model="confirmPassword" class="mt-2 w-full p-2 border rounded text-gray-700" placeholder="Confirm your password">
                </div>
                <div class="mt-4">
                    <form id="deleteForm" method="POST" @submit.prevent="confirmDeletion">
                        <input type="hidden" name="confirm_delete" value="1">
                        <input type="hidden" name="delete_reason" x-bind:value="deleteReason">
                        <button type="button" @click="showConfirmModal = false" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 mr-2">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDeletion(event) {
            if (!confirm("Are you absolutely sure you want to delete your account? This action cannot be undone.")) {
                event.preventDefault();
            }
        }

        document.getElementById('deleteForm').addEventListener('submit', confirmDeletion);
    </script>
</body>
</html>
