<?php
require '../includes/db.php';

// Function to manually confirm payment
function confirm_payment($transaction_id) {
    global $pdo;
    
    // Fetch the pending payment
    $stmt = $pdo->prepare("SELECT id, user_id, credits, status FROM payments WHERE transaction_id = ? AND status = 'pending'");
    $stmt->execute([$transaction_id]);
    $payment = $stmt->fetch();

    if ($payment) {
        $credits = $payment['credits'];
        $user_id = $payment['user_id'];
        
        // Assume manual confirmation is successful
        // In a real implementation, you would check the actual Robux transaction
        
        // Update user credits
        $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        $stmt->execute([$credits, $user_id]);

        // Mark payment as completed
        $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$payment['id']]);
        
        return true;
    }
    
    return false;
}

// Example of manually confirming a payment
if (isset($_GET['transaction_id'])) {
    $transaction_id = $_GET['transaction_id'];
    if (confirm_payment($transaction_id)) {
        echo "Payment confirmed and credits added.";
    } else {
        echo "Payment confirmation failed.";
    }
}
?>
