<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php'; // Include the functions file

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_license_key = generateLicenseKey();
    $stmt = $pdo->prepare('INSERT INTO license_keys (license_key) VALUES (?)');
    $stmt->execute([$new_license_key]);
    $message = "New license key generated: $new_license_key";
}

function isAdmin($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT is_staff FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user && $user['is_staff'] == 1;
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue License Keys</title>
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
                <a href="../auth/logout.php" class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-red-600 rounded-lg hover:opacity-80 transition duration-200">
                    Logout
                </a>
            </div>
        </div>
    </nav>
</header>

<main>
    <section class="py-8 max-w-screen-xl px-4 mx-auto lg:py-16">
        <h2 class="mb-7 md:mb-12 text-3xl md:text-6xl font-bold font-heading tracking-px-n leading-tight text-center">
            Issue New License Keys
        </h2>
        <div class="max-w-md mx-auto bg-[#0f0f17] rounded-xl p-6">
            <?php if (!empty($message)): ?>
                <div class="bg-green-600 text-white p-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post" class="space-y-6">
                <button type="submit" class="btn-primary w-full py-3 text-base font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 transition duration-200">Generate New License Key</button>
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