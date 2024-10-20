<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$api_key = fetch_api_key($pdo, $user_id);

// Fetch user info
$stmt = $pdo->prepare("SELECT credits, two_fa_enabled, discord_username, discord_webhook_url FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Function to create a custom tier
function create_custom_tier($pdo, $user_id, $tier_name, $tier_benefits) {
    $stmt = $pdo->prepare("INSERT INTO custom_tiers (user_id, tier_name, tier_benefits) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $tier_name, $tier_benefits]);
}

// Function to fetch custom tiers
function fetch_custom_tiers($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM custom_tiers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to send Discord webhook
function send_discord_webhook($webhook_url, $license_key, $tier) {
    $data = [
        "content" => "New license created!",
        "embeds" => [
            [
                "title" => "License Details",
                "fields" => [
                    ["name" => "License Key", "value" => $license_key, "inline" => true],
                    ["name" => "Tier", "value" => $tier, "inline" => true]
                ],
                "color" => 5814783
            ]
        ]
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = @file_get_contents($webhook_url, false, $context);
    return $result !== false;
}

// Function to renew a license
function renew_license($pdo, $user_id, $license_id, $new_valid_until) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET valid_until = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_valid_until, $license_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "License renewed successfully.";
    } else {
        $_SESSION['error'] = "Failed to renew license. Please ensure the license exists and belongs to you.";
    }
}

// Function to log user actions
function log_user_action($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $action]);
}

// Handle license actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_license'])) {
        $license_data = [
            'key' => generate_unique_license_key($pdo),
            'whitelist_id' => $_POST['whitelist_id'] ?? '',
            'whitelist_type' => $_POST['whitelist_type'] ?? 'user',
            'description' => $_POST['description'] ?? '',
            'valid_until' => $_POST['valid_until'] ?? null,
            'roblox_user_id' => $_POST['roblox_user_id'] ?? null,
            'max_uses' => $_POST['max_uses'] ?? null,
            'is_transferable' => isset($_POST['is_transferable']) ? 1 : 0,
            'custom_tier' => $_POST['custom_tier'] ?? null
        ];

        if (save_license($pdo, $user_id, $license_data)) {
            // Send Discord webhook
            $discord_webhook_url = $user_info['discord_webhook_url'];
            if ($discord_webhook_url) {
                send_discord_webhook($discord_webhook_url, $license_data['key'], $license_data['custom_tier']);
            }
            $_SESSION['message'] = "License created and saved successfully.";
        } else {
            $_SESSION['error'] = "Failed to save license.";
        }
    } elseif (isset($_POST['create_custom_tier'])) {
        $tier_name = $_POST['tier_name'];
        $tier_benefits = $_POST['tier_benefits'];
        if (create_custom_tier($pdo, $user_id, $tier_name, $tier_benefits)) {
            $_SESSION['message'] = "Custom tier created successfully.";
        } else {
            $_SESSION['error'] = "Failed to create custom tier.";
        }
    } elseif (isset($_POST['delete_license'])) {
        delete_license($pdo, $user_id, $_POST['license_id']);
    } elseif (isset($_POST['ban_license'])) {
        ban_license($pdo, $user_id, $_POST['license_id'], $_POST['ban_reason']);
    } elseif (isset($_POST['bulk_action'])) {
        handle_bulk_action($pdo, $user_id, $_POST['license_ids'], $_POST['bulk_action']);
    } elseif (isset($_POST['renew_license'])) {
        $license_id = $_POST['license_id'];
        $new_valid_until = $_POST['new_valid_until'];
        renew_license($pdo, $user_id, $license_id, $new_valid_until);
        log_user_action($pdo, $user_id, "Renewed license ID: $license_id");
    }
}

// Fetch licenses and custom tiers
$licenses = fetch_licenses($pdo, $user_id, $_GET);
$custom_tiers = fetch_custom_tiers($pdo, $user_id);

// Function to generate a unique license key
function generate_unique_license_key($pdo) {
    do {
        $license_key = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM licenses_new WHERE `key` = ?");
        $stmt->execute([$license_key]);
        $count = $stmt->fetchColumn();
    } while ($count > 0);

    return $license_key;
}

// Function to delete a license
function delete_license($pdo, $user_id, $license_id) {
    $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$license_id, $user_id]);

    $_SESSION['message'] = "License deleted successfully.";
}

// Function to ban a license
function ban_license($pdo, $user_id, $license_id, $ban_reason) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET is_banned = 1, ban_reason = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$ban_reason, $license_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "License banned successfully.";
    } else {
        $_SESSION['error'] = "Failed to ban license. Please ensure the license exists and belongs to you.";
    }
}

