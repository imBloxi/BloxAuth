<?php
require_once '../includes/config.php';
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
        "content" => "New license created: $license_key",
        "embeds" => [
            [
                "title" => "License Details",
                "description" => "A new license has been issued.",
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

    $context = stream_context_create($options);
    $result = file_get_contents($webhook_url, false, $context);
    if ($result === FALSE) {
        error_log("Failed to send Discord webhook.");
    }
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

// Function to create a license
function create_license($pdo, $user_id, $roblox_user_id, $description, $valid_until) {
    $stmt = $pdo->prepare("INSERT INTO licenses_new (user_id, roblox_user_id, description, valid_until, whitelist_type) VALUES (?, ?, ?, ?, 'user')");
    return $stmt->execute([$user_id, $roblox_user_id, $description, $valid_until]);
}

// Handle license actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_license'])) {
        $roblox_user_id = $_POST['roblox_user_id'];
        $description = $_POST['description'] ?? '';
        $valid_until = $_POST['valid_until'] ?? null;

        if (!is_numeric($roblox_user_id)) {
            $_SESSION['error_message'] = "Invalid Roblox USER ID provided.";
        } else {
            if (create_license($pdo, $user_id, $roblox_user_id, $description, $valid_until)) {
                $_SESSION['success_message'] = "License created successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to create license.";
            }
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
        $response = apiRequest('index.php?type=ban', 'POST', [
            'license_id' => $_POST['license_id'],
            'ban_reason' => $_POST['ban_reason']
        ]);

        if ($response['success']) {
            $_SESSION['message'] = "License banned successfully.";
        } else {
            $_SESSION['error'] = "Failed to ban license.";
        }
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

// Function to fetch license logs
function getLicenseLogs($license_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM license_logs WHERE license_id = ? ORDER BY timestamp DESC LIMIT 10");
    $stmt->execute([$license_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function apiRequest($endpoint, $method, $data) {
    $apiUrl = APP_URL . '/api/v1/licenses/' . $endpoint;
    $apiKey = API_KEY; // Assume API_KEY is defined in config.php

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
            'method'  => $method,
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($apiUrl, false, $context);
    if ($result === FALSE) { /* Handle error */ }

    return json_decode($result, true);
}

?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Management - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="h-full" x-data="{ showCreateModal: false, showEditModal: false, currentLicense: {} }">
    <div class="min-h-full">
        <?php include '../includes/navbar.php'; ?>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">License Management</h1>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                </div>
            <?php endif; ?>

            <div class="mb-4 flex justify-between items-center">
                <button @click="showCreateModal = true" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Create New License
                </button>
                <form action="" method="get" class="flex">
                    <input type="text" name="search" placeholder="Search licenses" class="rounded-l-md border-t mr-0 border-b border-l text-gray-800 border-gray-200 bg-white px-3 py-2">
                    <select name="whitelist_type" class="border-t border-b border-gray-200 bg-white px-3 py-2">
                        <option value="">All Types</option>
                        <option value="user">User</option>
                        <option value="group">Group</option>
                        <option value="place">Place</option>
                    </select>
                    <button type="submit" class="px-4 rounded-r-md bg-gray-300 text-gray-800 font-bold py-2 uppercase border-gray-300 border-t border-b border-r">Search</button>
                </form>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($license['key']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($license['whitelist_type']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($license['description']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $license['valid_until']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="currentLicense = <?php echo htmlspecialchars(json_encode($license)); ?>; showEditModal = true" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                        <button type="submit" name="revoke_license" class="text-red-600 hover:text-red-900">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Create License Modal -->
    <div x-show="showCreateModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="post">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <label for="whitelist_type" class="block text-sm font-medium text-gray-700">License Type</label>
                            <select name="whitelist_type" id="whitelist_type" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="user">User</option>
                                <option value="group">Group</option>
                                <option value="place">Place</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="whitelist_id" class="block text-sm font-medium text-gray-700">Roblox User ID</label>
                            <input type="text" name="roblox_user_id" id="whitelist_id" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" name="description" id="description" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="valid_until" class="block text-sm font-medium text-gray-700">Valid Until</label>
                            <input type="datetime-local" name="valid_until" id="valid_until" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="roblox_user_id" class="block text-sm font-medium text-gray-700">Roblox User ID</label>
                            <input type="text" name="roblox_user_id" id="roblox_user_id" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            <p class="mt-1 text-sm text-gray-600">
                                Please verify the Roblox User ID carefully. <a href="#" id="robloxProfileLink" target="_blank">Click here to view profile.</a>
                            </p>
                        </div>
                        <script>
                            const robloxUserIdInput = document.getElementById('roblox_user_id');
                            const robloxProfileLink = document.getElementById('robloxProfileLink');
                            robloxUserIdInput.addEventListener('input', function() {
                                if (this.value.match(/^\d+$/)) {
                                    robloxProfileLink.href = `https://www.roblox.com/users/${this.value}/profile`;
                                } else {
                                    robloxProfileLink.href = '#';
                                }
                            });
                        </script>
                        <div class="mb-4">
                            <label for="max_uses" class="block text-sm font-medium text-gray-700">Max Uses</label>
                            <input type="number" name="max_uses" id="max_uses" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="custom_tier" class="block text-sm font-medium text-gray-700">Custom Tier</label>
                            <input type="text" name="custom_tier" id="custom_tier" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="webhook_url" class="block text-sm font-medium text-gray-700">Webhook URL</label>
                            <input type="url" name="webhook_url" id="webhook_url" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="flex items-start mb-4">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="transferable" id="transferable" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="transferable" class="font-medium text-gray-700">Transferable</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" name="create_license" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Create License
                        </button>
                        <button type="button" @click="showCreateModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit License Modal -->
    <div x-show="showEditModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="" method="post">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <input type="hidden" name="license_id" x-bind:value="currentLicense.id">
                        <div class="mb-4">
                            <label for="edit_description" class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" name="description" id="edit_description" x-bind:value="currentLicense.description" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="edit_valid_until" class="block text-sm font-medium text-gray-700">Valid Until</label>
                            <input type="datetime-local" name="valid_until" id="edit_valid_until" x-bind:value="currentLicense.valid_until" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="edit_max_uses" class="block text-sm font-medium text-gray-700">Max Uses</label>
                            <input type="number" name="max_uses" id="edit_max_uses" x-bind:value="currentLicense.max_uses" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="edit_custom_tier" class="block text-sm font-medium text-gray-700">Custom Tier</label>
                            <input type="text" name="custom_tier" id="edit_custom_tier" x-bind:value="currentLicense.custom_tier" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="mb-4">
                            <label for="edit_webhook_url" class="block text-sm font-medium text-gray-700">Webhook URL</label>
                            <input type="url" name="webhook_url" id="edit_webhook_url" x-bind:value="currentLicense.webhook_url" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="flex items-start mb-4">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="transferable" id="edit_transferable" x-bind:checked="currentLicense.transferable" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="edit_transferable" class="font-medium text-gray-700">Transferable</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" name="edit_license" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Update License
                        </button>
                        <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
