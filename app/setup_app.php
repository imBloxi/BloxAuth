<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the user has already set up their app
$stmt = $pdo->prepare('SELECT app_name FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!empty($user['app_name'])) {
    header('Location: ../app/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $app_name = trim($_POST['app_name']);

    if (empty($app_name)) {
        $error = "App Name is required.";
    } else {
        $app_name = htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8');
        $seller_id = bin2hex(random_bytes(8));

        // Only update app_name and seller_id in the database
        $stmt = $pdo->prepare('UPDATE users SET app_name = ?, seller_id = ? WHERE id = ?');
        $stmt->execute([$app_name, $seller_id, $user_id]);

        header('Location: ../app/index.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your App | Professional Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div x-data="{ isOpen: true }" class="relative flex justify-center">
        <button @click="isOpen = true" class="px-6 py-2 mx-auto tracking-wide text-white capitalize transition-colors duration-300 transform bg-blue-600 rounded-md hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-80">
            Set Up Your App
        </button>

        <div x-show="isOpen" 
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            class="fixed inset-0 z-10 overflow-y-auto" 
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl rtl:text-right dark:bg-gray-900 sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
                    <div>
                        <div class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>

                        <div class="mt-2 text-center">
                            <h3 class="text-lg font-medium leading-6 text-gray-800 capitalize dark:text-white" id="modal-title">Set Up Your App</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Let's get your app ready for success! Fill in the details below to create your app.
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="mt-3 p-2 bg-red-100 text-red-700 rounded">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form class="mt-4" action="setup_app.php" method="POST">
                        <div class="space-y-3">
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">App Name</label>
                                <input id="app_name" name="app_name" type="text" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="app_description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">App Description</label>
                                <textarea id="app_description" name="app_description" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div>
                                <label for="app_category" class="block text-sm font-medium text-gray-700 dark:text-gray-200">App Category</label>
                                <select id="app_category" name="app_category" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select a category (Optional)</option>
                                    <option value="productivity">Productivity</option>
                                    <option value="entertainment">Entertainment</option>
                                    <option value="education">Education</option>
                                    <option value="lifestyle">Lifestyle</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-5 sm:flex sm:items-center sm:justify-between">
                            <a href="#" class="text-sm text-blue-500 hover:underline">Need help?</a>

                            <div class="sm:flex sm:items-center ">
                                <button @click="isOpen = false" type="button" class="w-full px-4 py-2 mt-2 text-sm font-medium tracking-wide text-gray-700 capitalize transition-colors duration-300 transform border border-gray-200 rounded-md sm:mt-0 sm:w-auto sm:mx-2 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-800 hover:bg-gray-100 focus:outline-none focus:ring focus:ring-gray-300 focus:ring-opacity-40">
                                    Cancel
                                </button>

                                <button type="submit" class="w-full px-4 py-2 mt-2 text-sm font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-blue-600 rounded-md sm:w-auto sm:mt-0 hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-40">
                                    Create App
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>