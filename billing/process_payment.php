<?php
// Start a credit purchase. Payments are confirmed by staff in verify_payment.php.
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_credits'])) {
    $additional_credits = (int) ($_POST['credits'] ?? 0);
    if ($additional_credits > 0 && $additional_credits <= 100000) {
        $transaction_id = uniqid('tx_', true);
        $pdo->prepare("INSERT INTO payments (user_id, credits, amount, currency, transaction_id, status) VALUES (?, ?, 0, 'ROBUX', ?, 'pending')")
            ->execute([$user_id, $additional_credits, $transaction_id]);
        $_SESSION['message'] = "Payment request $transaction_id created. Credits are added once staff confirm the payment.";
    } else {
        $_SESSION['message'] = 'Invalid credits amount.';
    }
}
redirect('/billing/index.php');

