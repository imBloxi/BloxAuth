<?php
// Staff only: confirm a pending credit payment and add the credits.
require_once '../includes/config.php';
ensure_admin();

function confirm_payment($transaction_id) {
    global $pdo;
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, user_id, credits FROM payments WHERE transaction_id = ? AND status = 'pending' FOR UPDATE");
    $stmt->execute([$transaction_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        $pdo->rollBack();
        return false;
    }
    $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?")->execute([$payment['credits'], $payment['user_id']]);
    $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?")->execute([$payment['id']]);
    $pdo->commit();
    return true;
}

if (isset($_POST['transaction_id'])) {
    echo confirm_payment($_POST['transaction_id']) ? "Payment confirmed and credits added." : "Payment confirmation failed.";
}
?>
