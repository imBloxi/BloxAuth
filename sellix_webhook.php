<?php
require '../includes/db.php';

$payload = file_get_contents('php://input');
$secret = 'YOUR_SELLIX_WEBHOOK_SECRET'; // Replace with your actual secret
$header_signature = $_SERVER['HTTP_X_SELLIX_SIGNATURE'];

$signature = hash_hmac('sha512', $payload, $secret);

if (hash_equals($signature, $header_signature)) {
    $data = json_decode($payload, true);
    
    if ($data['event'] == 'order:paid') {
        $order = $data['data']['order'];
        $product = $data['data']['product'];
        
        // Determine tier based on product
        $tier = $product['title'] === 'Tier 1' ? 'Tier 1' : 'Tier 2';
        $features = $tier === 'Tier 1' ? 'Unlimited licenses' : 'Unlimited licenses, priority support, advanced analytics';
        
        // Update user's subscription
        $stmt = $pdo->prepare("UPDATE users SET subscription_tier = ?, subscription_end_date = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE email = ?");
        $stmt->execute([$tier, $order['customer_email']]);
        
        // Log the transaction
        $stmt = $pdo->prepare("INSERT INTO subscription_transactions (user_id, tier_id, amount, transaction_date, status) VALUES ((SELECT id FROM users WHERE email = ?), ?, ?, NOW(), 'success')");
        $stmt->execute([$order['customer_email'], $tier === 'Tier 1' ? 1 : 2, $order['total']]);
    }
    
    http_response_code(200);
} else {
    http_response_code(400);
}
