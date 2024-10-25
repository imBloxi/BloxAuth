<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT username, email, credits, subscription_tier, subscription_end_date, app_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();

// Fetch recent licenses
$recent_licenses = fetch_recent_licenses($pdo, $user_id, 5);

// Fetch usage statistics
$usage_stats = fetch_usage_stats($pdo, $user_id);

// Fetch API key
$api_key = fetch_api_key($pdo, $user_id);

// Fetch recent user activities
$recent_activities = fetch_recent_user_activities($pdo, $user_id, 5);
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="h-full">
    <div class="min-h-full">
        <?php include '../includes/navbar.php'; ?>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($user_info['username']); ?>!</h1>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- App Overview -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">App Overview</h2>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="flex items-center">
                            <i class="fas fa-rocket text-3xl text-indigo-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">App Name</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user_info['app_name'] ?? 'Not set'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-crown text-3xl text-yellow-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Subscription</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user_info['subscription_tier'] ?? 'No active subscription'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-coins text-3xl text-green-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Credits</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo number_format($user_info['credits'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Usage Statistics</h2>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex items-center">
                            <i class="fas fa-key text-3xl text-blue-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Licenses</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo number_format($usage_stats['total_licenses']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-3xl text-green-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Licenses</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo number_format($usage_stats['active_licenses']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-3xl text-purple-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Uses</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo number_format($usage_stats['total_uses']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-hourglass-half text-3xl text-red-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Expiring Soon</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo number_format($usage_stats['expiring_soon']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Licenses -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Recent Licenses</h2>
                    <div class="mt-2 flex flex-col">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($recent_licenses as $license): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['key']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($license['whitelist_type']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('Y-m-d', strtotime($license['created_at'])); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Active
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Key -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">API Key</h2>
                    <div class="mt-2 flex items-center justify-between">
                        <input type="text" value="<?php echo htmlspecialchars($api_key); ?>" readonly class="flex-grow mr-4 p-2 border rounded">
                        <button onclick="copyApiKey()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-copy mr-2"></i> Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Recent Activities</h2>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($recent_activities as $activity): ?>
                        <li class="py-4">
                            <div class="flex space-x-3">
                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium"><?php echo htmlspecialchars($activity['action']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($activity['timestamp'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
    function copyApiKey() {
        var apiKeyInput = document.querySelector('input[readonly]');
        apiKeyInput.select();
        document.execCommand('copy');
        alert('API Key copied to clipboard!');
    }
    </script>
</body>
</html>
