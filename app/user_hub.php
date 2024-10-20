<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user information
$user_info = getUserInfo($pdo, $user_id);

// Fetch user scripts with versions
$user_scripts = getUserScriptsWithVersions($pdo, $user_id);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_script'])) {
        createNewScript($pdo, $user_id, $_POST['script_name'], $_POST['script_content']);
    } elseif (isset($_POST['create_version'])) {
        createNewScriptVersion($pdo, $user_id, $_POST['script_id'], $_POST['version_name'], $_POST['version_content']);
    } elseif (isset($_POST['update_active_version'])) {
        updateActiveScriptVersion($pdo, $user_id, $_POST['script_id'], $_POST['version_id']);
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Hub - Script Version Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="{ showCreateScriptModal: false, showCreateVersionModal: false, currentScriptId: null }">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Script Version Control</h1>

        <!-- Create New Script Button -->
        <button @click="showCreateScriptModal = true" class="bg-blue-500 text-white px-4 py-2 rounded mb-6 hover:bg-blue-600 transition duration-300">
            Create New Script
        </button>

        <!-- Scripts List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($user_scripts as $script): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4"><?= htmlspecialchars($script['name']) ?></h2>
                    <p class="text-gray-600 mb-4">Active Version: <?= htmlspecialchars($script['active_version_name']) ?></p>
                    
                    <!-- Version Dropdown -->
                    <div class="mb-4">
                        <label for="version_<?= $script['id'] ?>" class="block text-sm font-medium text-gray-700">Change Version:</label>
                        <select id="version_<?= $script['id'] ?>" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" onchange="updateActiveVersion(<?= $script['id'] ?>, this.value)">
                            <?php foreach ($script['versions'] as $version): ?>
                                <option value="<?= $version['id'] ?>" <?= $version['id'] == $script['active_version_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($version['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Create New Version Button -->
                    <button @click="showCreateVersionModal = true; currentScriptId = <?= $script['id'] ?>" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition duration-300">
                        Create New Version
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Create Script Modal -->
    <div x-show="showCreateScriptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold mb-4">Create New Script</h2>
            <form action="" method="POST">
                <div class="mb-4">
                    <label for="script_name" class="block text-sm font-medium text-gray-700">Script Name</label>
                    <input type="text" id="script_name" name="script_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="script_content" class="block text-sm font-medium text-gray-700">Script Content</label>
                    <textarea id="script_content" name="script_content" rows="10" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="showCreateScriptModal = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300 transition duration-300">Cancel</button>
                    <button type="submit" name="create_script" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-300">Create Script</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Version Modal -->
    <div x-show="showCreateVersionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold mb-4">Create New Version</h2>
            <form action="" method="POST">
                <input type="hidden" name="script_id" x-bind:value="currentScriptId">
                <div class="mb-4">
                    <label for="version_name" class="block text-sm font-medium text-gray-700">Version Name</label>
                    <input type="text" id="version_name" name="version_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="version_content" class="block text-sm font-medium text-gray-700">Version Content</label>
                    <textarea id="version_content" name="version_content" rows="10" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="showCreateVersionModal = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-300 transition duration-300">Cancel</button>
                    <button type="submit" name="create_version" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition duration-300">Create Version</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateActiveVersion(scriptId, versionId) {
            fetch('update_active_version.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `script_id=${scriptId}&version_id=${versionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Active version updated successfully');
                } else {
                    alert('Failed to update active version');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('An error occurred while updating the active version');
            });
        }
    </script>
</body>
</html>
