<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Check if the user has already set up their app
$stmt = $pdo->prepare('SELECT app_name, seller_id FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!empty($user['app_name'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $app_name = trim($_POST['app_name']);
    $app_description = trim($_POST['app_description']);
    $app_category = $_POST['app_category'];

    if (empty($app_name)) {
        $error_message = "App Name is required.";
    } else {
        try {
            $pdo->beginTransaction();

            $app_name = htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8');
            $app_description = htmlspecialchars($app_description, ENT_QUOTES, 'UTF-8');
            $seller_id = bin2hex(random_bytes(8));

            $stmt = $pdo->prepare('UPDATE users SET app_name = ?, app_description = ?, app_category = ?, seller_id = ? WHERE id = ?');
            $stmt->execute([$app_name, $app_description, $app_category, $seller_id, $user_id]);

            // Generate initial API key
            $api_key = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare('INSERT INTO api_keys (user_id, api_key) VALUES (?, ?)');
            $stmt->execute([$user_id, $api_key]);

            $pdo->commit();

            $success_message = "App setup completed successfully!";
            header("Refresh: 2; URL=dashboard.php");
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Setup failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your App - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
</head>
<body class="h-full">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Set Up Your App
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Let's get your app ready for success!
                </p>
            </div>
            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" action="setup_app.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="app_name" class="sr-only">App Name</label>
                        <input id="app_name" name="app_name" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="App Name">
                    </div>
                    <div>
                        <label for="app_description" class="sr-only">App Description</label>
                        <textarea id="app_description" name="app_description" rows="3" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="App Description"></textarea>
                    </div>
                    <div>
                        <label for="app_category" class="sr-only">App Category</label>
                        <select id="app_category" name="app_category" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                            <option value="">Select a category</option>
                            <option value="productivity">Productivity</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="education">Education</option>
                            <option value="lifestyle">Lifestyle</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Set Up My App
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>