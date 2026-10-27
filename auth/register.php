<?php
require_once '../includes/config.php';

$error_message = '';
$success_message = '';

if (is_user_logged_in()) {
    redirect('/app/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree_tos = isset($_POST['agree_tos']) ? 1 : 0;

    if (!check_rate_limit('registration', $_SERVER['REMOTE_ADDR'], MAX_REGISTRATION_ATTEMPTS, REGISTRATION_ATTEMPT_TIMEOUT)) {
        $error_message = "Too many registration attempts. Please try again later.";
    } elseif (!verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        $error_message = "CAPTCHA verification failed. Please try again.";
    } elseif ($username === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error_message = "All fields are required.";
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
        $error_message = "Username must be 3-32 characters: letters, numbers, dots, dashes or underscores.";
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
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error_message = "Username or email already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (username, email, password, tos_agreed, tos_agreed_date) VALUES (?, ?, ?, 1, NOW())')
                    ->execute([$username, $email, $password_hash]);
                $user_id = (int) $pdo->lastInsertId();

                login_user($user_id, $username);
                log_user_activity($user_id, 'Registered account');
                redirect('/app/setup_app.php');
            }
        } catch (PDOException $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $error_message = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <?php if (USE_RECAPTCHA): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Create an Account</h2>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo e($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo e($success_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" value="<?php echo e($_POST['username'] ?? ''); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
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
                    I agree to the <a href="<?php echo url('/legal/tos/'); ?>" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a>
                </label>
            </div>
            <?php if (USE_RECAPTCHA): ?>
            <div class="g-recaptcha" data-sitekey="<?php echo e(RECAPTCHA_SITE_KEY); ?>"></div>
            <?php endif; ?>
            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Register
                </button>
            </div>
        </form>
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Already have an account? <a href="<?php echo url('/auth/login.php'); ?>" class="font-medium text-indigo-600 hover:text-indigo-500">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>
