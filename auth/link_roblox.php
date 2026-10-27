<?php
// Links the logged-in user's Roblox account through Roblox OAuth.
require_once '../includes/config.php';

if (!is_user_logged_in()) {
    $_SESSION['message'] = 'You must be logged in to link your Roblox account.';
    redirect('/auth/login.php');
}
$user_id = $_SESSION['user_id'];
$redirect_uri = absolute_url('/auth/link_roblox.php');

if (ROBLOX_CLIENT_ID === '' || ROBLOX_CLIENT_SECRET === '') {
    $_SESSION['message'] = 'Roblox linking is not configured on this server.';
    redirect('/billing/index.php');
}

if (isset($_GET['error'])) {
    $_SESSION['message'] = 'Error linking Roblox account: ' . ($_GET['error_description'] ?? $_GET['error']);
    redirect('/billing/index.php');
}

if (!isset($_GET['code'])) {
    oauth_redirect('https://apis.roblox.com/oauth/v1/authorize', [
        'client_id' => ROBLOX_CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri' => $redirect_uri,
        'scope' => 'openid profile',
    ], 'roblox_oauth_state');
}

if (!oauth_state_ok('roblox_oauth_state')) {
    $_SESSION['message'] = 'Roblox linking failed: invalid state. Please try again.';
    redirect('/billing/index.php');
}

$access_token = oauth_exchange_code('https://apis.roblox.com/oauth/v1/token', [
    'client_id' => ROBLOX_CLIENT_ID,
    'client_secret' => ROBLOX_CLIENT_SECRET,
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
]);
$profile = $access_token ? http_get_json('https://apis.roblox.com/oauth/v1/userinfo', $access_token) : null;

if (empty($profile['sub'])) {
    $_SESSION['message'] = 'Failed to get your Roblox account from Roblox.';
    redirect('/billing/index.php');
}

$roblox_username = $profile['preferred_username'] ?? $profile['name'] ?? '';
$pdo->prepare('INSERT INTO roblox_accounts (user_id, roblox_username, roblox_user_id, verified, linked_at) VALUES (?, ?, ?, 1, NOW())
               ON DUPLICATE KEY UPDATE roblox_username = VALUES(roblox_username), roblox_user_id = VALUES(roblox_user_id), verified = 1, linked_at = NOW()')
    ->execute([$user_id, $roblox_username, $profile['sub']]);

$_SESSION['message'] = 'Roblox account linked successfully.';
redirect('/billing/index.php');
