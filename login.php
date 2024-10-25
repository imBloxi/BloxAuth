<?php
// ... existing login code ...

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    
    // Log the login session
    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, login_time) VALUES (?, NOW())");
    $stmt->execute([$user['id']]);
    $_SESSION['session_id'] = $pdo->lastInsertId();

    header('Location: dashboard.php');
    exit();
}

// ... rest of the login code ...
