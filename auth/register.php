<?php
require_once '../includes/config.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check registration rate limit
    if (!check_rate_limit('registration', $_SERVER['REMOTE_ADDR'], MAX_REGISTRATION_ATTEMPTS, REGISTRATION_ATTEMPT_TIMEOUT)) {
        $error_message = "Too many registration attempts. Please try again later.";
    } else {
        // Verify CAPTCHA
        $captcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (!verify_recaptcha($captcha_response)) {
            $error_message = "CAPTCHA verification failed. Please try again.";
        } else {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $agree_tos = isset($_POST['agree_tos']) ? 1 : 0;

            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $error_message = "All fields are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Invalid email format.";
            } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
                $error_message = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
            } elseif ($password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } elseif (!$agree_tos) {
                $error_message = "You must agree to the Terms of Service.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Check if username or email already exists
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
                    $stmt->execute([$username, $email]);
                    $existing_user = $stmt->fetch();

                    if ($existing_user) {
                        $error_message = "Username or email already exists.";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, tos_agreed) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$username, $email, $password_hash, $agree_tos]);
                        $user_id = $pdo->lastInsertId();

                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;

                        $pdo->commit();
                        $success_message = "Registration successful! Redirecting to dashboard...";
                        header("Refresh: 2; URL=" . APP_URL . "/app/dashboard.php");
                    }
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error_message = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Create an Account</h2>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password (min. <?php echo PASSWORD_MIN_LENGTH; ?> characters)</label>
                <input type="password" name="password" id="password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="agree_tos" id="agree_tos" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <label for="agree_tos" class="ml-2 block text-sm text-gray-900">
                    I agree to the <a href="<?php echo APP_URL; ?>/legal/tos/" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a>
                </label>
            </div>
            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Register
                </button>
            </div>
        </form>
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Already have an account? <a href="<?php echo APP_URL; ?>/auth/login.php" class="font-medium text-indigo-600 hover:text-indigo-500">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>
