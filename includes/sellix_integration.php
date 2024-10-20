<?php
require_once 'db.php';
require_once 'functions.php';

function create_sellix_product($api_key, $product_name, $product_price, $license_type, $custom_tier_id) {
    $url = 'https://dev.sellix.io/v1/products';
    $data = [
        'title' => $product_name,
        'price' => $product_price,
        'type' => 'serials',
        'custom_fields' => [
            [
                'name' => 'license_type',
                'value' => $license_type
            ],
            [
                'name' => 'custom_tier_id',
                'value' => $custom_tier_id
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code === 200 && isset($result['data']['uniqid'])) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO sellix_products (user_id, sellix_product_id, title, price, license_type, custom_tier_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $result['data']['uniqid'], $product_name, $product_price, $license_type, $custom_tier_id]);
        return ['status' => 'success', 'message' => 'Product created successfully'];
    } else {
        return ['status' => 'error', 'message' => $result['error'] ?? 'Unknown error occurred'];
    }
}

function get_sellix_products($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM sellix_products WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function handle_sellix_webhook($data, $webhook_secret) {
    // Verify webhook signature
    $signature = hash_hmac('sha512', file_get_contents('php://input'), $webhook_secret);
    if ($signature !== $_SERVER['HTTP_X_SELLIX_SIGNATURE']) {
        http_response_code(401);
        exit('Invalid signature');
    }

    $order_id = $data['uniqid'];
    $status = $data['status'];

    // Find the corresponding Sellix product
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM sellix_products WHERE sellix_product_id = ?");
    $stmt->execute([$data['product']['uniqid']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        exit('Product not found');
    }

    // Create or update the order
    $stmt = $pdo->prepare("INSERT INTO sellix_orders (user_id, sellix_order_id, sellix_product_id, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
    $stmt->execute([$product['user_id'], $order_id, $product['sellix_product_id'], $status, $status]);

    if ($status === 'completed') {
        // Generate and assign the license
        $license_data = [
            'key' => generate_unique_license_key($pdo),
            'whitelist_type' => 'user', // Default to user, can be updated later
            'description' => "License purchased through Sellix - Order ID: $order_id",
            'valid_until' => date('Y-m-d H:i:s', strtotime('+1 year')), // Set expiration to 1 year from now
            'custom_tier' => $product['custom_tier_id']
        ];

        if (save_license($pdo, $product['user_id'], $license_data)) {
            $license_id = $pdo->lastInsertId();
            
            // Update the Sellix order with the license ID
            $stmt = $pdo->prepare("UPDATE sellix_orders SET license_id = ? WHERE sellix_order_id = ?");
            $stmt->execute([$license_id, $order_id]);

            // Send notification to the user
            send_license_purchase_notification($product['user_id'], $license_data['key']);
        }
    }

    http_response_code(200);
    echo 'Webhook processed successfully';
}
