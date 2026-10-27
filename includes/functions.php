<?php
// Shared helpers. Loaded by includes/config.php, which also provides $pdo.

/* ---------------------------------------------------------------------
 * Output and URLs
 * ------------------------------------------------------------------- */

// Escape a value for HTML output.
function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Build a link to a path inside the app, e.g. url('/auth/login.php').
function url($path) {
    return APP_URL . $path;
}

function redirect($path) {
    header('Location: ' . url($path));
    exit();
}

// Read and clear a one-time message stored in the session.
function flash($key) {
    $message = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $message;
}

/* ---------------------------------------------------------------------
 * Authentication and roles
 * ------------------------------------------------------------------- */

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

// Send guests to the login page and banned users to the ban notice.
function redirect_if_not_logged_in() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        redirect('/auth/login.php');
    }
    check_user_ban_status($pdo, $_SESSION['user_id']);
}

function check_user_ban_status($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT is_banned, is_deleted, ban_reason FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || $user['is_deleted']) {
        session_unset();
        redirect('/auth/login.php');
    }
    if ($user['is_banned']) {
        $_SESSION['ban_reason'] = $user['ban_reason'];
        redirect('/not-approved/index.php');
    }
}

function is_staff_user($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT is_staff FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return (bool) $stmt->fetchColumn();
}

// Accepts isAdmin($pdo, $user_id) or isAdmin($user_id).
function isAdmin($pdo_or_user_id, $user_id = null) {
    global $pdo;
    if ($user_id === null) {
        $user_id = $pdo_or_user_id;
    }
    return is_staff_user($pdo, $user_id);
}

// Staff-only pages.
function ensure_admin() {
    global $pdo;
    redirect_if_not_logged_in();
    if (!is_staff_user($pdo, $_SESSION['user_id'])) {
        http_response_code(403);
        exit('Access denied.');
    }
}

// Log a user back in from the "remember me" cookie set at login.
function try_remember_login($pdo) {
    if (isset($_SESSION['user_id']) || empty($_COOKIE['remember_token']) || !is_string($_COOKIE['remember_token'])) {
        return;
    }
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE remember_token = ? AND is_deleted = 0');
    $stmt->execute([hash('sha256', $_COOKIE['remember_token'])]);
    if ($user = $stmt->fetch()) {
        login_user($user['id'], $user['username']);
    }
}

// Start a fresh session for a user after a successful login or signup.
function login_user($user_id, $username) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
}

/* ---------------------------------------------------------------------
 * CSRF, captcha and rate limiting
 * ------------------------------------------------------------------- */

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function http_post_form($url, array $fields) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response === false ? null : json_decode($response, true);
}

