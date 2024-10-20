<?php
// Check if the function is already defined
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('redirect_if_not_logged_in')) {
    function redirect_if_not_logged_in() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../login.php');
            exit();
        }
    }
}

function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
// Add a new notification
function add_notification($user_id, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}
// Add a transaction
function add_transaction($user_id, $amount, $type, $description) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)');
    return $stmt->execute([$user_id, $amount, $type, $description]);
}

// Get transaction history for a user
function get_transaction_history($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
// Get all notifications for a user
function get_notifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Mark a notification as read
function mark_notification_as_read($notification_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
    $stmt->execute([$notification_id]);
}

function log_usage($license_id, $roblox_id, $place_id, $success) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO usage_logs (license_id, roblox_id, place_id, success, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$license_id, $roblox_id, $place_id, $success, date('Y-m-d H:i:s')]);
}

function hash_email($email) {
    return password_hash($email, PASSWORD_DEFAULT);
}

function generateLicenseKey() {
    $segments = [
        bin2hex(random_bytes(1)), // xxx
        bin2hex(random_bytes(2)), // xxxx
        bin2hex(random_bytes(1)), // xxx
        bin2hex(random_bytes(3)), // xxxxxx
        bin2hex(random_bytes(1)), // x
        bin2hex(random_bytes(2))  // xxxx
    ];
    return implode('-', $segments);
}

function create_license($pdo, $user_id, $data) {
    // Implementation for creating a new license
    // Use $data to get the form inputs
}

if (!function_exists('delete_license')) {
    function delete_license($pdo, $user_id, $license_id) {
        // Implementation for deleting a license
    }
}

if (!function_exists('ban_license')) {
    function ban_license($pdo, $user_id, $license_id, $ban_reason) {
        // Implementation for banning a license
    }
}

if (!function_exists('handle_bulk_action')) {
    function handle_bulk_action($pdo, $user_id, $license_ids, $action) {
        // Implementation for handling bulk actions on licenses
    }
}

