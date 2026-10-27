<?php
// Claim credits for owning the BloxAuth game pass on Roblox (once per account).
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_gamepass'])) {
    $stmt = $pdo->prepare("SELECT u.gamepass_claimed, r.roblox_user_id, r.verified
                           FROM users u LEFT JOIN roblox_accounts r ON r.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();

    // Has another BloxAuth account already claimed with this Roblox account?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM roblox_accounts r JOIN users u ON u.id = r.user_id
                           WHERE r.roblox_user_id = ? AND u.gamepass_claimed = 1 AND u.id <> ?");
    $stmt->execute([$account['roblox_user_id'], $user_id]);
    $claimed_elsewhere = $stmt->fetchColumn() > 0;

    if ($account['gamepass_claimed']) {
        $_SESSION['message'] = 'You have already claimed the game pass.';
    } elseif (!$account['roblox_user_id'] || !$account['verified']) {
        $_SESSION['message'] = 'Link your Roblox account with "Link with Roblox" first.';
    } elseif ($claimed_elsewhere) {
        $_SESSION['message'] = 'This Roblox account has already been used to claim the game pass.';
    } else {
        $owned = http_get_json('https://inventory.roblox.com/v1/users/' . (int) $account['roblox_user_id'] . '/items/GamePass/' . (int) ROBLOX_GAMEPASS_ID);
        if ($owned === null) {
            $_SESSION['message'] = 'Error verifying game pass ownership. Please try again later.';
        } elseif (!empty($owned['data'])) {
            $claim = $pdo->prepare("UPDATE users SET credits = credits + 500, gamepass_claimed = 1 WHERE id = ? AND gamepass_claimed = 0");
            $claim->execute([$user_id]);
            if ($claim->rowCount() > 0) {
                $pdo->prepare("INSERT INTO credit_purchases (user_id, amount, cost) VALUES (?, 500, 0)")->execute([$user_id]);
            }
            $_SESSION['message'] = 'Purchase successful! 500 credits have been added to your account.';
        } else {
            $_SESSION['message'] = 'You do not own the required game pass.';
        }
    }
}

redirect('/billing/index.php');
