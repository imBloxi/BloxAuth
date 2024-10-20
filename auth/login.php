<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        
        // Log the IP address
        $stmt = $pdo->prepare('INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)');
        $stmt->execute([$user['id'], $ip_address]);

        header('Location: ../app/dashboard.php');
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/scripts/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/dist/output.css">
</head>
<body>
    <header>
        <nav class="border-gray-200 px-4 lg:px-6 py-2.5 mb-14">
            <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl">
                <a href="../" class="flex items-center">
                    <!-- Optional logo or brand name -->
                </a>
                <div class="flex items-center lg:order-2">
                    <a href="../auth/register.php" class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-blue-600 rounded-lg hover:opacity-80 transition duration-200">
                        Register
                    </a>
                </div>
                <div class="hidden justify-between items-center w-full lg:flex lg:w-auto lg:order-1" id="mmenu">
                    <ul class="flex flex-col mt-4 font-medium lg:flex-row lg:space-x-8 lg:mt-0">
                        <li>
                            <a href="../app/settings.php" class="block py-2 pr-4 pl-3 border-b lg:hover:bg-transparent lg:border-0 lg:p-0 text-gray-400 hover:bg-gray-700 hover:text-white transition duration-200">Settings</a>
                        </li>
                        <li>
                            <a href="../billing/" class="block py-2 pr-4 pl-3 border-b lg:hover:bg-transparent lg:border-0 lg:p-0 text-gray-400 hover:bg-gray-700 hover:text-white transition duration-200">Billing</a>
                        </li>
                        <li>
                            <a href="../obfuscate" class="block py-2 pr-4 pl-3 border-b lg:hover:bg-transparent lg:border-0 lg:p-0 text-gray-400 hover:bg-gray-700 hover:text-white transition duration-200">Obfuscation Hub</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main>
        <section class="py-8 max-w-screen-xl px-4 mx-auto lg:py-16">
            <h2 class="mb-7 md:mb-12 text-3xl md:text-6xl font-bold font-heading tracking-px-n leading-tight text-center">
                Login to <span class="text-transparent bg-clip-text bg-gradient-to-r to-blue-600 from-sky-400 inline-block">BloxAuth</span> 🚀
            </h2>
            <div class="max-w-md mx-auto bg-[#0f0f17] rounded-xl p-6">
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-600 text-white p-3 rounded mb-4">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                <form method="post" class="space-y-6">
                    <div>
                        <label for="username" class="block mb-2 text-sm font-medium text-white">Username</label>
                        <input type="text" name="username" id="username" class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white" required>
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-white">Password</label>
                        <input type="password" name="password" id="password" class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white" required>
                    </div>
                    <button type="submit" class="btn-primary w-full py-3 text-base font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 transition duration-200">Login</button>
                </form>
                <!-- Add this button to your login form -->
                <a href="https://discord.com/api/oauth2/authorize?client_id=YOUR_DISCORD_CLIENT_ID&redirect_uri=<?= urlencode('https://your-domain.com/auth/api/call_request.php') ?>&response_type=code&scope=identify%20email" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 transition duration-200">
                    Login with Discord
                </a>
            </div>
        </section>
    </main>

    <footer class="bg-[#0f0f17] text-white py-6 px-4 mt-16">
        <div class="max-w-screen-xl mx-auto text-center">
            <p>&copy; 2024 BloxAuth. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>