function fetch_licenses($pdo, $user_id, $filters = []) {
    $query = "SELECT * FROM licenses_new WHERE user_id = :user_id";
    $params = [':user_id' => $user_id];

    // Add filtering logic here based on $filters

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_api_key($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT api_key FROM api_keys WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function fetch_user_data($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetch_recent_licenses($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM licenses_new WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ... (existing functions)

function fetch_expiring_licenses_count($pdo, $user_id) {
    $query = "SELECT COUNT(*) FROM licenses_new 
              WHERE user_id = :user_id 
              AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchColumn();
}

function fetch_active_users_count($pdo) {
    $query = "SELECT COUNT(DISTINCT user_id) FROM login_logs 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function fetch_average_session_duration($pdo) {
    // This is a placeholder. You'll need to implement session tracking to get accurate data.
    return 15; // Return a default value of 15 minutes for now
}

function fetch_recent_user_activities($pdo, $user_id, $limit = 5) {
    $query = "SELECT action, timestamp FROM user_activity_logs 
              WHERE user_id = :user_id 
              ORDER BY timestamp DESC 
              LIMIT :limit";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_license_usage_data($pdo, $user_id) {
    $query = "SELECT DATE(created_at) as date, COUNT(*) as count 
              FROM license_logs 
              WHERE license_id IN (SELECT id FROM licenses_new WHERE user_id = :user_id) 
              GROUP BY DATE(created_at) 
              ORDER BY date DESC 
              LIMIT 7";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_credit_usage($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(usage_date, '%Y-%m') as month, SUM(credits_used) as credits FROM credit_usage_history WHERE user_id = ? GROUP BY month ORDER BY month DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_feature_usage($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT feature_name as feature, SUM(credits_used) as credits FROM feature_usage WHERE user_id = ? GROUP BY feature ORDER BY credits DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function check_free_trial_status($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            trial_credits, 
            trial_start_date, 
            DATEDIFF(NOW(), trial_start_date) AS days_since_start
        FROM 
            users
        WHERE 
            id = :user_id 
            AND trial_credits > 0
    ");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function fetch_usage_stats($pdo, $user_id) {
    // Implementation for fetching usage statistics
    // This is a placeholder and should be implemented based on your specific needs
    return [
        'total_uses' => 0,
        'active_licenses' => 0,
        // Add more statistics as needed
    ];
}

function fetch_anomaly_alerts($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM anomaly_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_user_activities($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM user_activity_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ensure_admin() {
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        header('Location: ../auth/login.php');
        exit();
    }
}

if (!function_exists('generate_unique_license_key')) {
    function generate_unique_license_key($pdo) {
        do {
            $license_key = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE license_key = ?");
            $stmt->execute([$license_key]);
            $count = $stmt->fetchColumn();
        } while ($count > 0);

        return $license_key;
    }
}

// Add more helper functions as needed

/*
function save_license($pdo, $user_id, $license_data) {
    $stmt = $pdo->prepare("INSERT INTO licenses_new (user_id, license_key, type, expiration_date, api_key, webhook_url, custom_domain) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $user_id,
        $license_data['license_key'],
        $license_data['type'],
        $license_data['expiration_date'],
        $license_data['api_key'],
        $license_data['webhook_url'],
        $license_data['custom_domain']
    ]);
}
*/

function check_user_ban_status($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT is_banned, ban_reason FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user['is_banned']) {
        $_SESSION['ban_reason'] = $user['ban_reason'];
        header('Location: not-approved/index.php');
        exit();
    }
}

function save_license($pdo, $user_id, $license_data) {
    $stmt = $pdo->prepare("INSERT INTO licenses_new (user_id, `key`, whitelist_id, whitelist_type, description, valid_until, roblox_user_id, max_uses, transferable, custom_tier) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $user_id,
        $license_data['key'],
        $license_data['whitelist_id'],
        $license_data['whitelist_type'],
        $license_data['description'],
        $license_data['valid_until'],
        $license_data['roblox_user_id'],
        $license_data['max_uses'],
        $license_data['is_transferable'],
        $license_data['custom_tier']
    ]);
}

function get_sellix_products($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM sellix_products WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_unread_notifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserScriptsWithVersions($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.active_version_id,
               v.id AS version_id, v.name AS version_name
        FROM scripts s
        LEFT JOIN script_versions v ON s.id = v.script_id
        WHERE s.user_id = ?
        ORDER BY s.id, v.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $scripts = [];
    foreach ($results as $row) {
        if (!isset($scripts[$row['id']])) {
            $scripts[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'active_version_id' => $row['active_version_id'],
                'versions' => []
            ];
        }
        $scripts[$row['id']]['versions'][] = [
            'id' => $row['version_id'],
            'name' => $row['version_name']
        ];
        if ($row['version_id'] == $row['active_version_id']) {
            $scripts[$row['id']]['active_version_name'] = $row['version_name'];
        }
    }
    return array_values($scripts);
}

function createNewScript($pdo, $user_id, $script_name, $script_content) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO scripts (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $script_name]);
        $script_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO script_versions (script_id, name, content) VALUES (?, 'v1.0', ?)");
        $stmt->execute([$script_id, $script_content]);
        $version_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE scripts SET active_version_id = ? WHERE id = ?");
        $stmt->execute([$version_id, $script_id]);

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
        return false; // Script doesn't exist or doesn't belong to user
    }

    $stmt = $pdo->prepare("INSERT INTO script_versions (script_id, name, content) VALUES (?, ?, ?)");
    return $stmt->execute([$script_id, $version_name, $version_content]);
}

function updateActiveScriptVersion($pdo, $user_id, $script_id, $version_id) {
    $stmt = $pdo->prepare("
        UPDATE scripts s
        JOIN script_versions v ON v.id = ? AND v.script_id = s.id
        SET s.active_version_id = ?
        WHERE s.id = ? AND s.user_id = ?
    ");
    return $stmt->execute([$version_id, $version_id, $script_id, $user_id]);
}

?>