function verify_recaptcha($response) {
    if (!USE_RECAPTCHA) return true;
    $result = http_post_form('https://www.google.com/recaptcha/api/siteverify', [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    return !empty($result['success']);
}

function verify_hcaptcha($response) {
    if (!USE_HCAPTCHA) return true;
    $result = http_post_form('https://hcaptcha.com/siteverify', [
        'secret' => HCAPTCHA_SECRET_KEY,
        'response' => $response,
    ]);
    return !empty($result['success']);
}

// Returns false once $identifier has used $max_attempts of $action within $timeout seconds.
function check_rate_limit($action, $identifier, $max_attempts, $timeout) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE action = ? AND identifier = ? AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$action, $identifier, $timeout]);
    if ($stmt->fetchColumn() >= $max_attempts) {
        return false;
    }
    $pdo->prepare("INSERT INTO rate_limits (action, identifier, timestamp) VALUES (?, ?, NOW())")
        ->execute([$action, $identifier]);
    if (random_int(1, 200) === 1) {
        $pdo->exec("DELETE FROM rate_limits WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
    return true;
}

/* ---------------------------------------------------------------------
 * OAuth (Roblox and Discord)
 * ------------------------------------------------------------------- */

// Full URL for a path, used for OAuth redirect URIs.
function absolute_url($path) {
    if (preg_match('#^https?://#', APP_URL)) {
        return APP_URL . $path;
    }
    $scheme = is_https() ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL . $path;
}

// Redirect to a provider's authorize page with a fresh anti-CSRF state value.
function oauth_redirect($authorize_url, array $params, $session_key) {
    $state = bin2hex(random_bytes(16));
    $_SESSION[$session_key] = $state;
    header('Location: ' . $authorize_url . '?' . http_build_query($params + ['state' => $state]));
    exit();
}

// True when the callback's state matches the one we issued. Each state is single-use.
function oauth_state_ok($session_key) {
    $expected = $_SESSION[$session_key] ?? '';
    unset($_SESSION[$session_key]);
    return $expected !== '' && is_string($_GET['state'] ?? null) && hash_equals($expected, $_GET['state']);
}

// Exchange an authorization code for an access token. Returns the token or null.
function oauth_exchange_code($token_url, array $fields) {
    $result = http_post_form($token_url, $fields + ['grant_type' => 'authorization_code']);
    return $result['access_token'] ?? null;
}

// Discord dropped "#1234" discriminators; "0" means the account has none.
function discord_display_name(array $discord_user) {
    $name = $discord_user['username'] ?? 'discord_user';
    $discriminator = $discord_user['discriminator'] ?? '0';
    return $discriminator !== '0' ? "$name#$discriminator" : $name;
}

function http_get_json($url, $bearer_token = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $bearer_token ? ['Authorization: Bearer ' . $bearer_token] : [],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($response !== false && $code >= 200 && $code < 300) ? json_decode($response, true) : null;
}

/* ---------------------------------------------------------------------
 * JSON API helpers
 * ------------------------------------------------------------------- */

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function api_response($status, $message, $data = null, $code = 200) {
    $response = ['status' => $status, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    json_response($response, $code);
}

// Returns the owning user ID for an API key, or false.
function validate_api_key($api_key) {
    global $pdo;
    if (!is_string($api_key) || $api_key === '') {
        return false;
    }
    $stmt = $pdo->prepare("SELECT user_id FROM api_keys WHERE api_key = ?");
    $stmt->execute([$api_key]);
    return $stmt->fetchColumn();
}

// Read an API key from X-API-Key or "Authorization: Bearer <key>".
function get_request_api_key() {
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        return trim($_SERVER['HTTP_X_API_KEY']);
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
        return $m[1];
    }
    return $auth !== '' ? trim($auth) : null;
}

function read_json_body() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : null;
}

/* ---------------------------------------------------------------------
 * Licenses
 * ------------------------------------------------------------------- */

function generate_unique_license_key($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM licenses_new WHERE `key` = ?");
    do {
        $license_key = bin2hex(random_bytes(16));
        $stmt->execute([$license_key]);
    } while ($stmt->fetchColumn() > 0);
    return $license_key;
}

// Dashed key format used by the admin key issuer.
function generateLicenseKey() {
    return implode('-', array_map(fn($n) => bin2hex(random_bytes($n)), [1, 2, 1, 3, 1, 2]));
}

// Normalise an optional date/time input ("" means no expiry).
function normalize_datetime($value) {
    if ($value === null || $value === '') {
        return null;
    }
    $ts = strtotime((string) $value);
    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
}

function nullable_int($value) {
    return ($value === null || $value === '') ? null : (int) $value;
}

