<?php
session_start();
require '../../includes/db.php';
require '../../includes/functions.php';

// Discord OAuth2 configuration
$discord_client_id = 'YOUR_DISCORD_CLIENT_ID';
$discord_client_secret = 'YOUR_DISCORD_CLIENT_SECRET';
$discord_redirect_uri = 'https://your-domain.com/auth/api/call_request.php';

if (isset($_GET['code'])) {
    // Exchange code for access token
    $token_url = 'https://discord.com/api/oauth2/token';
    $token_data = array(
        "client_id" => $discord_client_id,
        "client_secret" => $discord_client_secret,
        "grant_type" => "authorization_code",
        "code" => $_GET['code'],
        "redirect_uri" => $discord_redirect_uri
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $token_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($token_data),
        CURLOPT_RETURNTRANSFER => true
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response, true);

    if (isset($result['access_token'])) {
        // Get user info
        $user_url = 'https://discord.com/api/users/@me';
        $header = array("Authorization: Bearer {$result['access_token']}");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $user_url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_RETURNTRANSFER => true
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $user_info = json_decode($response, true);

        if (isset($user_info['id'])) {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE discord_id = ?");
            $stmt->execute([$user_info['id']]);
            $user = $stmt->fetch();

            if ($user) {
                // User exists, log them in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: ../../app/dashboard.php');
                exit();
            } else {
                // New user, register them
                $username = $user_info['username'] . '#' . $user_info['discriminator'];
                $email = $user_info['email'] ?? null;
                $discord_id = $user_info['id'];

                $stmt = $pdo->prepare("INSERT INTO users (username, email, discord_id, discord_username) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $discord_id, $username]);

                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                header('Location: ../../app/dashboard.php');
                exit();
            }
        }
    }
}

// If we get here, something went wrong
$_SESSION['error'] = 'Failed to authenticate with Discord';
header('Location: ../login.php');
exit();