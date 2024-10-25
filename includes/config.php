<?php
// Define ROOT_PATH only if it's not already defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));
}

// Include database connection settings
require_once ROOT_PATH . '/includes/db.php';

// Application settings
define('APP_NAME', 'BloxAuth');
define('APP_URL', 'https://your-domain.com');
define('APP_VERSION', '1.0.0');

// API settings
define('API_RATE_LIMIT', 60); // Requests per minute
define('API_VERSION', 'v1');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 12);

// Anti-bot and anti-spam settings
define('USE_RECAPTCHA', true);
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key');

define('USE_HCAPTCHA', false);
define('HCAPTCHA_SITE_KEY', 'your_hcaptcha_site_key');
define('HCAPTCHA_SECRET_KEY', 'your_hcaptcha_secret_key');

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 15 * 60); // 15 minutes

define('MAX_REGISTRATION_ATTEMPTS', 3);
define('REGISTRATION_ATTEMPT_TIMEOUT', 60 * 60); // 1 hour

// Email settings
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-email-password');
define('SMTP_FROM_EMAIL', 'noreply@your-domain.com');
define('SMTP_FROM_NAME', APP_NAME);

// Logging settings
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', sys_get_temp_dir() . '/bloxauth_error.log');

// Timezone setting
date_default_timezone_set('UTC');

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Error handling function
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno] $errstr in $errfile on line $errline\n";
    if (LOG_ERRORS) {
        error_log($error_message, 3, ERROR_LOG_FILE);
    }
}

// Set custom error handler
set_error_handler('custom_error_handler');

// Anti-bot and anti-spam functions
function verify_recaptcha($response) {
    if (!USE_RECAPTCHA) return true;

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success;
}

function verify_hcaptcha($response) {
    if (!USE_HCAPTCHA) return true;

    $url = 'https://hcaptcha.com/siteverify';
    $data = [
        'secret' => HCAPTCHA_SECRET_KEY,
        'response' => $response
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success;
}

function check_rate_limit($action, $identifier, $max_attempts, $timeout) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE action = ? AND identifier = ? AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$action, $identifier, $timeout]);
    $count = $stmt->fetchColumn();

    if ($count >= $max_attempts) {
        return false;
    }

    $stmt = $pdo->prepare("INSERT INTO rate_limits (action, identifier, timestamp) VALUES (?, ?, NOW())");
    $stmt->execute([$action, $identifier]);

    return true;
}

// API helper functions
function api_response($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

function validate_api_key($api_key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT user_id FROM api_keys WHERE api_key = ? AND is_active = 1");
    $stmt->execute([$api_key]);
    return $stmt->fetchColumn();
}


if (isset($_POST['create_license'])) {
    $license_data = [
        'key' => bin2hex(random_bytes(16)),
        'whitelist_type' => $_POST['license_type'],
        'whitelist_id' => $_POST['whitelist_id'],
        'max_uses' => $_POST['max_uses'] ?: null,
        'features' => isset($_POST['features']) ? implode(',', $_POST['features']) : '',
        'notes' => $_POST['notes'],
        'created_by' => $user_id,
    ];

    // Calculate expiration date
    if ($_POST['duration_unit'] === 'lifetime') {
        $license_data['valid_until'] = null;
    } else {
        $duration_value = intval($_POST['duration_value']);
        $duration_unit = $_POST['duration_unit'];
        $license_data['valid_until'] = date('Y-m-d H:i:s', strtotime("+{$duration_value} {$duration_unit}"));
    }

    // Handle custom fields
    if (isset($_POST['custom_fields'])) {
        $custom_fields = [];
        foreach ($_POST['custom_fields'] as $field) {
            if (!empty($field['key']) && !empty($field['value'])) {
                $custom_fields[$field['key']] = $field['value'];
            }
        }
        $license_data['custom_fields'] = json_encode($custom_fields);
    }

    if (create_license($pdo, $license_data)) {
        $_SESSION['success_message'] = "License created successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to create license.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


function log_api_request($user_id, $endpoint) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO api_requests (user_id, endpoint, request_time) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $endpoint]);
}

// Function to validate CSRF token
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Include other necessary functions
require_once ROOT_PATH . '/includes/functions.php';

// Billing API functions
function get_user_trial_info($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT trial_start_date, trial_end_date, trial_credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_subscription_info($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT subscription_tier, subscription_end_date FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_credits($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Authorize.Net settings
define('AUTHORIZENET_API_LOGIN_ID', 'your_api_login_id');
define('AUTHORIZENET_TRANSACTION_KEY', 'your_transaction_key');
define('AUTHORIZENET_SANDBOX', true); // Set to false for production

// Define API_KEY if not already defined
if (!defined('API_KEY')) {
    define('API_KEY', 'your_actual_api_key_here');  // Replace 'your_actual_api_key_here' with your actual API key
}

?>
