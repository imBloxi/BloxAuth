<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize settings if not set
$settings_defaults = [
    'api_logging_enabled' => false,
    'sellix_auto_fulfill' => false,
    'notify_on_new_license' => true,
    'notify_on_license_expiry' => true,
    'log_login_activity' => true,
    'log_license_usage' => true,
    'activity_retention' => 30,
];

foreach ($settings_defaults as $key => $default) {
    if (!isset($user[$key])) {
        $user[$key] = $default;
    }
}

// Fetch API key
$api_key = fetch_api_key($pdo, $user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update user settings
    $stmt = $pdo->prepare("UPDATE users SET 
        theme = ?,
        timezone = ?,
        discord_webhook_url = ?,
        api_rate_limit = ?,
        api_logging_enabled = ?,
        sellix_api_key = ?,
        sellix_store_id = ?,
        sellix_webhook_secret = ?,
        sellix_auto_fulfill = ?,
        allow_email_notifications = ?,
        allow_push_notifications = ?,
        notify_on_new_license = ?,
        notify_on_license_expiry = ?,
        log_login_activity = ?,
        log_license_usage = ?,
        activity_retention = ?
        WHERE id = ?");
    
    $stmt->execute([
        $_POST['theme'],
        $_POST['timezone'],
        $_POST['discord_webhook_url'],
        $_POST['api_rate_limit'],
        isset($_POST['api_logging_enabled']) ? 1 : 0,
        $_POST['sellix_api_key'],
        $_POST['sellix_store_id'],
        $_POST['sellix_webhook_secret'],
        isset($_POST['sellix_auto_fulfill']) ? 1 : 0,
        isset($_POST['allow_email_notifications']) ? 1 : 0,
        isset($_POST['allow_push_notifications']) ? 1 : 0,
        isset($_POST['notify_on_new_license']) ? 1 : 0,
        isset($_POST['notify_on_license_expiry']) ? 1 : 0,
        isset($_POST['log_login_activity']) ? 1 : 0,
        isset($_POST['log_license_usage']) ? 1 : 0,
        $_POST['activity_retention'],
        $user_id
    ]);

    $_SESSION['message'] = "Settings updated successfully.";
    header('Location: settings.php');
    exit();
}

// Fetch subscription levels
$stmt = $pdo->prepare("SELECT * FROM subscription_levels");
$stmt->execute();
$subscription_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body class="bg-gray-100" x-data="{ activeTab: 'general' }">
    <div class="min-h-screen">
        <?php include '../includes/navbar.php'; ?>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">User Settings</h1>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 animate__animated animate__fadeIn" role="alert">
                    <p><?= $_SESSION['message'] ?></p>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button @click="activeTab = 'general'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'general', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'general'}" class="w-1/5 py-4 px-1 text-center border-b-2 font-medium text-sm">
                            <i class="fas fa-cog mr-2"></i> General
                        </button>
                        <button @click="activeTab = 'api'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'api', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'api'}" class="w-1/5 py-4 px-1 text-center border-b-2 font-medium text-sm">
                            <i class="fas fa-key mr-2"></i> API
                        </button>
                        <button @click="activeTab = 'sellix'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'sellix', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'sellix'}" class="w-1/5 py-4 px-1 text-center border-b-2 font-medium text-sm">
                            <i class="fas fa-shopping-cart mr-2"></i> Sellix
                        </button>
                        <button @click="activeTab = 'notifications'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'notifications', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'notifications'}" class="w-1/5 py-4 px-1 text-center border-b-2 font-medium text-sm">
                            <i class="fas fa-bell mr-2"></i> Notifications
                        </button>
                        <button @click="activeTab = 'activity'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'activity', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'activity'}" class="w-1/5 py-4 px-1 text-center border-b-2 font-medium text-sm">
                            <i class="fas fa-chart-line mr-2"></i> Activity
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <form action="settings.php" method="post">
                        <div x-show="activeTab === 'general'">
                            <h2 class="text-2xl font-semibold mb-4">General Settings</h2>
                            
                            <div class="mb-4">
                                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                            <div class="mb-4">
                                <label for="theme" class="block text-sm font-medium text-gray-700">Theme</label>
                                <select id="theme" name="theme" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                                    <option value="dark" <?= $user['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                                    <option value="system" <?= $user['theme'] === 'system' ? 'selected' : '' ?>>System</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                <select id="timezone" name="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <?php
                                    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                                    foreach ($timezones as $tz) {
                                        echo "<option value=\"$tz\"" . ($user['timezone'] === $tz ? ' selected' : '') . ">$tz</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div x-show="activeTab === 'api'">
                            <h2 class="text-2xl font-semibold mb-4">API Settings</h2>
                            
                            <div class="mb-4">
                                <label for="api_key" class="block text-sm font-medium text-gray-700">API Key</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" id="api_key" value="<?= htmlspecialchars($api_key) ?>" readonly class="flex-grow rounded-l-md border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <button type="button" onclick="copyApiKey()" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        Copy
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="discord_webhook_url" class="block text-sm font-medium text-gray-700">Discord Webhook URL</label>
                                <input type="url" id="discord_webhook_url" name="discord_webhook_url" value="<?= htmlspecialchars($user['discord_webhook_url']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                            <div class="mb-4">
                                <label for="api_rate_limit" class="block text-sm font-medium text-gray-700">API Rate Limit (requests per minute)</label>
                                <input type="number" id="api_rate_limit" name="api_rate_limit" value="<?= htmlspecialchars($user['api_rate_limit']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="api_logging_enabled" <?= $user['api_logging_enabled'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Enable API Usage Logging</span>
                                </label>
                            </div>
                        </div>
                        
                        <div x-show="activeTab === 'sellix'">
                            <h2 class="text-2xl font-semibold mb-4">Sellix Integration</h2>
                            
                            <div class="mb-4">
                                <label for="sellix_api_key" class="block text-sm font-medium text-gray-700">Sellix API Key</label>
                                <input type="text" id="sellix_api_key" name="sellix_api_key" value="<?= htmlspecialchars($user['sellix_api_key']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            
                            <div class="mb-4">
                                <label for="sellix_store_id" class="block text-sm font-medium text-gray-700">Sellix Store ID</label>
                                <input type="text" id="sellix_store_id" name="sellix_store_id" value="<?= htmlspecialchars($user['sellix_store_id']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                            
                            <div class="mb-4">
                                <label for="sellix_webhook_secret" class="block text-sm font-medium text-gray-700">Sellix Webhook Secret</label>
                                <input type="text" id="sellix_webhook_secret" name="sellix_webhook_secret" value="<?= htmlspecialchars($user['sellix_webhook_secret']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="sellix_auto_fulfill" <?= $user['sellix_auto_fulfill'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Enable Automatic Order Fulfillment</span>
                                </label>
                            </div>
                        </div>
                        
                        <div x-show="activeTab === 'notifications'">
                            <h2 class="text-2xl font-semibold mb-4">Notification Settings</h2>
                            
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="allow_email_notifications" <?= $user['allow_email_notifications'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Allow Email Notifications</span>
                                </label>
                            </div>
                            
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="allow_push_notifications" <?= $user['allow_push_notifications'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Allow Push Notifications</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_on_new_license" <?= $user['notify_on_new_license'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Notify on New License Creation</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_on_license_expiry" <?= $user['notify_on_license_expiry'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Notify on License Expiry</span>
                                </label>
                            </div>
                        </div>
                        
                        <div x-show="activeTab === 'activity'">
                            <h2 class="text-2xl font-semibold mb-4">Activity Settings</h2>
                            
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="log_login_activity" <?= $user['log_login_activity'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Log Login Activity</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="log_license_usage" <?= $user['log_license_usage'] ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">Log License Usage</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label for="activity_retention" class="block text-sm font-medium text-gray-700">Activity Log Retention (days)</label>
                                <input type="number" id="activity_retention" name="activity_retention" value="<?= htmlspecialchars($user['activity_retention']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-between">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save Changes
                            </button>
                            <a href="../delete_account" class="text-sm text-red-600 hover:text-red-500">Delete Account</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyApiKey() {
            const apiKeyInput = document.getElementById('api_key');
            apiKeyInput.select();
            document.execCommand('copy');
            alert('API Key copied to clipboard');
        }
    </script>
</body>
</html>
