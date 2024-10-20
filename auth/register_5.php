<?php
session_start();
require '../includes/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            echo "All fields are required.";
            exit;
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Check if the username or email already exists
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();

        if ($user) {
            echo "Username or email already exists.";
        } else {
            // Insert the new user into the database
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, $password_hash]);
            header('Location: ../auth/login.php');
            exit;
        }
    }
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    echo 'General error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
                    <a href="../auth/login.php" class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-blue-600 rounded-lg hover:opacity-80 transition duration-200">
                        Login
                    </a>
                </div>
               
            </div>
        </nav>
    </header>
    
    <main>
        <section class="py-8 max-w-screen-xl px-4 mx-auto lg:py-16">
            <h2 class="mb-7 md:mb-12 text-3xl md:text-6xl font-bold font-heading tracking-px-n leading-tight text-center">
                Register to <span class="text-transparent bg-clip-text bg-gradient-to-r to-blue-600 from-sky-400 inline-block">BloxAuth</span> 🚀
            </h2>
            <div class="max-w-md mx-auto bg-[#0f0f17] rounded-xl p-6">
                <form method="post" class="space-y-6">
                    <div>
                        <label for="username" class="block mb-2 text-sm font-medium text-white">Username</label>
                        <input type="text" name="username" id="username" class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white" required>
                    </div>
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-white">Email</label>
                        <input type="email" name="email" id="email" class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white" required>
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-white">Password</label>
                        <input type="password" name="password" id="password" class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white" required>
                    </div>
                    <button type="submit" class="btn-primary w-full py-3 text-base font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 transition duration-200">Register</button>
                </form>
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
