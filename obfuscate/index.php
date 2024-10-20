<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Fetch user's remaining credits
$stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_credits = $stmt->fetchColumn();

$notification = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_credits < 1) {
        $notification = 'Insufficient credits to perform obfuscation.';
    } else {
        $script_content = $_POST['scriptContent'] ?? '';
        $options = $_POST['options'] ?? [];

        if (empty($script_content)) {
            $notification = 'No script provided.';
        } else {
            $api_key = '798156b-d4'; // Replace with your actual API key
            $obfuscate_url = 'https://api.luaobfuscator.com/v1/obfuscator/obfuscate';

            // Prepare the data for the API request
            $data = [
                'script' => $script_content,
                'options' => [
                    'Virtualize' => in_array('Virtualize', $options),
                    'Constants' => in_array('Constants', $options),
                    'Minify' => in_array('Minify', $options),
                    'EncryptStrings' => in_array('EncryptStrings', $options),
                ]
            ];

            $ch = curl_init($obfuscate_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $api_key
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $notification = 'Curl error: ' . curl_error($ch);
            } else {
                $result = json_decode($response, true);
                if (isset($result['code'])) {
                    // Deduct 1 credit from the user
                    $stmt = $pdo->prepare("UPDATE users SET credits = credits - 1 WHERE id = ?");
                    $stmt->execute([$user_id]);

                    // Log the obfuscation
                    $stmt = $pdo->prepare("INSERT INTO obfuscation_logs (user_id, script_length, options) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, strlen($script_content), json_encode($options)]);

                    // Save the obfuscated script to a file
                    $obfuscated_file = '../uploads/obfuscated_' . time() . '.lua';
                    file_put_contents($obfuscated_file, $result['code']);

                    // Provide download link
                    $notification = 'Obfuscation complete! <a href="' . $obfuscated_file . '" download>Download your obfuscated script</a>.';
                } else {
                    $notification = 'Failed to obfuscate the script. Please try again.';
                }
            }

            curl_close($ch);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Script Obfuscation - BloxAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
</head>
<body class="text-gray-300" x-data="{ step: 1, script: '', fileName: '', obfuscationOptions: [] }">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-center text-purple-400">Script Obfuscation</h1>

        <?php if ($notification): ?>
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <?= $notification ?>
            </div>
        <?php endif; ?>

        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <span :class="{ 'text-purple-400 font-bold': step === 1 }">1. Upload Script</span>
                    <span :class="{ 'text-purple-400 font-bold': step === 2 }">2. Configure Options</span>
                    <span :class="{ 'text-purple-400 font-bold': step === 3 }">3. Obfuscate</span>
                </div>
                <div class="mt-2 h-2 bg-gray-700 rounded-full">
                    <div class="h-full bg-purple-600 rounded-full transition-all duration-300" :style="{ width: (step / 3) * 100 + '%' }"></div>
                </div>
            </div>

            <form id="obfuscationForm" method="post">
                <!-- Step 1: Upload Script -->
                <div x-show="step === 1">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Upload Your Lua Script</h2>
                    <div class="mb-4">
                        <label for="scriptFile" class="block text-sm font-medium text-gray-400">Upload Lua File:</label>
                        <input type="file" id="scriptFile" name="scriptFile" accept=".lua" @change="handleFileUpload" class="mt-1 block w-full text-sm text-gray-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-purple-600 file:text-white
                            hover:file:bg-purple-700">
                    </div>
                    <div class="mb-4">
                        <label for="scriptContent" class="block text-sm font-medium text-gray-400">Or Paste Script Content:</label>
                        <textarea id="scriptContent" name="scriptContent" x-model="script" rows="10" class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white"></textarea>
                    </div>
                    <button @click="step = 2" type="button" class="bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 transition duration-200">Next</button>
                </div>

                <!-- Step 2: Configure Options -->
                <div x-show="step === 2">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Configure Obfuscation Options</h2>
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="Virtualize" x-model="obfuscationOptions" class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-400">Virtualize</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="Constants" x-model="obfuscationOptions" class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-400">Constants</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="Minify" x-model="obfuscationOptions" class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-400">Minify</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="options[]" value="EncryptStrings" x-model="obfuscationOptions" class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-400">Encrypt Strings</span>
                        </label>
                    </div>
                    <div class="mt-4 flex justify-between">
                        <button @click="step = 1" type="button" class="bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 transition duration-200">Back</button>
                        <button @click="step = 3" type="button" class="bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 transition duration-200">Next</button>
                    </div>
                </div>

                <!-- Step 3: Obfuscate -->
                <div x-show="step === 3">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Obfuscate Your Script</h2>
                    <p class="mb-4">You have <?= $user_credits ?> credits remaining. Obfuscation costs 1 credit.</p>
                    <p class="mb-4">Review your settings and click "Obfuscate" to process your script.</p>
                    <div class="mb-4">
                        <strong class="block text-purple-300">Script Length:</strong>
                        <span x-text="script.length + ' characters'"></span>
                    </div>
                    <div class="mb-4">
                        <strong class="block text-purple-300">Selected Options:</strong>
                        <ul class="list-disc list-inside">
                            <li x-show="obfuscationOptions.includes('Virtualize')">Virtualize</li>
                            <li x-show="obfuscationOptions.includes('Constants')">Constants</li>
                            <li x-show="obfuscationOptions.includes('Minify')">Minify</li>
                            <li x-show="obfuscationOptions.includes('EncryptStrings')">Encrypt Strings</li>
                        </ul>
                    </div>
                    <div class="flex justify-between">
                        <button @click="step = 2" type="button" class="bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 transition duration-200">Back</button>
                        <button type="submit" class="bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 transition duration-200">Obfuscate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.fileName = file.name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.script = e.target.result;
                };
                reader.readAsText(file);
            }
        }
    </script>
</body>
</html>