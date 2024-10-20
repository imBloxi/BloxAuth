<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Check if the user has set up their app
$stmt = $pdo->prepare('SELECT app_name FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (empty($user['app_name'])) {
    header('Location: setup_app.php');
    exit();
}

$recent_licenses = fetch_recent_licenses($pdo, $user_id);
$usage_stats = fetch_usage_stats($pdo, $user_id);
$anomaly_alerts = fetch_anomaly_alerts($pdo, $user_id);
$user_activities = fetch_user_activities($pdo, $user_id);

// Correct the SQL query to use the correct column names
$usage_stats_query = "SELECT 
    COUNT(*) as total_licenses,
    SUM(CASE WHEN is_banned = 0 THEN 1 ELSE 0 END) as active_licenses,
    SUM(current_uses) as total_uses,
    SUM(CASE WHEN valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as expiring_soon
FROM licenses_new
WHERE user_id = :user_id";

$stmt = $pdo->prepare($usage_stats_query);
$stmt->execute(['user_id' => $user_id]);
$usage_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure all keys exist, even if the query returns no results
$usage_stats = array_merge([
    'total_licenses' => 0,
    'active_licenses' => 0,
    'total_uses' => 0,
    'expiring_soon' => 0
], $usage_stats ?: []);

?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BloxAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
</head>
<body class="h-full">
    <div class="min-h-full">
        <?php include '../includes/navbar.php'; ?>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">License Management Dashboard</h1>
            </div>
        </header>

        <main>
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <!-- Stats overview -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Total Licenses
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            <?php echo $usage_stats['total_licenses']; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Active Licenses
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            <?php echo $usage_stats['active_licenses']; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Total Uses
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            <?php echo $usage_stats['total_uses']; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Expiring Soon
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            <?php echo $usage_stats['expiring_soon']; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and recent activity -->
                <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <!-- License activity chart -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <h3 class="text-lg font-medium text-gray-900">License Activity</h3>
                            <canvas id="licenseActivityChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent license activity -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <h3 class="text-lg font-medium text-gray-900">Recent License Activity</h3>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($recent_licenses as $license): ?>
                                    <li class="py-4">
                                        <div class="flex space-x-3">
                                            <div class="flex-1 space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-sm font-medium"><?php echo htmlspecialchars($license['key'] ?? 'N/A'); ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo isset($license['created_at']) ? date('M j, Y', strtotime($license['created_at'])) : 'N/A'; ?></p>
                                                </div>
                                                <p class="text-sm text-gray-500">Status: <?php echo ucfirst($license['status'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Anomaly alerts and user activity -->
                <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <!-- Anomaly alerts -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <h3 class="text-lg font-medium text-gray-900">Anomaly Alerts</h3>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($anomaly_alerts as $alert): ?>
                                    <li class="py-4">
                                        <div class="flex space-x-3">
                                            <div class="flex-1 space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-sm font-medium"><?php echo htmlspecialchars($alert['type'] ?? 'N/A'); ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo isset($alert['created_at']) ? date('M j, Y', strtotime($alert['created_at'])) : 'N/A'; ?></p>
                                                </div>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($alert['description'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- User activity -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($user_activities as $activity): ?>
                                    <li class="py-4">
                                        <div class="flex space-x-3">
                                            <div class="flex-1 space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-sm font-medium"><?php echo htmlspecialchars($activity['action'] ?? 'N/A'); ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo isset($activity['timestamp']) ? date('M j, Y', strtotime($activity['timestamp'])) : 'N/A'; ?></p>
                                                </div>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($activity['details'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Chart.js initialization for license activity chart
        var ctx = document.getElementById('licenseActivityChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'License Uses',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
