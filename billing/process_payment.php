<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_credits'])) {
    $additional_credits = (int)$_POST['credits'];
    if ($additional_credits > 0) {
        // Generate a unique transaction ID for payment
        $transaction_id = uniqid("tx_", true);
        
        // Save the payment request in the database
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, credits, transaction_id, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $additional_credits, $transaction_id]);
        
        // Redirect to the payment page
        header('Location: payment.php?transaction_id=' . urlencode($transaction_id) . '&credits=' . $additional_credits);
        exit();
    } else {
        $_SESSION['message'] = 'Invalid credits amount.';
    }
    header('Location: index.php');
    exit();
}
?>
