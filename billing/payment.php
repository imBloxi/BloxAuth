<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

if (isset($_POST['purchase_gamepass'])) {
    $stmt = $pdo->prepare("SELECT gamepass_claimed FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $gamepass_claimed = $stmt->fetchColumn();

    if ($gamepass_claimed) {
        $_SESSION['message'] = 'You have already claimed the game pass.';
    } else {
        $roblox_user_id = get_roblox_user_id($user_id);

        if ($roblox_user_id) {
            // Replace with your specific Game Pass ID
            $gamepass_id = '25106617';

            // Roblox Game Pass Verification API URL
            $api_url = "https://inventory.roblox.com/v1/users/$roblox_user_id/items/GamePass/$gamepass_id";

            $response = @file_get_contents($api_url);
            if ($response === FALSE) {
                $_SESSION['message'] = 'Error verifying game pass ownership. Please try again later.';
            } else {
                $response_data = json_decode($response, true);

                if (isset($response_data['data']) && is_array($response_data['data']) && !empty($response_data['data'])) {
                    // Add 500 credits to the user
                    $stmt = $pdo->prepare("UPDATE users SET credits = credits + 500, gamepass_claimed = 1 WHERE id = ?");
                    $stmt->execute([$user_id]);

                    $_SESSION['message'] = 'Purchase successful! 500 credits have been added to your account.';
                } else {
                    $_SESSION['message'] = 'You do not own the required game pass.';
                }
            }
        } else {
            $_SESSION['message'] = 'Roblox account not linked.';
        }
    }
}

header('Location: index.php');
exit;

function get_roblox_user_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM roblox_accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>
