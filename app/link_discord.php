<?php
// Links the logged-in user's Discord account through Discord OAuth.
require_once '../includes/config.php';
redirect_if_not_logged_in();

$redirect_uri = absolute_url('/app/link_discord.php');

if (DISCORD_CLIENT_ID === '' || DISCORD_CLIENT_SECRET === '') {
    $_SESSION['message'] = 'Discord linking is not configured on this server.';
    redirect('/app/settings.php');
}

if (isset($_GET['error'])) {
    $_SESSION['message'] = 'Error linking Discord account: ' . $_GET['error'];
    redirect('/app/settings.php');
}

if (!isset($_GET['code'])) {
    oauth_redirect('https://discord.com/api/oauth2/authorize', [
        'client_id' => DISCORD_CLIENT_ID,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'identify',
    ], 'discord_link_state');
}

if (!oauth_state_ok('discord_link_state')) {
    $_SESSION['message'] = 'Discord linking failed: invalid state. Please try again.';
    redirect('/app/settings.php');
}

$access_token = oauth_exchange_code('https://discord.com/api/oauth2/token', [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
]);
$discord_user = $access_token ? http_get_json('https://discord.com/api/users/@me', $access_token) : null;

if (empty($discord_user['id'])) {
    $_SESSION['message'] = 'Failed to fetch Discord user info.';
    redirect('/app/settings.php');
}

$pdo->prepare('UPDATE users SET discord_username = ?, discord_id = ? WHERE id = ?')
    ->execute([discord_display_name($discord_user), $discord_user['id'], $_SESSION['user_id']]);

$_SESSION['message'] = 'Discord account linked successfully.';
redirect('/app/settings.php');
