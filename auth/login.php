<?php
require_once '../includes/config.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        try {
            // Check login rate limit
            if (!check_rate_limit('login', $_SERVER['REMOTE_ADDR'], MAX_LOGIN_ATTEMPTS, LOGIN_ATTEMPT_TIMEOUT)) {
                throw new Exception("Too many login attempts. Please try again later.");
            }

            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                if ($user['locked_until'] && $user['locked_until'] > date('Y-m-d H:i:s')) {
                    throw new Exception("Account is locked. Please try again later.");
                }

                if (password_verify($password, $user['password'])) {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Reset login attempts
                    $stmt = $pdo->prepare('UPDATE users SET login_attempts = 0, last_login = NOW() WHERE id = ?');
                    $stmt->execute([$user['id']]);

                    if ($user['two_factor_enabled']) {
                        // Redirect to 2FA verification
                        $_SESSION['2fa_required'] = true;
                        header("Location: two_factor_auth.php");
                        exit;
                    }

                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', true, true);
                        
                        $stmt = $pdo->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
                        $stmt->execute([$token, $user['id']]);
                    }

                    // Log the login
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $stmt = $pdo->prepare('INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)');
                    $stmt->execute([$user['id'], $ip_address]);

                    $success_message = "Login successful! Redirecting to dashboard...";
                    header("Refresh: 2; URL=" . APP_URL . "/app/dashboard.php");
                } else {
                    // Failed login attempt
                    $stmt = $pdo->prepare('UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?');
                    $stmt->execute([$user['id']]);

                    if ($user['login_attempts'] >= 4) {
                        $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                        $stmt = $pdo->prepare('UPDATE users SET locked_until = ? WHERE id = ?');
                        $stmt->execute([$locked_until, $user['id']]);
                        throw new Exception("Too many failed attempts. Account locked for 30 minutes.");
                    }

                    throw new Exception("Invalid username or password.");
                }
            } else {
                throw new Exception("Invalid username or password.");
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
  
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="max-w-md w-full space-y-8 p-10 bg-white rounded-xl shadow-lg z-10">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                Welcome back
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Please sign in to your account
            </p>
        </div>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        <form class="mt-8 space-y-6" action="" method="POST">
            <input type="hidden" name="remember" value="true">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Username">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Password">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a href="<?php echo APP_URL; ?>/auth/forgot-password.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Sign in
                </button>
            </div>
        </form>
        <div class="text-center">
            <p class="mt-2 text-sm text-gray-600">
                Don't have an account? 
                <a href="<?php echo APP_URL; ?>/auth/register.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Sign up
                </a>
            </p>
        </div>
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">
                        Or continue with
                    </span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-3 gap-3">
                <div>
                    <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M20 10c0-5.523-4.477-10-10-10S0 4.477 0 10c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V10h2.54V7.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V10h2.773l-.443 2.89h-2.33v6.988C16.343 19.128 20 14.991 20 10z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>

                <div>
                    <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                </div>

                <div>
                    <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>