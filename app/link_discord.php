<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$clientId = 'YOUR_DISCORD_CLIENT_ID';
$clientSecret = 'YOUR_DISCORD_CLIENT_SECRET';
$redirectUri = 'https://yourdomain.com/app/discord_callback.php';
$scope = 'identify';
$state = bin2hex(random_bytes(16));

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Exchange code for access token
    $tokenUrl = 'https://discord.com/api/oauth2/token';
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $tokenData = json_decode($response, true);
    curl_close($ch);

    if (isset($tokenData['access_token'])) {
        $accessToken = $tokenData['access_token'];

        // Fetch user info from Discord
        $userUrl = 'https://discord.com/api/users/@me';
        $headers = ['Authorization: Bearer ' . $accessToken];
        $ch = curl_init($userUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $userData = json_decode($response, true);
        curl_close($ch);

        if (isset($userData['username'])) {
            $discordUsername = $userData['username'] . '#' . $userData['discriminator'];

            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("UPDATE users SET discord_username = ? WHERE id = ?");
            $stmt->execute([$discordUsername, $user_id]);

            $_SESSION['message'] = 'Discord account linked successfully.';
        } else {
            $_SESSION['message'] = 'Failed to fetch Discord user info.';
        }
    } else {
        $_SESSION['message'] = 'Failed to obtain access token.';
    }

    header('Location: settings.php');
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['message'] = 'Error linking Discord account: ' . $_GET['error'];
    header('Location: settings.php');
    exit();
} else {
    $authUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => $scope,
        'state' => $state
    ]);

    header('Location: ' . $authUrl);
    exit();
}
?>
