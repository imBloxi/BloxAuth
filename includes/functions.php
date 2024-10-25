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
function fetch_license_types($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM license_types ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

if (!function_exists('create_license')) {
    function create_license($pdo, $license_data) {
        try {
            $pdo->beginTransaction();
            $license_key = generate_unique_license_key($pdo);
            $stmt = $pdo->prepare("INSERT INTO licenses_new (user_id, `key`, whitelist_id, whitelist_type, description, valid_until, roblox_user_id, max_uses, transferable, custom_tier) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $license_data['user_id'],
                $license_key,
                $license_data['whitelist_id'],
                $license_data['whitelist_type'],
                $license_data['description'],
                $license_data['valid_until'],
                $license_data['roblox_user_id'],
                $license_data['max_uses'],
                $license_data['transferable'],
                $license_data['custom_tier']
            ]);
            $pdo->commit();
            return ['success' => true, 'license_key' => $license_key];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('fetch_licenses')) {
    function fetch_licenses($pdo, $user_id) {
        $stmt = $pdo->prepare("SELECT l.*, lt.name as license_type FROM licenses l JOIN license_types lt ON l.license_type_id = lt.id WHERE l.user_id = ? ORDER BY l.created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

// Ensure the detailed fetch_licenses function is also conditionally declared
if (!function_exists('fetch_licenses')) {
    function fetch_licenses($pdo, $user_id, $filters = []) {
        $query = "SELECT * FROM licenses_new WHERE user_id = :user_id";
        $params = [':user_id' => $user_id];

        // Add filtering logic here based on $filters

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, login_time, IFNULL(logout_time, NOW()))) AS avg_duration
              FROM user_sessions
              WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['avg_duration'] !== null) {
        return round($result['avg_duration'], 2);
    } else {
        return 0; // Return 0 if no data
    }
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

// Renaming the second create_license function to create_detailed_license
function create_detailed_license($pdo, $user_id, $data) {
    // Implementation for creating a new license with detailed data
    // Use $data to get the form inputs
    // Ensure this function's implementation is correct as per your requirements
}

// Check and adjust the function that handles license editing
// Assuming a function exists, ensure it is correctly updating the database
function edit_license($pdo, $license_id, $updated_data) {
    // Example implementation, adjust as necessary
    $stmt = $pdo->prepare("UPDATE licenses_new SET description = ?, valid_until = ? WHERE id = ?");
    $stmt->execute([$updated_data['description'], $updated_data['valid_until'], $license_id]);
    return $stmt->rowCount() > 0;
}

// Function to fetch license details for editing
function get_license_details($pdo, $license_id) {
    $stmt = $pdo->prepare("SELECT * FROM licenses_new WHERE id = ?");
    $stmt->execute([$license_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update license details
function update_license($pdo, $license_id, $license_data) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET whitelist_id = ?, whitelist_type = ?, description = ?, valid_until = ?, roblox_user_id = ?, max_uses = ?, transferable = ?, custom_tier = ? WHERE id = ?");
    $result = $stmt->execute([
        $license_data['whitelist_id'],
        $license_data['whitelist_type'],
        $license_data['description'],
        $license_data['valid_until'],
        $license_data['roblox_user_id'],
        $license_data['max_uses'],
        $license_data['transferable'],
        $license_data['custom_tier'],
        $license_id
    ]);
    return $result;
}

// Function to revoke a license
function revoke_license($pdo, $license_id) {
    $stmt = $pdo->prepare("UPDATE licenses_new SET is_revoked = 1 WHERE id = ?");
    return $stmt->execute([$license_id]);
}

// Ensure all your form handling in license.php uses these functions correctly

// Ensure this file contains the following function or add it if missing

function log_user_action($pdo, $user_id, $action) {
    // Implementation depends on how you want to log user actions, e.g., writing to a database
    $stmt = $pdo->prepare("INSERT INTO user_actions (user_id, action) VALUES (?, ?)");
    $stmt->execute([$user_id, $action]);
}

function log_user_activity($user_id, $activity) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity, timestamp) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $activity]);
}

function detect_anomalies($user_id) {
    global $pdo;
    // Fetch recent activities
    $stmt = $pdo->prepare("SELECT activity FROM user_activity_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 100");
    $stmt->execute([$user_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Simple anomaly detection logic
    $frequent_activities = array_count_values($activities);
    foreach ($frequent_activities as $activity => $count) {
        if ($count < 2) { // If an activity occurs less frequently, it might be an anomaly
            alert_admin("Possible anomaly detected for user $user_id: $activity");
        }
    }
}

function alert_admin($message) {
    // Logic to alert the system administrator
    error_log($message); // Simple logging, replace with more sophisticated alerting mechanism
}

function run_ml_model($user_id) {
    $command = escapeshellcmd("python detect_fraud.py $user_id");
    $output = shell_exec($command);
    return json_decode($output, true);
}

?>