// Create a license for $user_id. Returns ['success' => bool, 'license_key', 'license_id'] or an error.
function create_license($pdo, $user_id, array $data) {
    $type = $data['whitelist_type'] ?? 'user';
    if (!in_array($type, ['user', 'group', 'place'], true)) {
        return ['success' => false, 'error' => 'whitelist_type must be user, group or place'];
    }

    $license_key = $data['key'] ?? generate_unique_license_key($pdo);
    $stmt = $pdo->prepare("INSERT INTO licenses_new
        (user_id, `key`, whitelist_id, whitelist_type, description, valid_until, roblox_user_id, max_uses, transferable, custom_tier, webhook_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([
            $user_id,
            $license_key,
            ($data['whitelist_id'] ?? '') === '' ? null : trim((string) $data['whitelist_id']),
            $type,
            $data['description'] ?? '',
            normalize_datetime($data['valid_until'] ?? null),
            nullable_int($data['roblox_user_id'] ?? null),
            nullable_int($data['max_uses'] ?? null),
            !empty($data['transferable'] ?? $data['is_transferable'] ?? false) ? 1 : 0,
            ($data['custom_tier'] ?? '') === '' ? null : $data['custom_tier'],
            ($data['webhook_url'] ?? '') === '' ? null : $data['webhook_url'],
        ]);
    } catch (PDOException $e) {
        error_log('create_license failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to create license'];
    }
    return ['success' => true, 'license_key' => $license_key, 'license_id' => (int) $pdo->lastInsertId()];
}

// Kept for older callers: save_license($pdo, $user_id, $data) returns bool.
function save_license($pdo, $user_id, $license_data) {
    return create_license($pdo, $user_id, $license_data)['success'];
}

function fetch_licenses($pdo, $user_id, $filters = []) {
    $query = "SELECT * FROM licenses_new WHERE user_id = ?";
    $params = [$user_id];

    if (!empty($filters['search'])) {
        $query .= " AND (`key` LIKE ? OR description LIKE ? OR CAST(roblox_user_id AS CHAR) = ? OR CAST(whitelist_id AS CHAR) = ?)";
        $like = '%' . $filters['search'] . '%';
        array_push($params, $like, $like, $filters['search'], $filters['search']);
    }
    if (!empty($filters['whitelist_type']) && in_array($filters['whitelist_type'], ['user', 'group', 'place'], true)) {
        $query .= " AND whitelist_type = ?";
        $params[] = $filters['whitelist_type'];
    }
    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_recent_licenses($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM licenses_new WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_license_details($pdo, $license_id, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM licenses_new WHERE id = ? AND user_id = ?");
    $stmt->execute([$license_id, $user_id]);
    return $stmt->fetch();
}

// Human-readable state of a license row.
function license_status($license) {
    if (!empty($license['is_revoked'])) return 'Revoked';
    if (!empty($license['is_banned'])) return 'Banned';
    if (!empty($license['valid_until']) && strtotime($license['valid_until']) < time()) return 'Expired';
    if (!empty($license['max_uses']) && $license['current_uses'] >= $license['max_uses']) return 'Used up';
    return 'Active';
}

function edit_license($pdo, $user_id, $license_id, array $data) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET description = ?, valid_until = ?, max_uses = ?, custom_tier = ?, webhook_url = ?, transferable = ?
                           WHERE id = ? AND user_id = ?");
    $stmt->execute([
        $data['description'] ?? '',
        normalize_datetime($data['valid_until'] ?? null),
        nullable_int($data['max_uses'] ?? null),
        ($data['custom_tier'] ?? '') === '' ? null : $data['custom_tier'],
        ($data['webhook_url'] ?? '') === '' ? null : $data['webhook_url'],
        !empty($data['transferable']) ? 1 : 0,
        $license_id,
        $user_id,
    ]);
    return $stmt->rowCount() > 0;
}

function renew_license($pdo, $user_id, $license_id, $new_valid_until) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET valid_until = ?, renewal_date = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([normalize_datetime($new_valid_until), $license_id, $user_id]);
    return $stmt->rowCount() > 0;
}

function revoke_license($pdo, $user_id, $license_id) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET is_revoked = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$license_id, $user_id]);
    return $stmt->rowCount() > 0;
}

function ban_license($pdo, $user_id, $license_id, $ban_reason) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET is_banned = 1, ban_reason = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$ban_reason, $license_id, $user_id]);
    return $stmt->rowCount() > 0;
}

function delete_license($pdo, $user_id, $license_id) {
    if (!get_license_details($pdo, $license_id, $user_id)) {
        return false;
    }
    // Remove rows that reference the license first (their foreign keys block the delete).
    $pdo->beginTransaction();
    try {
        foreach (['usage_logs', 'license_logs', 'license_history', 'license_custom_fields', 'license_transfers', 'license_script_versions'] as $table) {
            $pdo->prepare("DELETE FROM `$table` WHERE license_id = ?")->execute([$license_id]);
        }
        $pdo->prepare("UPDATE sellix_orders SET license_id = NULL WHERE license_id = ?")->execute([$license_id]);
        $pdo->prepare("DELETE FROM licenses_new WHERE id = ? AND user_id = ?")->execute([$license_id, $user_id]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('delete_license failed: ' . $e->getMessage());
        return false;
    }
}

function handle_bulk_action($pdo, $user_id, $license_ids, $action) {
    $count = 0;
    foreach ((array) $license_ids as $license_id) {
        $ok = match ($action) {
            'revoke' => revoke_license($pdo, $user_id, $license_id),
            'delete' => delete_license($pdo, $user_id, $license_id),
            'ban'    => ban_license($pdo, $user_id, $license_id, 'Bulk ban'),
            default  => false,
        };
        $count += $ok ? 1 : 0;
    }
    return $count;
}

function fetch_usage_stats($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT
            COUNT(*) AS total_licenses,
            COALESCE(SUM(is_revoked = 0 AND is_banned = 0 AND (valid_until IS NULL OR valid_until > NOW())), 0) AS active_licenses,
            COALESCE(SUM(current_uses), 0) AS total_uses,
            COALESCE(SUM(valid_until BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)), 0) AS expiring_soon
        FROM licenses_new WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return array_map('intval', $stmt->fetch());
}

