<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

$clientId = '8950761597934695318';
$clientSecret = 'RBX-33Rua6PWl0St57gwgDpnIZ6Ze0qy19yhLFFjNBelzfr6mGqUy_73LtPfsMq7JaD_';
$redirectUri = 'https://lookshance.com/trial/auth/link_roblox.php';
$scope = 'openid profile asset:read';
$state = bin2hex(random_bytes(16));

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'You must be logged in to link your Roblox account.';
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $tokenEndpoint = 'https://apis.roblox.com/oauth/v1/token';

    $postData = [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code,
        'redirect_uri' => $redirectUri
    ];

    $ch = curl_init($tokenEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);

        if (isset($tokenData['access_token'])) {
            $accessToken = $tokenData['access_token'];

            $verifyEndpoint = 'https://apis.roblox.com/users/v1/users/authenticated';
            $header = ['Authorization: Bearer ' . $accessToken];

            $ch = curl_init($verifyEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $userData = json_decode($response, true);

                if (isset($userData['name'])) {
                    $robloxUsername = $userData['name'];

                    $stmt = $pdo->prepare('SELECT id FROM roblox_accounts WHERE user_id = ?');
                    $stmt->execute([$user_id]);
                    $existingLink = $stmt->fetchColumn();

                    if ($existingLink) {
                        $_SESSION['message'] = 'Your Roblox account is already linked.';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO roblox_accounts (user_id, roblox_username) VALUES (?, ?)');
                        if ($stmt->execute([$user_id, $robloxUsername])) {
                            $_SESSION['message'] = 'Roblox account linked successfully.';
                        } else {
                            $_SESSION['message'] = 'Failed to link Roblox account.';
                        }
                    }
                } else {
                    $_SESSION['message'] = 'Failed to get Roblox username from API.';
                }
            } else {
                $_SESSION['message'] = 'Failed to verify Roblox authentication: HTTP ' . $httpCode;
            }
        } else {
            $_SESSION['message'] = 'Failed to obtain access token.';
        }
    } else {
        $_SESSION['message'] = 'Failed to exchange authorization code for access token: HTTP ' . $httpCode;
    }

    header('Location: ../billing/billing.php');
    exit();
} elseif (isset($_GET['error'])) {
    $_SESSION['message'] = 'Error linking Roblox account: ' . $_GET['error_description'];
    header('Location: ../billing/billing.php');
    exit();
} else {
    $authUrl = 'https://apis.roblox.com/oauth/v1/authorize' .
               '?client_id=' . $clientId .
               '&response_type=code' .
               '&redirect_uri=' . urlencode($redirectUri) .
               '&scope=' . urlencode($scope) .
               '&state=' . urlencode($state);

    header('Location: ' . $authUrl);
    exit();
}
?>