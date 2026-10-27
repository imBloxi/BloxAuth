<?php
// Central bootstrap: every page includes this file. It loads settings,
// starts the session, connects to the database and loads shared functions.

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/..'));
}

// Read a setting from the environment, falling back to a default.
function env($name, $default = null) {
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

// Application settings
define('APP_NAME', env('APP_NAME', 'BloxAuth'));
// Public base URL without a trailing slash, e.g. https://auth.example.com.
// Leave empty to use relative links.
define('APP_URL', rtrim(env('APP_URL', ''), '/'));
define('APP_VERSION', '1.1.0');

// API settings
define('API_RATE_LIMIT', (int) env('API_RATE_LIMIT', 60)); // requests per minute per IP
define('API_VERSION', 'v1');

// Security settings
define('SESSION_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 12);

// Captcha. Turned on automatically once a secret key is configured.
define('RECAPTCHA_SITE_KEY', env('RECAPTCHA_SITE_KEY', ''));
define('RECAPTCHA_SECRET_KEY', env('RECAPTCHA_SECRET_KEY', ''));
define('USE_RECAPTCHA', RECAPTCHA_SECRET_KEY !== '');

define('HCAPTCHA_SITE_KEY', env('HCAPTCHA_SITE_KEY', ''));
define('HCAPTCHA_SECRET_KEY', env('HCAPTCHA_SECRET_KEY', ''));
define('USE_HCAPTCHA', HCAPTCHA_SECRET_KEY !== '');

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 15 * 60);

define('MAX_REGISTRATION_ATTEMPTS', 3);
define('REGISTRATION_ATTEMPT_TIMEOUT', 60 * 60);

// Email settings
define('SMTP_HOST', env('SMTP_HOST', 'smtp.example.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'noreply@example.com'));
define('SMTP_FROM_NAME', APP_NAME);

// Third-party integrations. Never hardcode these: set them in the environment.
define('ROBLOX_CLIENT_ID', env('ROBLOX_CLIENT_ID', ''));
define('ROBLOX_CLIENT_SECRET', env('ROBLOX_CLIENT_SECRET', ''));
define('ROBLOX_GAMEPASS_ID', env('ROBLOX_GAMEPASS_ID', '25106617'));
define('DISCORD_CLIENT_ID', env('DISCORD_CLIENT_ID', ''));
define('DISCORD_CLIENT_SECRET', env('DISCORD_CLIENT_SECRET', ''));
define('LUAOBFUSCATOR_API_KEY', env('LUAOBFUSCATOR_API_KEY', ''));
define('SELLIX_WEBHOOK_SECRET', env('SELLIX_WEBHOOK_SECRET', '')); // site-wide subscription webhook
define('SELLIX_TRIAL_URL', env('SELLIX_TRIAL_URL', ''));

// Logging
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', env('ERROR_LOG_FILE', sys_get_temp_dir() . '/bloxauth_error.log'));

date_default_timezone_set('UTC');

function custom_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false; // respect the @ operator
    }
    if (LOG_ERRORS) {
        error_log(date('Y-m-d H:i:s') . " - Error [$errno] $errstr in $errfile on line $errline\n", 3, ERROR_LOG_FILE);
    }
    return true;
}
set_error_handler('custom_error_handler');

// Session
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax', // blocks cross-site form posts from carrying the session
    ]);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';

if (PHP_SAPI !== 'cli') {
    try_remember_login($pdo);
}
