<?php
// Sign in (or sign up) with Discord.
require_once '../../includes/config.php';

$redirect_uri = absolute_url('/auth/api/call_request.php');

$fail = function ($message) {
    $_SESSION['error'] = $message;
    redirect('/auth/login.php');
};

if (DISCORD_CLIENT_ID === '' || DISCORD_CLIENT_SECRET === '') {
    $fail('Discord sign-in is not configured on this server.');
}

if (!isset($_GET['code'])) {
    oauth_redirect('https://discord.com/api/oauth2/authorize', [
        'client_id' => DISCORD_CLIENT_ID,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'identify email',
    ], 'discord_login_state');
}

if (!oauth_state_ok('discord_login_state')) {
    $fail('Failed to authenticate with Discord');
}

$access_token = oauth_exchange_code('https://discord.com/api/oauth2/token', [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
]);
$discord_user = $access_token ? http_get_json('https://discord.com/api/users/@me', $access_token) : null;

if (empty($discord_user['id'])) {
    $fail('Failed to authenticate with Discord');
}

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE discord_id = ? AND is_deleted = 0");
$stmt->execute([$discord_user['id']]);
$user = $stmt->fetch();

if (!$user) {
    $username = discord_display_name($discord_user);
    // Discord accounts sign in through Discord, so the local password is random and unusable.
    $pdo->prepare("INSERT INTO users (username, email, password, discord_id, discord_username) VALUES (?, ?, ?, ?, ?)")
        ->execute([$username, $discord_user['email'] ?? '', password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $discord_user['id'], $username]);
    $user = ['id' => (int) $pdo->lastInsertId(), 'username' => $username];
}

login_user($user['id'], $user['username']);
redirect('/app/dashboard.php');
