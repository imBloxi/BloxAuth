<?php
// Sellix webhook for BloxAuth's own subscription sales (site-wide secret).
require_once 'includes/config.php';

$payload = file_get_contents('php://input');
$header_signature = $_SERVER['HTTP_X_SELLIX_SIGNATURE'] ?? '';

if (SELLIX_WEBHOOK_SECRET === '' || !hash_equals(hash_hmac('sha512', $payload, SELLIX_WEBHOOK_SECRET), $header_signature)) {
    http_response_code(400);
    exit('Invalid signature');
}

$data = json_decode($payload, true);

if (($data['event'] ?? '') === 'order:paid') {
    $order = $data['data']['order'] ?? [];
    $product = $data['data']['product'] ?? [];

    $tier = ($product['title'] ?? '') === 'Tier 1' ? 'Tier 1' : 'Tier 2';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$order['customer_email'] ?? '']);
    $user_id = $stmt->fetchColumn();

    if ($user_id) {
        $pdo->prepare("UPDATE users SET subscription_tier = ?, subscription_end_date = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE id = ?")
            ->execute([$tier, $user_id]);
        $pdo->prepare("INSERT INTO subscription_transactions (user_id, tier_id, amount, transaction_date, status) VALUES (?, ?, ?, NOW(), 'success')")
            ->execute([$user_id, $tier === 'Tier 1' ? 1 : 2, $order['total'] ?? 0]);
    }
}

http_response_code(200);
