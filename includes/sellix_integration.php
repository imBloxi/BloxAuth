<?php
// Sellix store integration: create products and turn paid orders into licenses.
// Requires includes/config.php to be loaded first.

// Create a product in the user's Sellix store and remember it locally.
function create_sellix_product($user_id, $license_type, $custom_tier_id, $price, $currency) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT sellix_api_key, app_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (empty($user['sellix_api_key'])) {
        return ['status' => 'error', 'message' => 'Add your Sellix API key in Settings first.'];
    }

    $title = trim(($user['app_name'] ?: APP_NAME) . ' ' . $license_type . ' license');
    $ch = curl_init('https://dev.sellix.io/v1/products');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => json_encode([
            'title' => $title,
            'price' => (float) $price,
            'currency' => $currency,
            'type' => 'SERVICE',
            'description' => "License key for $title, delivered by " . APP_NAME . '.',
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $user['sellix_api_key'],
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode((string) $response, true);
    if ($http_code !== 200 || empty($result['data']['uniqid'])) {
        return ['status' => 'error', 'message' => $result['error'] ?? 'Sellix did not accept the product.'];
    }

    $pdo->prepare("INSERT INTO sellix_products (user_id, sellix_product_id, title, license_type, custom_tier_id, price, currency) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$user_id, $result['data']['uniqid'], $title, $license_type, $custom_tier_id ?: null, $price, $currency]);

    return ['status' => 'success', 'message' => 'Sellix product created successfully.'];
}

// Handle a Sellix order webhook. Accepts both {event, data: {order}} and a bare order payload.
function handle_sellix_webhook($pdo, $payload, array $data) {
    $order = $data['data'] ?? $data;
    $product_id = $order['product_id'] ?? $order['product']['uniqid'] ?? null;
    $order_id = $order['uniqid'] ?? null;

    $stmt = $pdo->prepare("SELECT p.*, u.sellix_webhook_secret FROM sellix_products p JOIN users u ON u.id = p.user_id WHERE p.sellix_product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product || !$order_id) {
        http_response_code(404);
        exit('Product not found');
    }

    $signature = $_SERVER['HTTP_X_SELLIX_SIGNATURE'] ?? '';
    if (empty($product['sellix_webhook_secret']) || !hash_equals(hash_hmac('sha512', $payload, $product['sellix_webhook_secret']), $signature)) {
        http_response_code(401);
        exit('Invalid signature');
    }

    $status = strtolower((string) ($order['status'] ?? ''));
    if (($data['event'] ?? '') === 'order:paid') {
        $status = 'completed';
    }

    $pdo->prepare("INSERT INTO sellix_orders (user_id, sellix_order_id, sellix_product_id, status) VALUES (?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE status = VALUES(status)")
        ->execute([$product['user_id'], $order_id, $product['sellix_product_id'], $status]);

    // Issue one license per order, even if Sellix retries the webhook.
    $stmt = $pdo->prepare("SELECT license_id FROM sellix_orders WHERE sellix_order_id = ?");
    $stmt->execute([$order_id]);
    $already_issued = $stmt->fetchColumn();

    if ($status === 'completed' && !$already_issued) {
        $result = create_license($pdo, $product['user_id'], [
            'whitelist_type' => in_array($product['license_type'], ['user', 'group', 'place'], true) ? $product['license_type'] : 'user',
            'description' => "License purchased through Sellix - Order ID: $order_id",
            'valid_until' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'custom_tier' => get_custom_tier_name($pdo, $product['custom_tier_id']),
        ]);

        if ($result['success']) {
            $pdo->prepare("UPDATE sellix_orders SET license_id = ? WHERE sellix_order_id = ?")->execute([$result['license_id'], $order_id]);
            send_license_purchase_notification($product['user_id'], $result['license_key']);
        }
    }

    http_response_code(200);
    echo 'Webhook processed successfully';
}