// Function to handle bulk actions
function handle_bulk_action($pdo, $user_id, $license_ids, $action) {
    foreach ($license_ids as $license_id) {
        if ($action === 'delete') {
            delete_license($pdo, $user_id, $license_id);
        } elseif ($action === 'ban') {
            ban_license($pdo, $user_id, $license_id, 'Bulk ban action');
        }
    }

    $_SESSION['message'] = "Bulk action completed successfully.";
}

// Function to fetch license logs
function getLicenseLogs($license_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM license_logs WHERE license_id = ? ORDER BY timestamp DESC LIMIT 10");
    $stmt->execute([$license_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Management - BloxAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="h-full" x-data="{ showCreateModal: false, showLogsModal: false, showBanModal: false, showCustomTierModal: false, currentLogs: [], currentLicenseId: null, licenseType: 'standard', idType: 'user' }">
    <div class="min-h-full">
        <?php include '../includes/navbar.php'; ?>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">License Management</h1>
            </div>
        </header>

        <main>
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                        <p><?= $_SESSION['message'] ?></p>
                        <?php unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- API Key Section -->
                <div class="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200 mb-6">
                    <div class="px-4 py-5 sm:px-6">
                        <h2 class="text-lg font-medium text-gray-900">API Key</h2>
                        <p class="mt-1 text-sm text-gray-500">Use this key to authenticate your API requests.</p>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <input type="text" id="apiKey" value="<?= htmlspecialchars($api_key) ?>" readonly class="flex-grow bg-gray-100 p-2 rounded mr-2">
                            <button onclick="copyApiKey()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                                <i class="fas fa-copy mr-2"></i>Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- License Management Section -->
                <div class="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Your Licenses</h2>
                        <button @click="showCreateModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                            <i class="fas fa-plus mr-2"></i>Create New License
                        </button>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <!-- Advanced search and filter form -->
                        <form action="" method="get" class="mb-4">
                            <!-- Add search and filter inputs here -->
                        </form>

                        <!-- Licenses table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Key</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($licenses as $license): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($license['key']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($license['whitelist_type']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($license['description']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= $license['valid_until'] ? date('Y-m-d', strtotime($license['valid_until'])) : 'N/A' ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <button @click="showLogsModal = true; currentLogs = <?= htmlspecialchars(json_encode(getLicenseLogs($license['id'], $pdo))) ?>" class="text-blue-600 hover:text-blue-900">Logs</button>
                                                <button @click="showBanModal = true; currentLicenseId = <?= $license['id'] ?>" class="text-red-600 hover:text-red-900">Ban</button>
                                                <form action="" method="post" class="inline">
                                                    <input type="hidden" name="license_id" value="<?= $license['id'] ?>">
                                                    <button type="submit" name="delete_license" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk actions form -->
                        <form action="" method="post" class="mt-4">
                            <!-- Add bulk action inputs and button here -->
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modify the Create License Modal -->
    <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl w-full">
            <h2 class="text-2xl font-semibold mb-4 text-indigo-600">Create New License</h2>
            <form action="license.php" method="post">
                <div class="mb-4">
                    <label for="license_type" class="block text-sm font-medium text-gray-700">License Type</label>
                    <input type="text" id="license_type" name="license_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Enter custom license type">
                </div>

                <!-- Adaptive User ID Section -->
                <div class="mb-4">
                    <label for="id_type" class="block text-sm font-medium text-gray-700">ID Type</label>
                    <select id="id_type" name="id_type" x-model="idType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="user">User</option>
                        <option value="group">Group</option>
                        <option value="organization">Organization</option>
                    </select>
                </div>

                <!-- Adaptive ID Input Field -->
                <div class="mb-4">
                    <label :for="idType + '_id'" class="block text-sm font-medium text-gray-700" x-text="idType.charAt(0).toUpperCase() + idType.slice(1) + ' ID'"></label>
                    <input :type="idType === 'user' ? 'number' : 'text'" :id="idType + '_id'" :name="idType + '_id'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :placeholder="'Enter ' + idType + ' ID'">
                </div>

                <!-- Additional API Inputs for Licenses -->
                <div x-show="licenseType === 'premium' || licenseType === 'enterprise'" class="mb-4">
                    <label for="api_key" class="block text-sm font-medium text-gray-700">API Key</label>
                    <input type="text" id="api_key" name="api_key" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Enter API key">
                </div>

                <div x-show="licenseType === 'premium' || licenseType === 'enterprise'" class="mb-4">
                    <label for="webhook_url" class="block text-sm font-medium text-gray-700">Webhook URL</label>
                    <input type="url" id="webhook_url" name="webhook_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="https://example.com/webhook">
                </div>

                <div x-show="licenseType === 'enterprise'" class="mb-4">
                    <label for="custom_domain" class="block text-sm font-medium text-gray-700">Custom Domain</label>
                    <input type="text" id="custom_domain" name="custom_domain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="api.yourdomain.com">
                </div>

                <div class="mb-4">
                    <label for="whitelist_type" class="block text-sm font-medium text-gray-700">Whitelist Type</label>
                    <select id="whitelist_type" name="whitelist_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="user">User</option>
                        <option value="group">Group</option>
                        <option value="place">Place</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="valid_until" class="block text-sm font-medium text-gray-700">Valid Until</label>
                        <input type="datetime-local" id="valid_until" name="valid_until" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="max_uses" class="block text-sm font-medium text-gray-700">Max Uses</label>
                        <input type="number" id="max_uses" name="max_uses" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Leave blank for unlimited">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="roblox_user_id" class="block text-sm font-medium text-gray-700">Roblox User ID</label>
                    <input type="number" id="roblox_user_id" name="roblox_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_transferable" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-600">Transferable</span>
                    </label>
                </div>
                <div x-show="licenseType === 'premium' || licenseType === 'enterprise'" class="mb-4">
                    <label for="custom_field" class="block text-sm font-medium text-gray-700">Custom Field</label>
                    <input type="text" id="custom_field" name="custom_field" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div x-show="licenseType === 'enterprise'" class="mb-4">
                    <label for="api_rate_limit" class="block text-sm font-medium text-gray-700">API Rate Limit (requests per minute)</label>
                    <input type="number" id="api_rate_limit" name="api_rate_limit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="custom_tier" class="block text-sm font-medium text-gray-700">Custom Tier</label>
                    <select id="custom_tier" name="custom_tier" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">Select a tier</option>
                        <?php foreach ($custom_tiers as $tier): ?>
                            <option value="<?= htmlspecialchars($tier['tier_name']) ?>"><?= htmlspecialchars($tier['tier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-between items-center">
                    <button type="submit" name="create_license" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">Create License</button>
                    <button type="button" @click="showCustomTierModal = true" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition">
                        <i class="fas fa-plus mr-2"></i>Add Custom Tier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Tier Modal -->
    <div x-show="showCustomTierModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <h2 class="text-2xl font-semibold mb-4 text-indigo-600">Create Custom Tier</h2>
            <form action="license.php" method="post">
                <div class="mb-4">
                    <label for="tier_name" class="block text-sm font-medium text-gray-700">Tier Name</label>
                    <input type="text" id="tier_name" name="tier_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for="tier_benefits" class="block text-sm font-medium text-gray-700">Tier Benefits</label>
                    <textarea id="tier_benefits" name="tier_benefits" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="showCustomTierModal = false" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-400 transition">Cancel</button>
                    <button type="submit" name="create_custom_tier" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">Create Tier</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showLogsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <h2 class="text-2xl font-semibold mb-4 text-indigo-600">License Logs</h2>
            <ul class="mb-4 max-h-60 overflow-y-auto">
                <template x-for="log in currentLogs" :key="log.id">
                    <li class="mb-2">
                        <span x-text="log.action"></span> - 
                        IP: <span x-text="log.ip_address"></span> - 
                        <span x-text="new Date(log.timestamp).toLocaleString()"></span>
                    </li>
                </template>
            </ul>
            <div class="flex justify-end">
                <button @click="showLogsModal = false" class="bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700">Close</button>
            </div>
        </div>
    </div>

    <div x-show="showBanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <h2 class="text-2xl font-semibold mb-4 text-indigo-600">Ban License</h2>
            <form action="license.php" method="post">
                <input type="hidden" name="license_id" x-bind:value="currentLicenseId">
                <div class="mb-4">
                    <label for="ban_reason" class="block text-sm font-medium text-gray-700">Ban Reason</label>
                    <textarea id="ban_reason" name="ban_reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="showBanModal = false" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md mr-2 hover:bg-gray-400 transition">Cancel</button>
                    <button type="submit" name="ban_license" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">Ban License</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function copyApiKey() {
            const apiKeyInput = document.getElementById('apiKey');
            apiKeyInput.select();
            apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');
            alert('API Key copied to clipboard');
        }
    </script>
</body>
</html>