function fetch_expiring_licenses_count($pdo, $user_id) {
    return fetch_usage_stats($pdo, $user_id)['expiring_soon'];
}

function getLicenseLogs($license_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM usage_logs WHERE license_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$license_id]);
    return $stmt->fetchAll();
}

function log_usage($license_id, $roblox_id, $place_id, $success) {
    global $pdo;
    $pdo->prepare('INSERT INTO usage_logs (license_id, roblox_id, place_id, success, created_at) VALUES (?, ?, ?, ?, NOW())')
        ->execute([$license_id, (string) $roblox_id, (string) $place_id, $success ? 1 : 0]);
}

/*
 * Check a license key from a game server.
 *   $roblox_id: the Roblox user (or group) the game belongs to
 *   $place_id:  the place the game is running in
 *   $count_use: false for periodic re-checks from an already running server
 * Returns ['valid' => bool, 'message' => string, 'license' => row|null].
 */
function validate_license_key($pdo, $license_key, $roblox_id, $place_id = null, $count_use = true) {
    $roblox_id = $roblox_id === null ? '' : (string) $roblox_id;
    $place_id = ($place_id === null || $place_id === '') ? null : (string) $place_id;

    $stmt = $pdo->prepare('SELECT * FROM licenses_new WHERE `key` = ?');
    $stmt->execute([$license_key]);
    $license = $stmt->fetch();

    if (!$license) {
        return validate_legacy_license_key($pdo, $license_key, $roblox_id, $place_id);
    }

    $fail = fn($message) => ['valid' => false, 'message' => $message, 'license' => $license];

    if ($license['is_revoked']) return $fail('License has been revoked');
    if ($license['is_banned']) return $fail('License has been banned' . ($license['ban_reason'] ? ': ' . $license['ban_reason'] : ''));
    if ($license['valid_until'] && strtotime($license['valid_until']) < time()) return $fail('License has expired');
    if ($count_use && $license['max_uses'] && $license['current_uses'] >= $license['max_uses']) return $fail('License has reached its usage limit');

    switch ($license['whitelist_type']) {
        case 'place':
            if ($place_id === null || (string) $license['whitelist_id'] !== $place_id) {
                return $fail('License is not valid for this place');
            }
            break;
        case 'group':
            if ((string) $license['whitelist_id'] !== $roblox_id) {
                return $fail('License is not valid for this group');
            }
            break;
        default: // user
            $bound_user = $license['roblox_user_id'] ?? $license['whitelist_id'];
            if ($bound_user !== null && (string) $bound_user !== $roblox_id) {
                return $fail('Invalid license key or Roblox ID');
            }
    }

    $pdo->prepare('UPDATE licenses_new SET current_uses = current_uses + ?, last_used = NOW() WHERE id = ?')
        ->execute([$count_use ? 1 : 0, $license['id']]);
    log_usage($license['id'], $roblox_id, $place_id, true);

    return ['valid' => true, 'message' => 'License is valid', 'license' => $license];
}

// Keys created before licenses_new existed live in the old `licenses` table.
function validate_legacy_license_key($pdo, $license_key, $roblox_id, $place_id) {
    $stmt = $pdo->prepare('SELECT * FROM licenses WHERE `key` = ? AND roblox_id = ?');
    $stmt->execute([$license_key, $roblox_id]);
    $license = $stmt->fetch();

    if ($license && ($place_id === null || empty($license['place_id']) || (string) $license['place_id'] === $place_id)) {
        // Not written to usage_logs: its license_id refers to licenses_new.
        return ['valid' => true, 'message' => 'License is valid', 'license' => $license];
    }
    return ['valid' => false, 'message' => 'Invalid license key or Roblox ID', 'license' => null];
}

/* ---------------------------------------------------------------------
 * Users and moderation
 * ------------------------------------------------------------------- */

function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function fetch_user_data($pdo, $user_id) {
    return get_user_by_id($user_id);
}

function getUserInfo($pdo, $user_id) {
    return get_user_by_id($user_id);
}

function fetch_api_key($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT api_key FROM api_keys WHERE user_id = ? ORDER BY id LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: '';
}

