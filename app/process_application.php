<?php
session_start();
require '../includes/db.php';

// Function to check if the user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if the user is a staff member
function is_staff($pdo) {
    if (!is_logged_in()) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT is_staff FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user && $user['is_staff'];
}

// Check if the user is logged in and is a staff member
if (!is_staff($pdo)) {
    header('Location: ../auth/login.php');
    exit;
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $action = $_POST['action'];

    try {
        if ($action === 'accept') {
            // Unban the user by setting is_banned to 0 and clearing the ban_reason
            $stmt = $pdo->prepare('UPDATE users SET is_banned = 0, ban_reason = NULL WHERE username = ?');
            $stmt = $pdo->prepare('DELETE FROM applications WHERE username = ?');
            $stmt->execute([$username]);
        } elseif ($action === 'reject') {
            // Delete the application
            $stmt = $pdo->prepare('DELETE FROM applications WHERE username = ?');
            $stmt->execute([$username]);
        }

        // Redirect back to the review applications page
        header('Location: review_applications.php');
        exit;
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'General error: ' . $e->getMessage();
    }
}
?>
