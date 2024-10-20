<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Check if user has enough credits
$stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_credits = $stmt->fetchColumn();

if ($user_credits < 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient credits']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $script_content = $_POST['script'] ?? '';
    $options = json_decode($_POST['options'], true) ?? [];

    if (empty($script_content)) {
        http_response_code(400);
        echo json_encode(['error' => 'No script provided']);
        exit;
    }

    $api_key = '798-fcb4-'; // Replace with your actual API key
    $obfuscate_url = 'https://api.luaobfuscator.com/v1/obfuscator/obfuscate';

    // Prepare the data for the API request
    $data = [
        'script' => $script_content,
        'options' => [
            'Virtualize' => $options['Virtualize'] ?? false,
            'Constants' => $options['Constants'] ?? false,
            'Minify' => $options['Minify'] ?? false,
            'EncryptStrings' => $options['EncryptStrings'] ?? false,
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
        http_response_code(500);
        echo json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
    } else {
        $result = json_decode($response, true);
        if (isset($result['code'])) {
            // Deduct 1 credit from the user
            $stmt = $pdo->prepare("UPDATE users SET credits = credits - 1 WHERE id = ?");
            $stmt->execute([$user_id]);

            // Log the obfuscation
            $stmt = $pdo->prepare("INSERT INTO obfuscation_logs (user_id, script_length, options) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, strlen($script_content), json_encode($options)]);

            // Output the obfuscated code
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="obfuscated_script.lua"');
            echo $result['code'];
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to obfuscate the script. Please try again.']);
        }
    }

    curl_close($ch);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}