function log_moderation($pdo, $user_id, $moderator_id, $action, $reason) {
    $pdo->prepare('INSERT INTO moderation_logs (user_id, moderator_id, action, reason) VALUES (?, ?, ?, ?)')
        ->execute([$user_id, $moderator_id, $action, $reason]);
}

function suspendUser($pdo, $user_id, $moderator_id, $reason) {
    $pdo->prepare('UPDATE users SET is_suspended = 1, suspend_reason = ? WHERE id = ?')->execute([$reason, $user_id]);
    log_moderation($pdo, $user_id, $moderator_id, 'suspend', $reason);
}

function banUser($pdo, $user_id, $moderator_id, $reason) {
    $pdo->prepare('UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?')->execute([$reason, $user_id]);
    log_moderation($pdo, $user_id, $moderator_id, 'ban', $reason);
}

function unbanUser($pdo, $user_id, $moderator_id) {
    $pdo->prepare('UPDATE users SET is_banned = 0, ban_reason = NULL, is_suspended = 0, suspend_reason = NULL WHERE id = ?')->execute([$user_id]);
    log_moderation($pdo, $user_id, $moderator_id, 'unban', '');
}

/* ---------------------------------------------------------------------
 * Notifications and activity
 * ------------------------------------------------------------------- */

function add_notification($user_id, $message) {
    global $pdo;
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$user_id, $message]);
}

function get_notifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function get_unread_notifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function mark_notification_as_read($notification_id, $user_id) {
    global $pdo;
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$notification_id, $user_id]);
}

function send_license_purchase_notification($user_id, $license_key) {
    add_notification($user_id, "A license was purchased through Sellix: $license_key");
}

function log_user_activity($user_id, $action) {
    global $pdo;
    $pdo->prepare("INSERT INTO user_activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())")->execute([$user_id, $action]);
}

function log_user_action($pdo, $user_id, $action) {
    log_user_activity($user_id, $action);
}

function fetch_recent_user_activities($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT action, timestamp FROM user_activity_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_user_activities($pdo, $user_id, $limit = 5) {
    return fetch_recent_user_activities($pdo, $user_id, $limit);
}

function fetch_anomaly_alerts($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM anomaly_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_active_users_count($pdo) {
    return $pdo->query("SELECT COUNT(DISTINCT user_id) FROM login_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
}

function fetch_license_usage_data($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT DATE(u.created_at) AS date, COUNT(*) AS count
        FROM usage_logs u JOIN licenses_new l ON l.id = u.license_id
        WHERE l.user_id = ? GROUP BY DATE(u.created_at) ORDER BY date DESC LIMIT 7");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/* ---------------------------------------------------------------------
 * Billing
 * ------------------------------------------------------------------- */

function get_user_trial_info($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT trial_start_date, trial_end_date, trial_credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_user_subscription_info($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT subscription_tier, subscription_end_date FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_user_credits($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

function fetch_credit_usage($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(usage_date, '%Y-%m') AS month, SUM(credits_used) AS credits FROM credit_usage_history WHERE user_id = ? GROUP BY month ORDER BY month DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function fetch_feature_usage($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT feature_name AS feature, SUM(credits_used) AS credits FROM feature_usage WHERE user_id = ? GROUP BY feature_name ORDER BY credits DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function check_free_trial_status($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT trial_credits, trial_start_date, DATEDIFF(NOW(), trial_start_date) AS days_since_start
                           FROM users WHERE id = ? AND trial_credits > 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/* ---------------------------------------------------------------------
 * Custom tiers and Sellix
 * ------------------------------------------------------------------- */

function fetch_custom_tiers($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM custom_tiers WHERE user_id = ? ORDER BY tier_name");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function create_custom_tier($pdo, $user_id, $tier_name, $tier_benefits) {
    return $pdo->prepare("INSERT INTO custom_tiers (user_id, tier_name, tier_benefits) VALUES (?, ?, ?)")
        ->execute([$user_id, $tier_name, $tier_benefits]);
}

function get_custom_tier_name($pdo, $tier_id) {
    if (!$tier_id) return '';
    $stmt = $pdo->prepare("SELECT tier_name FROM custom_tiers WHERE id = ?");
    $stmt->execute([$tier_id]);
    return $stmt->fetchColumn() ?: '';
}

function get_sellix_products($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM sellix_products WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/* ---------------------------------------------------------------------
 * Script obfuscation (luaobfuscator.com)
 * ------------------------------------------------------------------- */

// Obfuscate a Lua script. Returns ['code' => string] or ['error' => string].
// Charges one credit only when obfuscation succeeds.
function obfuscate_lua_script($pdo, $user_id, $script, array $options) {
    if (LUAOBFUSCATOR_API_KEY === '') {
        return ['error' => 'Obfuscation is not configured on this server.'];
    }
    if (get_user_credits($user_id) < 1) {
        return ['error' => 'Insufficient credits to perform obfuscation.'];
    }

    $call = function ($endpoint, $body, array $headers) {
        $ch = curl_init('https://api.luaobfuscator.com/v1/obfuscator/' . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array_merge(['apikey: ' . LUAOBFUSCATOR_API_KEY], $headers),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response === false ? null : json_decode($response, true);
    };

    // Step 1 uploads the script, step 2 obfuscates it with the chosen options.
    $session = $call('newscript', $script, ['Content-Type: text/plain']);
    if (empty($session['sessionId'])) {
        return ['error' => $session['message'] ?? 'Failed to upload the script for obfuscation.'];
    }
    $allowed = ['Virtualize', 'Constants', 'Minify', 'EncryptStrings'];
    $flags = [];
    foreach ($allowed as $name) {
        $flags[$name] = in_array($name, $options, true) || !empty($options[$name]);
    }
    $result = $call('obfuscate', json_encode($flags), ['Content-Type: application/json', 'sessionId: ' . $session['sessionId']]);
    if (empty($result['code'])) {
        return ['error' => $result['message'] ?? 'Failed to obfuscate the script. Please try again.'];
    }

    $charge = $pdo->prepare("UPDATE users SET credits = credits - 1 WHERE id = ? AND credits >= 1");
    $charge->execute([$user_id]);
    if ($charge->rowCount() === 0) {
        return ['error' => 'Insufficient credits to perform obfuscation.'];
    }
    $pdo->prepare("INSERT INTO obfuscations (user_id, script, obfuscated_script) VALUES (?, ?, ?)")->execute([$user_id, $script, $result['code']]);
    $pdo->prepare("INSERT INTO feature_usage (user_id, feature_name, credits_used, usage_date) VALUES (?, 'obfuscation', 1, NOW())")->execute([$user_id]);

    return ['code' => $result['code'], 'id' => (int) $pdo->lastInsertId()];
}

/* ---------------------------------------------------------------------
 * Script version control (user hub)
 * ------------------------------------------------------------------- */

function getUserScriptsWithVersions($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.active_version_id, v.id AS version_id, v.name AS version_name
        FROM scripts s
        LEFT JOIN script_versions v ON s.id = v.script_id
        WHERE s.user_id = ?
        ORDER BY s.id, v.created_at DESC");
    $stmt->execute([$user_id]);

    $scripts = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!isset($scripts[$row['id']])) {
            $scripts[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'active_version_id' => $row['active_version_id'],
                'active_version_name' => null,
                'versions' => [],
            ];
        }
        if ($row['version_id'] !== null) {
            $scripts[$row['id']]['versions'][] = ['id' => $row['version_id'], 'name' => $row['version_name']];
            if ($row['version_id'] == $row['active_version_id']) {
                $scripts[$row['id']]['active_version_name'] = $row['version_name'];
            }
        }
    }
    return array_values($scripts);
}

function createNewScript($pdo, $user_id, $script_name, $script_content) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO scripts (user_id, name) VALUES (?, ?)")->execute([$user_id, $script_name]);
        $script_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO script_versions (script_id, name, content) VALUES (?, 'v1.0', ?)")->execute([$script_id, $script_content]);
        $version_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE scripts SET active_version_id = ? WHERE id = ?")->execute([$version_id, $script_id]);
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

function createNewScriptVersion($pdo, $user_id, $script_id, $version_name, $version_content) {
    $stmt = $pdo->prepare("SELECT id FROM scripts WHERE id = ? AND user_id = ?");
    $stmt->execute([$script_id, $user_id]);
    if (!$stmt->fetch()) {
        return false;
    }
    return $pdo->prepare("INSERT INTO script_versions (script_id, name, content) VALUES (?, ?, ?)")
        ->execute([$script_id, $version_name, $version_content]);
}

function updateActiveScriptVersion($pdo, $user_id, $script_id, $version_id) {
    $stmt = $pdo->prepare("
        UPDATE scripts s
        JOIN script_versions v ON v.id = ? AND v.script_id = s.id
        SET s.active_version_id = ?
        WHERE s.id = ? AND s.user_id = ?");
    return $stmt->execute([$version_id, $version_id, $script_id, $user_id]);
}
